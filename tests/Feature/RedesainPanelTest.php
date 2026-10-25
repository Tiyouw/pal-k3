<?php

namespace Tests\Feature;

use App\Filament\Auth\Masuk;
use App\Filament\Pages\Dasbor;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\Issue;
use App\Models\Schedule;
use App\Models\User;
use App\Services\RingkasanDasbor;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RedesainPanelTest extends TestCase
{
    use RefreshDatabase;

    private function aset(): Asset
    {
        $this->seed([MasterSeeder::class, AparSeeder::class]);

        return Asset::where('kode', '01D')->firstOrFail();
    }

    private function inspeksi(Asset $asset, array $data = []): Inspection
    {
        return Inspection::create(array_merge([
            'asset_id' => $asset->id, 'user_id' => User::factory()->inspektur()->create()->id,
            'status' => 'final', 'inspected_at' => now(), 'kesimpulan' => 'layak', 'gate_status' => 'sesuai',
        ], $data));
    }

    private function panel(User $user): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    public function test_kepatuhan_menghitung_unit_aktif_sekali_dan_kondisi_terbaru(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10));
        $asset = $this->aset();
        $this->inspeksi($asset, ['inspected_at' => now()->subMinute(), 'kesimpulan' => 'tidak_layak']);
        $this->inspeksi($asset);
        $this->inspeksi(Asset::where('kode', '02D')->firstOrFail(), ['status' => 'draft']);
        $inactive = Asset::where('kode', '03D')->firstOrFail();
        $inactive->update(['aktif' => false]);
        $this->inspeksi($inactive);
        $this->inspeksi(Asset::where('kode', '04D')->firstOrFail(), ['inspected_at' => now()->startOfMonth()->subDay()]);
        $data = app(RingkasanDasbor::class)->data();
        $this->assertSame(34, $data['total']);
        $this->assertSame(1, $data['diperiksa']);
        $this->assertSame(33, $data['jumlah']['belum']);
        $this->assertSame(1, $data['kondisi']['layak']);
        $this->assertSame(0, $data['kondisi']['tidak_layak']);
    }

    public function test_antrean_mengecualikan_temuan_selesai_dan_inspeksi_yang_sudah_ditinjau(): void
    {
        $asset = $this->aset();
        foreach (['jauh', 'perlu_review', 'gps_lemah', 'tanpa_gps'] as $status) {
            $this->inspeksi($asset, ['gate_status' => $status]);
        }
        $this->inspeksi($asset, ['gate_status' => 'jauh', 'ditinjau_pada' => now()]);
        $this->inspeksi($asset, ['gate_status' => 'jauh', 'status' => 'draft']);
        foreach ([['terbuka', today()->subDay()], ['proses', today()], ['selesai', today()->subDay()]] as [$status, $tanggal]) {
            Issue::create(['asset_id' => $asset->id, 'item' => 'Uji tenggat', 'severity' => 'berat', 'status' => $status, 'target_selesai' => $tanggal]);
        }
        $data = app(RingkasanDasbor::class)->data();
        $this->assertSame(4, $data['jumlah']['posisi']);
        $this->assertSame(2, $data['jumlah']['berat']);
        $this->assertSame(1, $data['jumlah']['lewat']);
    }

    public function test_dasbor_kosong_tetap_terbaca_dan_get_tidak_membuat_jadwal(): void
    {
        $this->panel(User::factory()->admin()->create());
        $this->get('/admin')->assertOk()->assertSee('Belum ada data APAR');
        $this->assertSame(0, Schedule::count());
    }

    public function test_antrean_tab_dan_paginasi_memuat_semua_temuan(): void
    {
        $asset = $this->aset();
        $this->panel(User::factory()->admin()->create());
        for ($i = 1; $i <= 7; $i++) {
            Issue::create(['asset_id' => $asset->id, 'item' => 'Kerusakan unik '.$i, 'severity' => 'berat', 'status' => 'terbuka']);
        }
        Livewire::test(Dasbor::class)->assertSee('Kerusakan unik 1')->assertDontSee('Kerusakan unik 7')
            ->call('gotoPage', 2)->assertSee('Kerusakan unik 7')
            ->call('pilihTab', 'belum')->assertSee('Belum ada inspeksi final bulan ini');
    }

    #[DataProvider('aksiYangMengurangiAntrean')]
    public function test_halaman_antrean_disesuaikan_setelah_entri_terakhir_diproses(string $tab, string $action): void
    {
        $this->freezeTime();
        $asset = $this->aset();
        $this->panel(User::factory()->admin()->create());

        for ($i = 1; $i <= 7; $i++) {
            $record = $tab === 'posisi'
                ? $this->inspeksi($asset, ['gate_status' => 'jauh'])
                : Issue::create([
                    'asset_id' => $asset->id, 'item' => 'Antrean perbaikan '.$i,
                    'severity' => 'berat', 'status' => 'terbuka',
                    'target_selesai' => today()->subDay(),
                ]);
        }

        $data = match ($action) {
            'tinjau' => ['catatan_review' => 'Kehadiran diverifikasi melalui foto.'],
            'tenggat' => ['target_selesai' => today()->addDay()->toDateString()],
            'selesai' => ['tindak_lanjut' => 'Komponen diganti dan diuji kembali.'],
        };

        Livewire::test(Dasbor::class)
            ->call('pilihTab', $tab)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->callAction($action, $data, ['id' => $record->id])
            ->assertHasNoActionErrors()
            ->assertSet('paginators.page', 1)
            ->assertDontSee('Tidak ada antrean di kategori ini');

        $this->assertSame(6, app(RingkasanDasbor::class)->antrean($tab)->count());
        $expected = $action === 'tenggat'
            ? ['target_selesai' => today()->addDay()->toDateTimeString()]
            : $data;
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, ...$expected]);
    }

    public static function aksiYangMengurangiAntrean(): array
    {
        return [
            'selesaikan temuan berat' => ['berat', 'selesai'],
            'jadwalkan ulang temuan lewat tenggat' => ['lewat', 'tenggat'],
            'tinjau bukti posisi' => ['posisi', 'tinjau'],
        ];
    }

    public function test_tautan_halaman_antrean_yang_sudah_tidak_ada_kembali_ke_halaman_tersedia(): void
    {
        $asset = $this->aset();
        Issue::create(['asset_id' => $asset->id, 'item' => 'Selang pecah', 'severity' => 'berat', 'status' => 'terbuka']);
        $this->panel(User::factory()->admin()->create());

        $this->get('/admin?page=9')->assertOk()->assertSee('Selang pecah');
    }

    public function test_tab_tidak_dapat_diubah_langsung_melewati_validasi(): void
    {
        $this->panel(User::factory()->admin()->create());

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(Dasbor::class)->set('tab', 'tidak-dikenal');
    }

    public function test_tinjauan_memerlukan_alasan_dan_menyimpan_admin_tanpa_mengubah_gps(): void
    {
        $inspection = $this->inspeksi($this->aset(), ['gate_status' => 'gps_lemah', 'jarak_m' => 140]);
        $admin = User::factory()->admin()->create();
        $this->panel($admin);
        Livewire::test(Dasbor::class)->callAction('tinjau', ['catatan_review' => 'pendek'], ['id' => $inspection->id])
            ->assertHasActionErrors(['catatan_review' => 'min']);
        $this->assertNull($inspection->fresh()->ditinjau_pada);
        Livewire::test(Dasbor::class)->callAction('tinjau', ['catatan_review' => 'Kehadiran terverifikasi melalui foto dan QR.'], ['id' => $inspection->id])->assertHasNoActionErrors();
        $inspection->refresh();
        $this->assertSame($admin->id, $inspection->ditinjau_oleh);
        $this->assertNotNull($inspection->ditinjau_pada);
        $this->assertSame(140, $inspection->jarak_m);
        $this->assertSame('gps_lemah', $inspection->gate_status);
        $this->assertSame(0, app(RingkasanDasbor::class)->tinjauan()->count());
    }

    public function test_admin_menetapkan_tenggat_dan_menyelesaikan_temuan(): void
    {
        $issue = Issue::create(['asset_id' => $this->aset()->id, 'item' => 'Segel rusak', 'severity' => 'berat', 'status' => 'terbuka']);
        $this->panel(User::factory()->admin()->create());
        Livewire::test(Dasbor::class)->callAction('tenggat', ['target_selesai' => today()->subDay()->toDateString()], ['id' => $issue->id])->assertHasActionErrors();
        Livewire::test(Dasbor::class)->callAction('tenggat', ['target_selesai' => today()->addDay()->toDateString()], ['id' => $issue->id])->assertHasNoActionErrors();
        $this->assertSame(today()->addDay()->toDateString(), $issue->fresh()->target_selesai->toDateString());
        Livewire::test(Dasbor::class)->callAction('selesai', ['tindak_lanjut' => 'Segel diganti dan kondisi diuji kembali.'], ['id' => $issue->id])->assertHasNoActionErrors();
        $this->assertSame('selesai', $issue->fresh()->status);
        $this->assertNotNull($issue->fresh()->selesai_pada);
    }

    public function test_pemantau_tidak_bisa_memanggil_aksi_tulis_langsung(): void
    {
        $asset = $this->aset();
        $inspection = $this->inspeksi($asset, ['gate_status' => 'tanpa_gps']);
        $issue = Issue::create(['asset_id' => $asset->id, 'item' => 'Segel rusak', 'severity' => 'berat', 'status' => 'terbuka']);
        $this->panel(User::factory()->pemantau()->create());
        $this->get('/admin')->assertOk()->assertSee('AKSES PEMANTAU')->assertDontSee('Tetapkan tenggat')->assertDontSee('Data Induk');
        Livewire::test(Dasbor::class)->call('mountAction', 'tinjau', ['id' => $inspection->id])->call('callMountedAction');
        Livewire::test(Dasbor::class)->call('mountAction', 'tenggat', ['id' => $issue->id])->call('callMountedAction');
        Livewire::test(Dasbor::class)->call('mountAction', 'selesai', ['id' => $issue->id])->call('callMountedAction');
        $this->assertNull($inspection->fresh()->ditinjau_pada);
        $this->assertNull($issue->fresh()->target_selesai);
        $this->assertSame('terbuka', $issue->fresh()->status);
    }

    public function test_login_panel_memakai_nip_dan_menolak_peran_inspektur_atau_nonaktif(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->admin()->create(['nip' => '001A', 'password' => 'sandi-pengujian']);
        Livewire::test(Masuk::class)->fillForm(['nip' => '001A', 'password' => 'sandi-pengujian'])->call('authenticate')->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($admin);
        auth()->logout();
        foreach ([User::factory()->inspektur(), User::factory()->admin()->nonaktif()] as $factory) {
            $user = $factory->create(['password' => 'sandi-pengujian']);
            Livewire::test(Masuk::class)->fillForm(['nip' => $user->nip, 'password' => 'sandi-pengujian'])->call('authenticate')->assertHasFormErrors(['nip']);
            $this->assertGuest();
        }
    }
}

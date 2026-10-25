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
        $this->get('/admin')->assertOk()->assertSee('Akses hanya baca')->assertDontSee('Tetapkan tenggat')->assertDontSee('Data Induk');
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

    public function test_peta_memuat_semua_aset_bertitik_dengan_koordinat_di_dalam_kanvas(): void
    {
        $this->aset();
        $peta = app(RingkasanDasbor::class)->data()['peta'];

        $this->assertSame(0, $peta['tanpa_titik']);
        $this->assertCount(35, $peta['zona']['Semua']['titik']);
        $this->assertArrayHasKey('Gedung PIP · Lt. 1', $peta['zona']);
        $this->assertSame(35, collect($peta['zona'])->except('Semua')->sum('total'));

        foreach ($peta['zona'] as $zona) {
            foreach ($zona['titik'] as $titik) {
                $this->assertGreaterThanOrEqual(0, $titik['x']);
                $this->assertLessThanOrEqual($zona['lebar'], $titik['x']);
                $this->assertGreaterThanOrEqual(0, $titik['y']);
                $this->assertLessThanOrEqual($zona['tinggi'], $titik['y']);
                $this->assertTrue(is_finite($titik['x']) && is_finite($titik['y']));
            }
        }
    }

    public function test_peta_tidak_membocorkan_token_qr_dan_hanya_membawa_kolom_yang_perlu(): void
    {
        $asset = $this->aset();
        $json = json_encode(app(RingkasanDasbor::class)->data()['peta']);

        $this->assertStringNotContainsString($asset->qr_token, $json);
        $this->assertStringNotContainsString('PAL-K3-', $json);
        $this->assertStringNotContainsString('qr_token', $json);
    }

    public function test_aset_tanpa_koordinat_dihitung_tetapi_tidak_digambar(): void
    {
        $this->aset();
        Asset::where('kode', '01D')->update(['lat' => null, 'lng' => null]);

        $peta = app(RingkasanDasbor::class)->data()['peta'];

        $this->assertSame(1, $peta['tanpa_titik']);
        $this->assertCount(34, $peta['zona']['Semua']['titik']);
        $this->assertNotContains('01D', collect($peta['zona']['Semua']['titik'])->pluck('kode')->all());
    }

    public function test_zona_satu_titik_atau_berkoordinat_sama_tidak_membagi_dengan_nol(): void
    {
        $this->aset();
        $asset = Asset::where('kode', '01D')->firstOrFail();
        Asset::query()->where('gedung', $asset->gedung)->where('lantai', $asset->lantai)->update(['lat' => $asset->lat, 'lng' => $asset->lng]);

        $zona = app(RingkasanDasbor::class)->data()['peta']['zona'];

        foreach ($zona as $z) {
            foreach ($z['titik'] as $titik) {
                $this->assertTrue(is_finite($titik['x']) && is_finite($titik['y']), 'koordinat harus berhingga');
            }
        }
    }

    public function test_peta_menandai_status_dan_tinjauan_posisi(): void
    {
        $asset = $this->aset();
        $this->inspeksi($asset, ['kesimpulan' => 'tidak_layak', 'gate_status' => 'jauh']);
        $this->inspeksi(Asset::where('kode', '02D')->firstOrFail(), ['kesimpulan' => 'layak_catatan']);
        $this->inspeksi(Asset::where('kode', '03D')->firstOrFail(), ['gate_status' => 'jauh', 'ditinjau_pada' => now()]);

        $titik = collect(app(RingkasanDasbor::class)->data()['peta']['zona']['Semua']['titik'])->keyBy('kode');

        $this->assertSame('tidak_layak', $titik['01D']['status']);
        $this->assertTrue($titik['01D']['tinjau']);
        $this->assertSame('layak_catatan', $titik['02D']['status']);
        $this->assertFalse($titik['02D']['tinjau']);
        $this->assertFalse($titik['03D']['tinjau'], 'yang sudah ditinjau tidak lagi menunggu tinjauan');
        $this->assertSame('belum', $titik['04D']['status']);
    }

    public function test_dasbor_admin_memuat_sakelar_ikon_peta_dan_zona_tanpa_token_qr(): void
    {
        $asset = $this->aset();
        $this->panel(User::factory()->admin()->create());

        $this->get('/admin')->assertOk()
            ->assertSee('aria-label="Tampilan titik per area"', false)
            ->assertSee('aria-label="Tampilan peta"', false)
            ->assertSee('k3-peta-svg', false)
            ->assertSee('Peta skematik, bukan skala')
            ->assertDontSee($asset->qr_token);
    }

    public function test_pemantau_melihat_ringkasan_manajemen_tanpa_antrean_dan_aksi_tulis(): void
    {
        $asset = $this->aset();
        Issue::create(['asset_id' => $asset->id, 'item' => 'Segel hilang uji', 'severity' => 'berat', 'status' => 'terbuka', 'target_selesai' => today()->subDay()]);
        $this->panel(User::factory()->pemantau()->create());

        $this->get('/admin')->assertOk()
            ->assertSee('Ringkasan untuk manajemen')
            ->assertSee('Risiko terbuka')
            ->assertSee('Segel hilang uji')
            ->assertSee('Lewat tenggat')
            ->assertSee('Laporan siap cetak')
            ->assertSee(route('laporan.pms'), false)
            ->assertSee(route('laporan.temuan'), false)
            ->assertSee('Akses hanya baca')
            ->assertDontSee('PERLU KEPUTUSAN ANDA')
            ->assertDontSee('Tetapkan tenggat')
            ->assertDontSee('Tandai selesai')
            ->assertDontSee('Tinjau posisi')
            ->assertDontSee('Data Induk');
    }

    public function test_admin_tidak_melihat_ringkasan_manajemen_pemantau(): void
    {
        $this->aset();
        $this->panel(User::factory()->admin()->create());

        $this->get('/admin')->assertOk()->assertSee('PERLU KEPUTUSAN ANDA')->assertDontSee('Ringkasan untuk manajemen');
    }

    public function test_risiko_mengurutkan_berat_dulu_lalu_tenggat_terdekat(): void
    {
        $asset = $this->aset();
        $buat = fn (string $item, string $berat, ?string $tenggat) => Issue::create(['asset_id' => $asset->id, 'item' => $item, 'severity' => $berat, 'status' => 'terbuka', 'target_selesai' => $tenggat]);
        $buat('sedang-tanpa-tenggat', 'sedang', null);
        $buat('berat-tanpa-tenggat', 'berat', null);
        $buat('berat-lusa', 'berat', today()->addDays(2)->toDateString());
        $buat('berat-kemarin', 'berat', today()->subDay()->toDateString());
        Issue::create(['asset_id' => $asset->id, 'item' => 'sudah-selesai', 'severity' => 'berat', 'status' => 'selesai']);

        $urutan = app(RingkasanDasbor::class)->risiko()->pluck('item')->all();

        $this->assertSame(['berat-kemarin', 'berat-lusa', 'berat-tanpa-tenggat', 'sedang-tanpa-tenggat'], $urutan);
    }

    public function test_narasi_mengikuti_keadaan_data(): void
    {
        $ringkasan = app(RingkasanDasbor::class);

        $this->assertStringContainsString('Belum ada APAR aktif', $ringkasan->narasi(['total' => 0]));
        $this->assertStringContainsString('Belum ada inspeksi bulan ini', $ringkasan->narasi(['total' => 35, 'diperiksa' => 0, 'hari' => 9]));

        $teks = $ringkasan->narasi(['total' => 35, 'diperiksa' => 22, 'persen' => 62.9, 'hari' => 9, 'kondisi' => collect(['tidak_layak' => 4]),
            'jumlah' => ['berat' => 2, 'lewat' => 1, 'posisi' => 3]]);
        $this->assertStringContainsString('22 dari 35 APAR', $teks);
        $this->assertStringContainsString('62,9%', $teks);
        $this->assertStringContainsString('4 unit dinyatakan tidak layak', $teks);
        $this->assertStringContainsString('2 temuan berat dan 1 temuan lewat tenggat', $teks);
        $this->assertStringContainsString('3 inspeksi', $teks);
    }

    public function test_merek_geofencing_di_panel_login_dan_favicon(): void
    {
        $this->aset();
        $this->get('/admin/login')->assertOk()
            ->assertSee('Geofencing')
            ->assertSee('geofencing.svg', false)
            ->assertSee('k3-merek-tanda', false)
            ->assertDontSee('k3-monogram', false)
            ->assertDontSee('k3SidebarDesktop', false);

        $this->get('/masuk')->assertOk()->assertSee('Geofencing')->assertSee('k3-merek-tanda', false);
    }

    public function test_panel_memuat_skrip_sidebar_dan_kaki_yang_menempel_untuk_pengguna_masuk(): void
    {
        $this->aset();
        $this->panel(User::factory()->admin()->create(['name' => 'Admin Uji Coba']));

        $this->get('/admin')->assertOk()
            ->assertSee('k3SidebarDesktop', false)
            ->assertSee('aria-keyshortcuts="Control+B Meta+B"', false)
            ->assertSee('Ciutkan menu')
            ->assertSee('Admin Uji Coba')
            ->assertSee('Administrator K3')
            ->assertSee('geofencing.svg', false);
    }
}

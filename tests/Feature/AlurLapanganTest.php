<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Alur lapangan Bagian 7.1 tahap 3 sampai 10, diuji lewat HTTP.
 *
 * Smoke HTTP di peladen nyata memakai APP_DEBUG=false, jadi galat apa pun di
 * alur ini hanya tampak sebagai "Server Error" tanpa jejak. Test ini memakai
 * withoutExceptionHandling() supaya penyebabnya terbaca utuh.
 */
class AlurLapanganTest extends TestCase
{
    use RefreshDatabase;

    private User $inspektur;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterSeeder::class);
        $this->seed(AparSeeder::class);

        // Default factory sudah inspektur, tapi ditulis eksplisit supaya test
        // tidak ikut berubah kalau default factory digeser nanti.
        $this->inspektur = User::factory()->create(['role' => 'inspektur']);
        $this->asset = Asset::query()->firstOrFail();
    }

    protected function tearDown(): void
    {
        // Waktu uji yang dibekukan di satu test tidak boleh bocor ke test lain
        // dalam proses yang sama.
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function pemindai_terbuka_untuk_inspektur(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->inspektur)
            ->get('/petugas/pindai')
            ->assertOk();
    }

    /** Tahap 4 sampai 6: token sah menukar diri jadi draf lalu mengalihkan. */
    #[Test]
    public function token_sah_membuat_draf_dan_mengalihkan_ke_formulir(): void
    {
        $this->withoutExceptionHandling();

        $respons = $this->actingAs($this->inspektur)
            ->get('/i/'.$this->asset->qr_token);

        $inspeksi = Inspection::query()
            ->where('asset_id', $this->asset->id)
            ->where('user_id', $this->inspektur->id)
            ->firstOrFail();

        $this->assertSame('draf', $inspeksi->status);
        $this->assertTrue((bool) $inspeksi->qr_verified);

        $respons->assertRedirect(route('petugas.inspeksi.isi', $inspeksi));
    }

    /** Formulir checklist harus merender, bukan sekadar rute terdaftar. */
    #[Test]
    public function formulir_checklist_merender(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->inspektur)->get('/i/'.$this->asset->qr_token);

        $inspeksi = Inspection::query()->where('user_id', $this->inspektur->id)->firstOrFail();

        $this->actingAs($this->inspektur)
            ->get(route('petugas.inspeksi.isi', $inspeksi))
            ->assertOk()
            ->assertSee($this->asset->kode);
    }

    /** Pindai ulang stiker yang sama memakai draf lama, tidak menumpuk draf. */
    #[Test]
    public function pindai_ulang_memakai_draf_yang_sama(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->inspektur)->get('/i/'.$this->asset->qr_token);
        $this->actingAs($this->inspektur)->get('/i/'.$this->asset->qr_token);

        $this->assertSame(1, Inspection::query()
            ->where('asset_id', $this->asset->id)
            ->where('user_id', $this->inspektur->id)
            ->count());
    }

    /** Stiker palsu tidak boleh 500, harus halaman gagal yang bisa dibaca. */
    #[Test]
    public function token_tak_dikenal_menampilkan_halaman_gagal(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->inspektur)
            ->get('/i/PAL-K3-0000000000000000000000000000dead')
            ->assertOk()
            ->assertSee('Stiker tidak dikenali');
    }

    /** Beranda dan riwayat petugas ikut diuji karena satu layout. */
    #[Test]
    public function beranda_dan_riwayat_petugas_merender(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->inspektur)->get('/petugas')->assertOk();
        $this->actingAs($this->inspektur)->get('/petugas/riwayat')->assertOk();
    }

    /**
     * Urgensi tenggat periode tampil di beranda, bukan cuma di panel admin.
     *
     * Tenggat APAR = tanggal 30 (periode 30 hari NFPA 10). Tiga keadaan waktu
     * diuji: lewat tenggat (merah), mepet tiga hari (kuning), dan waktu cukup
     * (abu tanpa alarm).
     */
    #[Test]
    public function beranda_menandai_urgensi_tenggat_periode(): void
    {
        $this->withoutExceptionHandling();

        Carbon::setTestNow(Carbon::create(2026, 3, 31, 10));
        $this->actingAs($this->inspektur)->get('/petugas')
            ->assertOk()
            ->assertSee('Terlewat 1 hari')
            ->assertSee('melewati tenggat 30 Mar');

        Carbon::setTestNow(Carbon::create(2026, 3, 28, 10));
        $this->actingAs($this->inspektur)->get('/petugas')
            ->assertOk()
            ->assertSee('Tinggal 3 hari');

        Carbon::setTestNow(Carbon::create(2026, 3, 20, 10));
        $this->actingAs($this->inspektur)->get('/petugas')
            ->assertOk()
            ->assertSee('Tenggat 30 Mar');
    }

    /**
     * Tabel inspeksi di panel admin dengan baris draf nyata.
     *
     * Smoke panel yang lain berjalan di tabel kosong, jadi closure kolom tidak
     * pernah dievaluasi dan galat nama parameter lolos. Draf punya inspected_at,
     * kesimpulan, jarak_m, dan durasi_detik yang masih NULL sekaligus, jadi
     * baris ini yang paling keras menguji kolom.
     */
    #[Test]
    public function tabel_inspeksi_admin_merender_dengan_baris_draf(): void
    {
        $this->withoutExceptionHandling();

        $this->actingAs($this->inspektur)->get('/i/'.$this->asset->qr_token);

        $draf = Inspection::query()->firstOrFail();
        $this->assertNull($draf->inspected_at);
        $this->assertNull($draf->kesimpulan);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/inspections')
            ->assertOk()
            ->assertSee($this->asset->kode);
    }

    private function drafTerisi(bool $bermasalah = false): Inspection
    {
        $this->actingAs($this->inspektur)->get('/i/'.$this->asset->qr_token);
        $inspeksi = Inspection::where('user_id', $this->inspektur->id)->where('status', 'draf')->firstOrFail();
        $butirBerat = ChecklistItem::where('answer_type', 'boolean')->where('severity_default', 'berat')->firstOrFail();

        foreach (ChecklistItem::orderBy('urut')->get() as $item) {
            $nilai = $item->isDuaNilai() ? 'ok' : $item->daftarPilihan()[0];
            if ($bermasalah && $item->id === $butirBerat->id) {
                $nilai = 'tidak';
            }

            $this->postJson(route('petugas.inspeksi.jawaban', $inspeksi), [
                'checklist_item_id' => $item->id, 'nilai' => $nilai,
            ])->assertOk()->assertJsonPath('tersimpan', true);
        }

        return $inspeksi;
    }

    #[Test]
    #[DataProvider('hasilPemeriksaan')]
    public function pemeriksaan_lengkap_menyimpan_bukti_temuan_dan_jadwal(bool $bermasalah, string $kesimpulan, int $jumlahTemuan): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 17:00:00'));
        Storage::fake('public');
        $inspeksi = $this->drafTerisi($bermasalah);

        $this->postJson(route('petugas.inspeksi.lokasi', $inspeksi), [
            'lat' => $this->asset->lat, 'lng' => $this->asset->lng, 'accuracy' => 5,
        ])->assertOk()->assertJsonPath('status', 'sesuai');
        $this->postJson(route('petugas.inspeksi.foto', $inspeksi), [
            'foto' => UploadedFile::fake()->image('contoh.jpg', 640, 480),
        ])->assertOk()->assertJsonPath('jumlah', 1);

        $this->post(route('petugas.inspeksi.kirim', $inspeksi), [
            'kesimpulan' => $kesimpulan,
            'rekomendasi' => $bermasalah ? 'Ganti komponen dan periksa kembali.' : null,
            'device_time' => '2020-01-01 00:00:00',
        ])->assertRedirect(route('petugas.inspeksi.selesai', $inspeksi))->assertSessionHasNoErrors();

        $inspeksi->refresh();
        $this->assertSame('final', $inspeksi->status);
        $this->assertSame('2026-10-30 17:00:00', $inspeksi->inspected_at->toDateTimeString());
        $this->assertSame($jumlahTemuan, $inspeksi->issues()->count());
        $this->assertDatabaseHas('schedules', ['inspection_id' => $inspeksi->id, 'status' => 'selesai']);
        $this->assertSame('2026-10-30', $this->asset->fresh()->terakhir_dicek->toDateString());
        Storage::disk('public')->assertExists($inspeksi->photos()->firstOrFail()->path);
        $this->get(route('petugas.inspeksi.selesai', $inspeksi))->assertOk()->assertSee($this->asset->kode);

        $this->post(route('petugas.inspeksi.kirim', $inspeksi), ['kesimpulan' => $kesimpulan])
            ->assertRedirect(route('petugas.inspeksi.selesai', $inspeksi));
        $this->assertSame($jumlahTemuan, $inspeksi->issues()->count());
    }

    public static function hasilPemeriksaan(): array
    {
        return [
            'APAR baik' => [false, 'layak', 0],
            'APAR dengan temuan berat' => [true, 'tidak_layak', 1],
        ];
    }

    #[Test]
    public function inspeksi_final_menolak_perubahan_lokasi_dan_penghapusan_foto(): void
    {
        Storage::fake('public');
        $inspeksi = $this->drafTerisi();
        $this->postJson(route('petugas.inspeksi.foto', $inspeksi), [
            'foto' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertOk();
        $foto = $inspeksi->photos()->firstOrFail();
        $inspeksi->update(['status' => 'final', 'inspected_at' => now(), 'gps_lat' => -8.1, 'gps_lng' => 113.7]);

        $this->postJson(route('petugas.inspeksi.lokasi', $inspeksi), [
            'lat' => -7, 'lng' => 112, 'accuracy' => 1,
        ])->assertUnprocessable()->assertJsonPath('pesan', 'Inspeksi sudah dikirim.');
        $this->deleteJson(route('petugas.inspeksi.foto.hapus', [$inspeksi, $foto->id]))
            ->assertUnprocessable()->assertJsonPath('pesan', 'Inspeksi sudah dikirim.');

        $this->assertSame(-8.1, $inspeksi->fresh()->gps_lat);
        $this->assertModelExists($foto);
        Storage::disk('public')->assertExists($foto->path);
    }

    #[Test]
    public function seluruh_jawaban_tanpa_foto_belum_bisa_dikirim(): void
    {
        $inspeksi = $this->drafTerisi();

        $this->post(route('petugas.inspeksi.kirim', $inspeksi), ['kesimpulan' => 'layak'])
            ->assertSessionHasErrors(['kesimpulan' => 'Foto wajib diunggah minimal satu sebelum inspeksi dikirim.']);

        $this->assertSame('draf', $inspeksi->fresh()->status);
        $this->assertDatabaseCount('issues', 0);
    }

    #[Test]
    public function temuan_berat_tidak_bisa_dikirim_sebagai_layak_dan_memerlukan_rekomendasi(): void
    {
        Storage::fake('public');
        $inspeksi = $this->drafTerisi(true);
        $this->postJson(route('petugas.inspeksi.foto', $inspeksi), ['foto' => UploadedFile::fake()->image('bukti.jpg')])->assertOk();

        $this->post(route('petugas.inspeksi.kirim', $inspeksi), ['kesimpulan' => 'layak'])
            ->assertSessionHasErrors(['kesimpulan' => 'Terdapat temuan berat, kesimpulan tidak dapat diisi Layak pakai.']);
        $this->post(route('petugas.inspeksi.kirim', $inspeksi), ['kesimpulan' => 'tidak_layak'])
            ->assertSessionHasErrors(['rekomendasi' => 'Ada temuan pada inspeksi ini, rekomendasi wajib diisi.']);

        $this->assertSame('draf', $inspeksi->fresh()->status);
        $this->assertDatabaseCount('issues', 0);
    }

    #[Test]
    public function jawaban_palsu_dan_angka_pada_butir_pilihan_ditolak(): void
    {
        $this->actingAs($this->inspektur)->get('/i/'.$this->asset->qr_token);
        $inspeksi = Inspection::where('user_id', $this->inspektur->id)->firstOrFail();

        foreach (['boolean', 'select'] as $tipe) {
            $item = ChecklistItem::where('answer_type', $tipe)->firstOrFail();
            $this->postJson(route('petugas.inspeksi.jawaban', $inspeksi), [
                'checklist_item_id' => $item->id, 'nilai' => 'pilihan-tidak-sah',
            ])->assertUnprocessable()->assertJsonValidationErrors('nilai');
            $this->postJson(route('petugas.inspeksi.jawaban', $inspeksi), [
                'checklist_item_id' => $item->id, 'nilai_angka' => 1,
            ])->assertUnprocessable()->assertJsonValidationErrors('nilai_angka');
        }

        $this->assertDatabaseCount('inspection_answers', 0);
    }

    #[Test]
    public function akun_panel_tidak_membuat_draf_saat_membuka_qr(): void
    {
        foreach (['admin', 'pemantau'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/i/'.$this->asset->qr_token)->assertRedirect('/admin');
        }

        $this->assertDatabaseCount('inspections', 0);
    }

    #[Test]
    public function jadwal_baru_telat_setelah_hari_tenggat_berakhir(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 17:00:00'));
        $jadwal = Schedule::pastikanAda($this->asset);
        $this->assertSame('Belum', $jadwal->labelStatus());
        $this->travelTo(Carbon::parse('2026-10-31 00:00:01'));
        $this->assertSame('Terlewat', $jadwal->labelStatus());
        $inspeksi = Inspection::create([
            'asset_id' => $this->asset->id, 'user_id' => $this->inspektur->id,
            'status' => 'final', 'inspected_at' => now(),
        ]);

        $jadwal->tandaiSelesai($inspeksi);

        $this->assertSame('telat', $jadwal->fresh()->status);
    }

    #[Test]
    public function pemeriksaan_ulang_bulan_yang_sama_memakai_satu_jadwal(): void
    {
        $this->travelTo(Carbon::parse('2026-10-26 10:00:00'));
        Storage::fake('public');

        for ($i = 0; $i < 2; $i++) {
            $inspeksi = $this->drafTerisi();
            $this->postJson(route('petugas.inspeksi.foto', $inspeksi), [
                'foto' => UploadedFile::fake()->image('bukti.jpg'),
            ])->assertOk();
            $this->post(route('petugas.inspeksi.kirim', $inspeksi), ['kesimpulan' => 'layak'])
                ->assertRedirect(route('petugas.inspeksi.selesai', $inspeksi))->assertSessionHasNoErrors();
        }

        $this->assertSame(2, Inspection::where('status', 'final')->count());
        $this->assertDatabaseCount('schedules', 1);
        $this->assertDatabaseHas('schedules', ['asset_id' => $this->asset->id, 'inspection_id' => $inspeksi->id]);
    }
}

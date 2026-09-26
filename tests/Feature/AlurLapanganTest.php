<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Inspection;
use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->asset     = Asset::query()->firstOrFail();
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
            ->get('/i/' . $this->asset->qr_token);

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

        $this->actingAs($this->inspektur)->get('/i/' . $this->asset->qr_token);

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

        $this->actingAs($this->inspektur)->get('/i/' . $this->asset->qr_token);
        $this->actingAs($this->inspektur)->get('/i/' . $this->asset->qr_token);

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

        $this->actingAs($this->inspektur)->get('/i/' . $this->asset->qr_token);

        $draf = Inspection::query()->firstOrFail();
        $this->assertNull($draf->inspected_at);
        $this->assertNull($draf->kesimpulan);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/inspections')
            ->assertOk()
            ->assertSee($this->asset->kode);
    }
}

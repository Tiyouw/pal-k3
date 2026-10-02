<?php

namespace Tests\Feature;

use App\Models\Asset;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Halaman muka membaca tabel asset_types untuk menyusun kartu modul, jadi tes
 * ini wajib memakai RefreshDatabase + seeder. Versi bawaan Laravel tidak
 * menyentuh basis data dan gagal dengan "no such table: asset_types".
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterSeeder::class);
        $this->seed(AparSeeder::class);
    }

    #[Test]
    public function halaman_muka_merender_dan_memuat_empat_kartu_modul(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('APAR', false);
        $response->assertSee('Hydrant', false);
    }

    /** Tamu di halaman muka diarahkan ke halaman masuk petugas, bukan panel admin. */
    #[Test]
    public function tamu_melihat_tautan_masuk_petugas(): void
    {
        $this->get('/')->assertOk()->assertSee(route('petugas.masuk'), false);
    }

    /**
     * Halaman muka terbuka tanpa masuk. Token QR adalah satu-satunya penentu
     * keaslian pemeriksaan, dan sebaran titik APAR tidak layak diumumkan.
     */
    #[Test]
    public function halaman_muka_tidak_membuka_token_qr_maupun_lokasi_aset(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/PAL-K3-[0-9a-f]{32}/', $html);

        $rahasia = Asset::all()
            ->flatMap(fn (Asset $a) => [$a->qr_token, $a->lokasi_teks, $a->lat, $a->lng])
            ->filter(fn ($nilai) => filled($nilai))
            ->map(fn ($nilai) => (string) $nilai);

        $this->assertNotEmpty($rahasia, 'Seeder APAR seharusnya mengisi aset untuk diperiksa.');

        foreach ($rahasia as $nilai) {
            $this->assertStringNotContainsString($nilai, $html);
        }
    }

    /** Keluhan pengguna atas versi lama: emoji membuat halaman terasa asal jadi. */
    #[Test]
    public function halaman_muka_tanpa_emoji(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u',
            $html,
        );
    }
}

<?php

namespace Tests\Feature;

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
}

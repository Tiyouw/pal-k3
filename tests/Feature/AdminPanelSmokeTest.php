<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bukti panel admin benar-benar merender, bukan sekadar route terdaftar.
 * Laravel 13 lebih baru dari matriks resmi Filament v3, jadi ini harus diuji.
 */
class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterSeeder::class);
        $this->seed(AparSeeder::class);

        // Wajib state admin(): default factory adalah inspektur dan akan ditolak 403.
        $this->admin = User::factory()->admin()->create([
            'name'  => 'Admin K3',
            'email' => 'admin@pal.test',
        ]);
    }

    #[Test]
    public function tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    #[Test]
    public function halaman_masuk_merender_form_livewire(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        // Form Filament dirender Livewire, jadi penanda yang dicari wire:*, bukan name="email".
        $response->assertSee('wire:snapshot', escape: false);
        $response->assertSee('data.email', escape: false);
        $response->assertSee('data.password', escape: false);
    }

    /** Locale id: Filament menerjemahkan "Dashboard" jadi "Dasbor", jadi asersi tidak boleh bergantung bahasa. */
    #[Test]
    public function dasbor_terbuka_setelah_masuk(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('lang="id"', escape: false);
        $response->assertSee('wire:snapshot', escape: false);
        // Menu navigasi resource harus muncul, bukti panel benar-benar terpasang.
        $response->assertSee('/admin/assets', escape: false);
    }

    #[Test]
    #[DataProvider('halamanDaftar')]
    public function halaman_daftar_resource_merender(string $path): void
    {
        $this->actingAs($this->admin)
            ->get($path)
            ->assertOk()
            ->assertSee('wire:snapshot', escape: false);
    }

    public static function halamanDaftar(): array
    {
        return [
            'divisi'          => ['/admin/divisions'],
            'tipe objek'      => ['/admin/asset-types'],
            'aset'            => ['/admin/assets'],
            'grup checklist'  => ['/admin/checklist-groups'],
            'item checklist'  => ['/admin/checklist-items'],
            'inspeksi'        => ['/admin/inspections'],
            'temuan'          => ['/admin/issues'],
        ];
    }

    #[Test]
    #[DataProvider('halamanBuat')]
    public function halaman_buat_resource_merender(string $path): void
    {
        $this->actingAs($this->admin)
            ->get($path)
            ->assertOk()
            ->assertSee('wire:snapshot', escape: false);
    }

    public static function halamanBuat(): array
    {
        return [
            'buat divisi'     => ['/admin/divisions/create'],
            'buat tipe objek' => ['/admin/asset-types/create'],
            'buat aset'       => ['/admin/assets/create'],
        ];
    }

    /** Daftar aset harus benar-benar memuat data seed, bukan tabel kosong. */
    #[Test]
    public function daftar_aset_memuat_data_seed(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/assets')
            ->assertOk()
            ->assertSee('01D');
    }

    #[Test]
    public function halaman_ubah_aset_terbuka(): void
    {
        $aset = \App\Models\Asset::where('kode', '01D')->firstOrFail();

        $this->actingAs($this->admin)
            ->get("/admin/assets/{$aset->getKey()}/edit")
            ->assertOk()
            ->assertSee('wire:snapshot', escape: false);
    }

    #[Test]
    public function halaman_tak_dikenal_balas_404(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/tidak-ada-halaman-ini')
            ->assertNotFound();
    }

    /**
     * Gerbang peran. Tanpa pengecekan di canAccessPanel, setiap user terautentikasi
     * bisa membuka panel admin, termasuk inspektur lapangan.
     */
    #[Test]
    public function inspektur_ditolak_masuk_panel_admin(): void
    {
        $inspektur = User::factory()->inspektur()->create();

        $this->actingAs($inspektur)->get('/admin')->assertForbidden();
        $this->actingAs($inspektur)->get('/admin/assets')->assertForbidden();
    }

    #[Test]
    public function pemantau_boleh_masuk_panel_admin(): void
    {
        $pemantau = User::factory()->pemantau()->create();

        $this->actingAs($pemantau)->get('/admin')->assertOk();
    }

    /** Petugas yang sudah tidak aktif tidak boleh masuk walau perannya admin. */
    #[Test]
    public function admin_nonaktif_ditolak(): void
    {
        $nonaktif = User::factory()->admin()->nonaktif()->create();

        $this->actingAs($nonaktif)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function peran_bawaan_factory_adalah_inspektur(): void
    {
        $this->assertSame(User::ROLE_INSPEKTUR, User::factory()->create()->role);
        $this->assertNotContains(User::ROLE_INSPEKTUR, User::ROLE_PANEL);
    }
}

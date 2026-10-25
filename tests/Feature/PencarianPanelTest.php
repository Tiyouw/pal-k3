<?php

namespace Tests\Feature;

use App\Filament\Pencarian\PencarianPanel;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\Issue;
use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PencarianPanelTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(User $pengguna): Asset
    {
        $this->seed([MasterSeeder::class, AparSeeder::class]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($pengguna);

        $aset = Asset::where('kode', '14D')->firstOrFail();
        Inspection::create([
            'asset_id' => $aset->id, 'user_id' => User::factory()->inspektur()->create(['name' => 'Inspektur Uji'])->id,
            'status' => 'final', 'inspected_at' => now(), 'kesimpulan' => 'tidak_layak', 'gate_status' => 'sesuai',
        ]);
        Issue::create(['asset_id' => $aset->id, 'item' => 'Manometer di zona merah', 'severity' => 'berat', 'status' => 'terbuka']);

        return $aset;
    }

    /** @return array<string, array<int, GlobalSearchResult>> */
    private function cari(string $kata): array
    {
        $hasil = app(PencarianPanel::class)->getResults($kata);

        return $hasil ? $hasil->getCategories()->map(fn ($isi) => collect($isi)->all())->all() : [];
    }

    public function test_escape_like_menjadikan_wildcard_huruf_biasa(): void
    {
        $this->assertSame('50!%!_!!', PencarianPanel::escapeLike('50%_!'));
    }

    public function test_kata_terlalu_pendek_tidak_mencari_apa_pun(): void
    {
        $this->siapkan(User::factory()->admin()->create());

        $this->assertNull(app(PencarianPanel::class)->getResults('1'));
        $this->assertNull(app(PencarianPanel::class)->getResults('  '));
    }

    public function test_admin_mencari_aset_temuan_dan_inspeksi_sekaligus(): void
    {
        $this->siapkan(User::factory()->admin()->create());

        $hasil = $this->cari('14D');

        $this->assertArrayHasKey('Aset', $hasil);
        $this->assertArrayHasKey('Temuan', $hasil);
        $this->assertArrayHasKey('Inspeksi', $hasil);
        $this->assertStringContainsString('14D', $hasil['Aset'][0]->title);
        $this->assertSame('Manometer di zona merah', $hasil['Temuan'][0]->title);
        $this->assertSame('Inspeksi 14D', $hasil['Inspeksi'][0]->title);
    }

    public function test_nama_petugas_menemukan_inspeksinya(): void
    {
        $this->siapkan(User::factory()->admin()->create());

        $this->assertArrayHasKey('Inspeksi', $this->cari('Inspektur Uji'));
    }

    public function test_token_qr_tidak_bisa_dicari_dan_tidak_pernah_muncul(): void
    {
        $aset = $this->siapkan(User::factory()->admin()->create());

        foreach ([$aset->qr_token, substr($aset->qr_token, 0, 14), 'PAL-K3', substr($aset->qr_token, 8, 12)] as $kata) {
            $this->assertArrayNotHasKey('Aset', $this->cari($kata), "kata '{$kata}' tidak boleh menemukan aset");
        }

        $json = json_encode($this->cari('14D'));
        $this->assertStringNotContainsString($aset->qr_token, $json);
        $this->assertStringNotContainsString('PAL-K3-', $json);
    }

    public function test_wildcard_like_tidak_berlaku_sebagai_wildcard(): void
    {
        $this->siapkan(User::factory()->admin()->create());

        $this->assertSame([], $this->cari('%%'));
        $this->assertSame([], $this->cari('__'));
        $this->assertSame([], $this->cari('1%D'));
    }

    public function test_admin_melihat_tindakan_dan_halaman_data_induk(): void
    {
        $this->siapkan(User::factory()->admin()->create());

        $this->assertArrayHasKey('Tindakan', $this->cari('Tambah aset'));
        $this->assertArrayHasKey('Tindakan', $this->cari('stiker'));
        $this->assertArrayHasKey('Halaman', $this->cari('Divisi'));
    }

    public function test_pemantau_tanpa_tindakan_tulis_aset_dan_halaman_data_induk(): void
    {
        $this->siapkan(User::factory()->pemantau()->create());

        $this->assertArrayNotHasKey('Tindakan', $this->cari('stiker'));
        $this->assertArrayNotHasKey('Tindakan', $this->cari('Tambah aset'));
        $this->assertArrayNotHasKey('Aset', $this->cari('14D'), 'Aset adalah Data Induk, tersembunyi dari pemantau');
        $this->assertArrayNotHasKey('Halaman', $this->cari('Divisi'));
        $this->assertArrayNotHasKey('Halaman', $this->cari('Tipe Objek'));
    }

    public function test_pemantau_tetap_bisa_mencari_halaman_temuan_dan_inspeksi(): void
    {
        $this->siapkan(User::factory()->pemantau()->create());

        $this->assertArrayHasKey('Halaman', $this->cari('Laporan'));
        $hasil = $this->cari('14D');
        $this->assertArrayHasKey('Temuan', $hasil);
        $this->assertArrayHasKey('Inspeksi', $hasil);
    }

    public function test_pemantau_tidak_diarahkan_ke_halaman_ubah_temuan(): void
    {
        $this->siapkan(User::factory()->pemantau()->create());

        $url = $this->cari('14D')['Temuan'][0]->url;

        $this->assertStringNotContainsString('/edit', $url);
    }

    public function test_pencarian_tidak_menulis_ke_basis_data(): void
    {
        $this->siapkan(User::factory()->admin()->create());
        $sebelum = [Asset::count(), Inspection::count(), Issue::count(), User::count()];

        $this->cari('14D');
        $this->cari('stiker');

        $this->assertSame($sebelum, [Asset::count(), Inspection::count(), Issue::count(), User::count()]);
    }

    public function test_halaman_panel_memuat_kolom_cari_dengan_pintasan(): void
    {
        $this->siapkan(User::factory()->admin()->create());

        $this->get('/admin')->assertOk()->assertSee('Cari APAR, temuan, halaman', false);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\InspectionResource\Pages\ListInspections;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DaftarInspeksiTest extends TestCase
{
    use RefreshDatabase;

    private function inspeksi(string $kode, array $data = []): Inspection
    {
        return Inspection::create(array_merge([
            'asset_id' => Asset::where('kode', $kode)->firstOrFail()->id, 'user_id' => User::factory()->inspektur()->create()->id,
            'status' => 'final', 'inspected_at' => now(), 'kesimpulan' => 'layak', 'gate_status' => 'sesuai',
        ], $data));
    }

    /**
     * Regresi: closure tab dan filter memakai parameter bernama $q, padahal Filament menyuntikkan berdasarkan
     * nama $query. Akibatnya Builder kosong tanpa model dan menu Inspeksi error 500 setiap kali ada inspeksi
     * yang menunggu tinjauan (tab bawaan jadi "Antre ditinjau"). Tes daftar lama lolos karena datanya kosong.
     */
    public function test_menu_inspeksi_terbuka_saat_ada_inspeksi_menunggu_tinjauan_dan_tab_serta_filter_bekerja(): void
    {
        $this->seed([MasterSeeder::class, AparSeeder::class]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());

        $menunggu = $this->inspeksi('01D', ['gate_status' => 'jauh', 'kesimpulan' => 'tidak_layak']);
        $normal = $this->inspeksi('02D');
        $durasi = $this->inspeksi('03D', ['durasi_detik' => 10]);

        $this->get('/admin/inspections')->assertOk();

        $uji = fn () => Livewire::test(ListInspections::class);
        $uji()->assertSet('activeTab', 'antre')->assertCanSeeTableRecords([$menunggu])->assertCanNotSeeTableRecords([$normal]);
        $uji()->set('activeTab', 'tidak_layak')->assertCanSeeTableRecords([$menunggu])->assertCanNotSeeTableRecords([$normal]);
        $uji()->set('activeTab', 'menyimpang')->assertCanSeeTableRecords([$menunggu])->assertCanNotSeeTableRecords([$normal]);
        $uji()->set('activeTab', 'semua')->assertCanSeeTableRecords([$menunggu, $normal, $durasi]);
        $uji()->set('activeTab', 'semua')->filterTable('perlu_tinjauan')->assertCanSeeTableRecords([$menunggu])->assertCanNotSeeTableRecords([$normal]);
        $uji()->set('activeTab', 'semua')->filterTable('durasi_singkat')->assertCanSeeTableRecords([$durasi])->assertCanNotSeeTableRecords([$normal]);
    }

    public function test_pemantau_juga_bisa_membuka_menu_inspeksi_yang_berisi_antrean(): void
    {
        $this->seed([MasterSeeder::class, AparSeeder::class]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->pemantau()->create());
        $this->inspeksi('01D', ['gate_status' => 'perlu_review']);

        $this->get('/admin/inspections')->assertOk();
    }
}

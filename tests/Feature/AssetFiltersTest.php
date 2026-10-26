<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Models\Asset;
use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function panel(): void
    {
        $this->seed([MasterSeeder::class, AparSeeder::class]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_filter_koordinat_tetap_menghormati_filter_gedung(): void
    {
        $this->panel();
        $tanpaKoordinat = Asset::where('kode', '01D')->firstOrFail();
        $tanpaKoordinat->update(['gedung' => 'Gedung Uji', 'lat' => null]);
        $lainGedung = Asset::where('kode', '02D')->firstOrFail();
        $lainGedung->update(['gedung' => 'Gedung Lain', 'lng' => null]);
        $lengkap = Asset::where('kode', '03D')->firstOrFail();
        $lengkap->update(['gedung' => 'Gedung Uji']);

        Livewire::test(ListAssets::class)->filterTable('gedung', 'Gedung Uji')->filterTable('tanpa_titik')
            ->assertCanSeeTableRecords([$tanpaKoordinat])->assertCanNotSeeTableRecords([$lainGedung, $lengkap]);
    }

    public function test_filter_jatuh_tempo_bisa_digunakan_tanpa_error_injeksi_query(): void
    {
        $this->panel();
        $lewat = Asset::where('kode', '01D')->firstOrFail();
        $lewat->update(['terakhir_dicek' => '2020-01-01']);
        $belum = Asset::where('kode', '02D')->firstOrFail();
        $belum->update(['terakhir_dicek' => '2099-01-01']);

        Livewire::test(ListAssets::class)->filterTable('jatuh_tempo')
            ->assertCanSeeTableRecords([$lewat])->assertCanNotSeeTableRecords([$belum]);
    }
}

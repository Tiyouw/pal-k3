<?php

namespace App\Filament\Resources\InspectionResource\Pages;

use App\Filament\Resources\InspectionResource;
use App\Models\Asset;
use App\Models\Inspection;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tanpa tombol buat: inspeksi hanya lahir dari pindaian QR di lapangan.
 *
 * Tab "Antre ditinjau" adalah antrean kerja admin yang disebut Bagian 8.2.
 * Ia dipasang sebagai tab, bukan halaman terpisah, supaya peninjau melihat
 * jumlahnya tanpa harus ingat membuka menu lain.
 */
class ListInspections extends ListRecords
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'antre' => Tab::make('Antre ditinjau')
                ->badge(InspectionResource::antreanTinjauan()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->where('status', 'final')
                    ->whereIn('gate_status', Asset::statusPerluTinjauan())
                    ->whereNull('ditinjau_pada')),

            'menyimpang' => Tab::make('Lokasi menyimpang')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->whereIn('gate_status', Asset::statusPerluTinjauan())),

            'tidak_layak' => Tab::make('Tidak layak pakai')
                ->badge(Inspection::where('kesimpulan', 'tidak_layak')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('kesimpulan', 'tidak_layak')),

            'semua' => Tab::make('Semua'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        // Antrean dibuka lebih dahulu: itu satu-satunya tab yang menuntut tindakan.
        return InspectionResource::antreanTinjauan()->exists() ? 'antre' : 'semua';
    }
}

<?php

namespace App\Filament\Resources\InspectionResource\Pages;

use App\Filament\Resources\InspectionResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Halaman ini hanya untuk menuliskan hasil tinjauan. Tombol hapus sengaja
 * TIDAK dipasang: inspeksi adalah bukti kehadiran, dan bukti yang bisa
 * dihapus dari panel admin bukan bukti. Aset yang salah dicek ditangani
 * dengan inspeksi baru plus catatan tinjauan, bukan dengan menghilangkan
 * jejaknya.
 */
class EditInspection extends EditRecord
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}

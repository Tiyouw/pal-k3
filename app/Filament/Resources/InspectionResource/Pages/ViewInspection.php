<?php

namespace App\Filament\Resources\InspectionResource\Pages;

use App\Filament\Resources\InspectionResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Bukti lapangan dibaca, bukan disunting. Halaman ini jadi tujuan bawaan
 * dari tabel supaya sekali klik tidak langsung membuka formulir ubah.
 */
class ViewInspection extends ViewRecord
{
    protected static string $resource = InspectionResource::class;
}

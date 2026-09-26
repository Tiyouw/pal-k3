<?php

namespace App\Filament\Resources\ChecklistItemResource\Pages;

use App\Filament\Resources\ChecklistItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChecklistItem extends CreateRecord
{
    protected static string $resource = ChecklistItemResource::class;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetType extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'nama', 'is_mobile', 'periode_hari', 'urut', 'aktif'];

    protected $casts = [
        'is_mobile'    => 'boolean',
        'aktif'        => 'boolean',
        'periode_hari' => 'integer',
        'urut'         => 'integer',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function checklistGroups(): HasMany
    {
        return $this->hasMany(ChecklistGroup::class)->orderBy('urut');
    }
}

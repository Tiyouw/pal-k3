<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'kode', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}

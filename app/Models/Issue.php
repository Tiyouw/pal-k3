<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'inspection_id', 'checklist_item_id', 'item',
        'severity', 'status', 'tindak_lanjut', 'target_selesai', 'selesai_pada',
    ];

    protected $casts = [
        'target_selesai' => 'date',
        'selesai_pada'   => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    /** Umur temuan dalam hari, dipakai di rekapitulasi laporan. */
    public function umurHari(): int
    {
        $akhir = $this->selesai_pada ?? now();

        return (int) $this->created_at->diffInDays($akhir);
    }
}

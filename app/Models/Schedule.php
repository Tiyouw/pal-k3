<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bagian 4.2: penjadwalan berkala.
 *
 * Satu baris = satu kewajiban inspeksi pada satu periode untuk satu aset.
 * Kepatuhan dihitung dari tabel ini, bukan dari tanggal terakhir dicek,
 * supaya bulan yang terlewat tetap terlihat sebagai lubang.
 */
class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'periode', 'jatuh_tempo', 'inspection_id', 'status',
    ];

    protected $casts = [
        'periode'     => 'date',
        'jatuh_tempo' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Pastikan baris jadwal ada untuk periode bulan tertentu.
     * Idempoten: dipanggil berulang tidak menambah baris ganda.
     */
    public static function pastikanAda(Asset $asset, ?Carbon $bulan = null): self
    {
        $bulan   = ($bulan ?? now())->copy()->startOfMonth();
        $periode = $asset->assetType?->periode_hari ?? 30;

        return static::firstOrCreate(
            ['asset_id' => $asset->id, 'periode' => $bulan->toDateString()],
            ['jatuh_tempo' => $bulan->copy()->addDays($periode - 1)->toDateString()],
        );
    }

    /** Dipanggil setelah inspeksi difinalkan. */
    public function tandaiSelesai(Inspection $inspection): void
    {
        $this->update([
            'inspection_id' => $inspection->id,
            'status'        => $inspection->inspected_at->gt($this->jatuh_tempo) ? 'telat' : 'selesai',
        ]);
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            'selesai' => 'Terlaksana',
            'telat'   => 'Terlaksana (telat)',
            default   => $this->jatuh_tempo->isPast() ? 'Terlewat' : 'Belum',
        };
    }
}

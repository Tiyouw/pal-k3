<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasFactory;

    /** Bagian 6.1: kesimpulan inspeksi. */
    public const KESIMPULAN = [
        'layak'         => 'Layak pakai',
        'layak_catatan' => 'Layak dengan catatan',
        'tidak_layak'   => 'Tidak layak pakai',
    ];

    protected $fillable = [
        'asset_id', 'user_id', 'inspected_at', 'mulai_pada', 'durasi_detik',
        'device_time', 'ua',
        'gps_lat', 'gps_lng', 'gps_accuracy', 'jarak_m',
        'qr_verified', 'gate_status', 'catatan', 'rekomendasi', 'status',
        'kesimpulan', 'ditinjau_pada', 'ditinjau_oleh', 'catatan_review',
    ];

    protected $casts = [
        'inspected_at'  => 'datetime',
        'mulai_pada'    => 'datetime',
        'device_time'   => 'datetime',
        'ditinjau_pada' => 'datetime',
        'durasi_detik'  => 'integer',
        'gps_lat'       => 'float',
        'gps_lng'       => 'float',
        'gps_accuracy'  => 'integer',
        'jarak_m'       => 'integer',
        'qr_verified'   => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(InspectionAnswer::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function adaTemuan(): bool
    {
        return $this->answers->contains(fn (InspectionAnswer $a) => $a->isTemuan());
    }

    public function labelGerbang(): string
    {
        return match ($this->gate_status) {
            'sesuai'        => 'Lokasi sesuai',
            'perlu_review'  => 'Perlu ditinjau',
            'jauh'          => 'Jauh dari aset',
            'gps_lemah'     => 'GPS lemah',
            'tidak_berlaku' => 'Objek bergerak',
            default         => 'Tanpa GPS',
        };
    }

    /** Warna lencana; dipakai panel admin dan riwayat petugas. */
    public function warnaGerbang(): string
    {
        return match ($this->gate_status) {
            'sesuai'       => 'success',
            'perlu_review' => 'warning',
            'jauh'         => 'danger',
            'gps_lemah'    => 'info',
            default        => 'gray',
        };
    }

    public function labelKesimpulan(): string
    {
        return self::KESIMPULAN[$this->kesimpulan] ?? '-';
    }

    public function perluTinjauan(): bool
    {
        return in_array($this->gate_status, Asset::statusPerluTinjauan(), true);
    }

    /**
     * Bagian 5.7: "satu inspektur memindai seluruh aset dalam dua menit".
     * Pengisian di bawah 45 detik untuk 10 item praktis mustahil dilakukan
     * sambil benar-benar memeriksa tabung. Ditandai, tidak ditolak.
     */
    public const DURASI_WAJAR_DETIK = 45;

    public function durasiTakWajar(): bool
    {
        return $this->durasi_detik !== null
            && $this->durasi_detik < self::DURASI_WAJAR_DETIK;
    }
}

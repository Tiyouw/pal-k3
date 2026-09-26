<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Services\PhotoStore;

class InspectionPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['inspection_id', 'path', 'taken_at', 'lat', 'lng', 'bytes'];

    protected $casts = [
        'taken_at' => 'datetime',
        'lat'      => 'float',
        'lng'      => 'float',
        'bytes'    => 'integer',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Alamat publik foto.
     *
     * Memakai disk yang sama dengan PhotoStore. Kalau tautan simbolik
     * public/storage belum dibuat (php artisan storage:link), alamat ini
     * mengembalikan 404 meski berkasnya ada di disk.
     */
    public function url(): string
    {
        return Storage::disk(PhotoStore::DISK)->url($this->path);
    }

    /** Ukuran berkas yang mudah dibaca, dipakai di panel admin. */
    public function ukuran(): string
    {
        $b = $this->bytes ?? 0;

        return $b >= 1048576
            ? number_format($b / 1048576, 1, ',', '.') . ' MB'
            : number_format($b / 1024, 0, ',', '.') . ' KB';
    }

    /** Berkas fisik ikut terhapus saat baris dihapus lewat cara apa pun. */
    protected static function booted(): void
    {
        static::deleting(function (self $photo) {
            Storage::disk(PhotoStore::DISK)->delete($photo->path);
        });
    }
}

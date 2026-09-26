<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionAnswer extends Model
{
    use HasFactory;

    /** Simbol laporan PMS resmi. */
    public const SIMBOL_FUNGSI_OK    = "\u{2713}"; // centang
    public const SIMBOL_FUNGSI_TIDAK = 'X';
    public const SIMBOL_KONDISI_OK   = 'O';
    public const SIMBOL_KONDISI_TDK  = "\u{00D8}"; // O bergaris

    protected $fillable = [
        'inspection_id', 'checklist_item_id', 'nilai', 'nilai_angka', 'catatan',
    ];

    protected $casts = ['nilai_angka' => 'integer'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }

    public function isTemuan(): bool
    {
        return $this->severity() !== null;
    }

    /**
     * Tingkat keparahan temuan, atau null kalau jawaban ini bukan temuan.
     * Satu pintu untuk semua tipe jawaban, dipakai IssueGenerator (Bagian 6.3).
     */
    public function severity(): ?string
    {
        $item = $this->item;

        if (! $item) {
            return null;
        }

        // Jumlah di bawah nilai baku (kotak P3K) = kekurangan isi.
        if ($item->answer_type === 'number') {
            $baku = $item->jumlah_baku;

            if ($baku !== null && $this->nilai_angka !== null && $this->nilai_angka < $baku) {
                return $item->severity_default ?: 'sedang';
            }

            return null;
        }

        // Teks dan tanggal tidak dinilai otomatis.
        if (in_array($item->answer_type, ['text', 'date'], true)) {
            return null;
        }

        return $item->severityUntukNilai($this->nilai);
    }

    /** Kekurangan jumlah untuk kotak P3K, mis. "Kasa steril 14 dari 40, kurang 26". */
    public function kekurangan(): ?int
    {
        if ($this->item?->answer_type !== 'number' || $this->item->jumlah_baku === null) {
            return null;
        }

        $kurang = $this->item->jumlah_baku - (int) $this->nilai_angka;

        return $kurang > 0 ? $kurang : null;
    }

    public function simbol(): string
    {
        $type = $this->item?->answer_type;

        if ($type === 'number') {
            return (string) ($this->nilai_angka ?? '-');
        }

        if ($type === 'fungsi') {
            return $this->nilai === 'ok' ? self::SIMBOL_FUNGSI_OK : self::SIMBOL_FUNGSI_TIDAK;
        }

        if ($type === 'kondisi' || $type === 'boolean') {
            return $this->nilai === 'ok' ? self::SIMBOL_KONDISI_OK : self::SIMBOL_KONDISI_TDK;
        }

        /**
         * Tipe select: sel laporan PMS hanya selebar satu simbol, teks pilihan
         * tidak akan muat. Dicetak O / O-bergaris menurut severity_map, sedangkan
         * teks pilihan yang sebenarnya masuk ke kolom rekomendasi lewat tabel issues.
         */
        if ($type === 'select') {
            return $this->severity() === null
                ? self::SIMBOL_KONDISI_OK
                : self::SIMBOL_KONDISI_TDK;
        }

        return (string) ($this->nilai ?? '-');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistItem extends Model
{
    use HasFactory;

    /** Bagian 4.4 rancangan: dua dimensi penilaian terpisah, bukan sekadar baik/tidak baik. */
    public const TIPE = [
        'fungsi'  => 'Fungsi (bekerja / tidak bekerja)',
        'kondisi' => 'Kondisi (baik / tidak baik)',
        'boolean' => 'Ada / tidak ada',
        'select'  => 'Pilihan',
        'number'  => 'Angka dengan satuan',
        'date'    => 'Tanggal',
        'text'    => 'Teks bebas',
    ];

    public const SEVERITY = [
        'berat'  => 'Berat',
        'sedang' => 'Sedang',
        'ringan' => 'Ringan',
    ];

    protected $fillable = [
        'checklist_group_id', 'label', 'answer_type', 'options', 'jumlah_baku',
        'satuan', 'dasar_hukum', 'keterangan', 'severity_default', 'severity_map',
        'wajib', 'urut', 'aktif',
    ];

    protected $casts = [
        'options'      => 'array',
        'severity_map' => 'array',
        'wajib'        => 'boolean',
        'aktif'        => 'boolean',
        'jumlah_baku'  => 'integer',
        'urut'         => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChecklistGroup::class, 'checklist_group_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(InspectionAnswer::class, 'checklist_item_id');
    }

    /** Dua dimensi laporan PMS: fungsi dicetak centang/silang, kondisi dicetak O / O-garis. */
    public function isDuaNilai(): bool
    {
        return in_array($this->answer_type, ['fungsi', 'kondisi', 'boolean'], true);
    }

    /** Daftar pilihan untuk answer_type = select. */
    public function daftarPilihan(): array
    {
        return array_values($this->options ?? []);
    }

    /**
     * Apakah nilai yang dipilih termasuk temuan, dan seberapa berat.
     * Bagian 6.3: severity_map memetakan pilihan -> tingkat keparahan.
     * Pilihan yang tidak terdaftar di severity_map dianggap BUKAN temuan.
     */
    public function severityUntukNilai(?string $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        $map = $this->severity_map ?? [];

        if ($map !== [] && array_key_exists($nilai, $map)) {
            return $map[$nilai];
        }

        // fungsi / kondisi / boolean: hanya 'tidak' yang jadi temuan.
        if ($nilai === 'tidak') {
            return $this->severity_default ?: 'sedang';
        }

        return null;
    }

    /** Label dua pilihan sesuai dimensi penilaiannya (Bagian 4.4). */
    public function labelNilai(string $nilai): string
    {
        return match ($this->answer_type) {
            'fungsi'  => $nilai === 'ok' ? 'Berfungsi' : 'Tidak berfungsi',
            'boolean' => $nilai === 'ok' ? 'Ada' : 'Tidak ada',
            default   => $nilai === 'ok' ? 'Baik' : 'Tidak baik',
        };
    }
}

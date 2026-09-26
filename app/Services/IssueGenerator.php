<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionAnswer;
use App\Models\Issue;
use Illuminate\Support\Carbon;

/**
 * Bagian 6.3 rancangan: pembangkitan temuan secara otomatis.
 *
 * Setiap jawaban tidak baik melahirkan satu baris di tabel issues dengan tingkat
 * keparahan yang ditentukan aturan. Tabel issues inilah sumber kolom Rekomendasi
 * dan Tindak Lanjut pada laporan bulanan, sehingga laporan tidak perlu diisi ulang
 * secara manual.
 *
 * Tenggat perbaikan menurut tingkat:
 *   berat  -> 3 hari   (tabung diturunkan dari layanan, diganti unit cadangan)
 *   sedang -> 14 hari  (dijadwalkan perbaikan dalam tenggat yang ditetapkan)
 *   ringan -> 30 hari  (dibenahi pada kunjungan berikutnya)
 */
class IssueGenerator
{
    public const TENGGAT_HARI = [
        'berat'  => 3,
        'sedang' => 14,
        'ringan' => 30,
    ];

    public const TINDAKAN = [
        'berat'  => 'Tabung diturunkan dari layanan, diganti unit cadangan',
        'sedang' => 'Dijadwalkan perbaikan dalam tenggat yang ditetapkan',
        'ringan' => 'Dibenahi pada kunjungan berikutnya',
    ];

    /**
     * Bangun ulang temuan untuk satu inspeksi.
     *
     * Sengaja hapus-lalu-buat: inspeksi yang masih berstatus draf bisa disunting
     * berulang, dan temuan basi dari jawaban yang sudah diperbaiki tidak boleh
     * tertinggal. Temuan dari inspeksi LAIN tidak disentuh.
     *
     * @return int jumlah temuan yang dibuat
     */
    public function bangun(Inspection $inspection): int
    {
        $inspection->loadMissing('answers.item.group', 'asset');

        Issue::where('inspection_id', $inspection->id)->delete();

        $dibuat = 0;

        foreach ($inspection->answers as $answer) {
            $severity = $answer->severity();

            if ($severity === null) {
                continue;
            }

            Issue::create([
                'asset_id'          => $inspection->asset_id,
                'inspection_id'     => $inspection->id,
                'checklist_item_id' => $answer->checklist_item_id,
                'item'              => $this->ringkasan($answer),
                'severity'          => $severity,
                'status'            => 'terbuka',
                'target_selesai'    => $this->tenggat($inspection, $severity),
            ]);

            $dibuat++;
        }

        return $dibuat;
    }

    /**
     * Kalimat temuan yang langsung bisa dibaca petugas K3 di laporan,
     * tanpa perlu membuka aplikasi untuk tahu apa yang salah.
     */
    protected function ringkasan(InspectionAnswer $answer): string
    {
        $item  = $answer->item;
        $label = $item->label;

        if ($item->answer_type === 'select') {
            return $label . ': ' . $answer->nilai;
        }

        if ($item->answer_type === 'number') {
            $kurang = $answer->kekurangan();

            return $kurang === null
                ? $label
                : sprintf(
                    '%s: %d dari %d %s, kurang %d',
                    $label,
                    (int) $answer->nilai_angka,
                    (int) $item->jumlah_baku,
                    $item->satuan ?: 'buah',
                    $kurang,
                );
        }

        return $label . ': ' . $item->labelNilai('tidak');
    }

    protected function tenggat(Inspection $inspection, string $severity): Carbon
    {
        $hari = self::TENGGAT_HARI[$severity] ?? 14;
        $awal = $inspection->inspected_at ?? now();

        return $awal->copy()->addDays($hari)->startOfDay();
    }

    /**
     * Bagian 6.1: kesimpulan kelayakan diturunkan dari temuan terberat,
     * supaya kesimpulan tidak bisa berbeda dari isi checklist.
     *
     * Petugas tetap boleh menimpa lewat formulir; nilai ini jadi usulan awal.
     */
    public function usulKesimpulan(Inspection $inspection): string
    {
        $severities = $inspection->answers
            ->map(fn (InspectionAnswer $a) => $a->severity())
            ->filter()
            ->values();

        if ($severities->isEmpty()) {
            return 'layak';
        }

        return $severities->contains('berat') ? 'tidak_layak' : 'layak_catatan';
    }
}

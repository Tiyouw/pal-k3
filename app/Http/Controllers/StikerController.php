<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Bagian 5.3: lembar cetak stiker QR.
 *
 * Keluaran berupa HTML yang dicetak lewat dialog cetak peramban, bukan PDF.
 * Alasannya praktis: stiker dicetak ke kertas label A4 dengan potongan tetap,
 * dan pengelola stiker perlu melihat pratinjau lalu memilih rentang halaman
 * sendiri. Membangun PDF justru menambah selisih ukuran milimeter antara
 * pratinjau dan hasil cetak.
 *
 * Halaman ini tidak boleh terbuka untuk inspektur. Siapa pun yang dapat mencetak
 * stiker dapat memindainya dari mana saja, sehingga bukti kehadiran lewat QR
 * kehilangan artinya. Penjagaan ada di rute: hanya peran panel yang diizinkan.
 */
class StikerController extends Controller
{
    /** Tata letak label yang tersedia, ukuran dalam milimeter. */
    public const TATA_LETAK = [
        '3x8' => ['kolom' => 3, 'baris' => 8, 'lebar' => 63.5, 'tinggi' => 33.9, 'nama' => '24 label per lembar (63,5 x 33,9 mm)'],
        '2x5' => ['kolom' => 2, 'baris' => 5, 'lebar' => 99.1, 'tinggi' => 57.0, 'nama' => '10 label per lembar (99,1 x 57 mm)'],
        '2x4' => ['kolom' => 2, 'baris' => 4, 'lebar' => 99.1, 'tinggi' => 67.7, 'nama' => '8 label per lembar (99,1 x 67,7 mm)'],
    ];

    public function index(Request $request): View
    {
        $data = $request->validate([
            'tipe'   => ['nullable', 'string'],
            'gedung' => ['nullable', 'string'],
            'lantai' => ['nullable', 'string'],
            'kode'   => ['nullable', 'string'],
            'tata'   => ['nullable', 'string', 'in:' . implode(',', array_keys(self::TATA_LETAK))],
        ]);

        $tata = self::TATA_LETAK[$data['tata'] ?? '2x5'];

        $q = Asset::query()
            ->with('assetType')
            ->where('aktif', true)
            ->whereNotNull('qr_token');

        if (! empty($data['tipe'])) {
            $q->whereHas('assetType', fn ($s) => $s->where('slug', $data['tipe']));
        }

        if (! empty($data['gedung'])) {
            $q->where('gedung', $data['gedung']);
        }

        if (! empty($data['lantai'])) {
            $q->where('lantai', $data['lantai']);
        }

        // Pencetakan ulang satu atau dua stiker yang rusak: kode dipisah koma.
        if (! empty($data['kode'])) {
            $kode = collect(explode(',', $data['kode']))
                ->map(fn ($k) => trim($k))
                ->filter()
                ->all();

            $q->whereIn('kode', $kode);
        }

        $aset = $q->orderBy('gedung')->orderBy('lantai')->orderBy('kode')->get();

        return view('stiker.lembar', [
            'aset'     => $aset,
            'tata'     => $tata,
            'kodeTata' => $data['tata'] ?? '2x5',
            'halaman'  => $aset->chunk($tata['kolom'] * $tata['baris']),
            'pilihan'  => self::TATA_LETAK,
            'tipe'     => AssetType::orderBy('urut')->get(),
            'filter'   => $data,
            'gedung'   => $this->daftarGedung(),
        ]);
    }

    private function daftarGedung(): Collection
    {
        return Asset::query()
            ->whereNotNull('gedung')
            ->distinct()
            ->orderBy('gedung')
            ->pluck('gedung');
    }
}

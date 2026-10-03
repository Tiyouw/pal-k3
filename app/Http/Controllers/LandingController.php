<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Bagian 3.1: halaman depan publik.
 *
 * Empat kartu modul ditarik dari tabel asset_types, bukan ditulis tetap di
 * berkas tampilan. Dengan begitu penambahan objek inspeksi berikutnya cukup
 * lewat data induk dan halaman depan ikut menyesuaikan sendiri.
 *
 * Halaman ini tidak memuat jumlah aset, lokasi, maupun angka kepatuhan. Alamat
 * terbuka tanpa masuk, dan sebaran titik APAR di area galangan termasuk
 * keterangan yang tidak layak diumumkan.
 */
class LandingController extends Controller
{
    /** Keterangan modul yang belum punya penjelasan di data induk. */
    private const RINGKASAN = [
        'apar'    => 'Pemeriksaan alat pemadam api ringan setiap bulan: isi, tekanan, kondisi fisik, mulut pancar, dan penempatan.',
        'hydrant' => 'Pemeriksaan jaringan hydrant: rumah hydrant, kopling, selang, nozel, dan katup.',
        'p3k'     => 'Pemeriksaan kotak pertolongan pertama: kelengkapan isi terhadap jumlah baku dan masa kedaluwarsa.',
        'damkar'  => 'Pemeriksaan mobil pemadam kebakaran: mesin, pompa, tangki, dan kelengkapan perlengkapan.',
    ];

    public function index(): View
    {
        $modul = AssetType::query()
            ->orderBy('urut')
            ->get()
            ->map(fn (AssetType $t) => [
                'slug'     => $t->slug,
                'nama'     => $t->nama,
                'periode'  => $this->labelPeriode($t->periode_hari),
                'ringkas'  => self::RINGKASAN[$t->slug] ?? '',
                // Modul yang belum punya checklist ditandai sebagai tahap
                // berikutnya, bukan disembunyikan: pembaca perlu melihat
                // rencana utuhnya.
                'siap'     => $t->aktif && $t->checklistGroups()->exists(),
            ]);

        return view('landing', [
            'modul'  => $modul,
            'masuk'  => Auth::check(),
            'tujuan' => Auth::check()
                ? (Auth::user()->isInspektur() ? route('petugas.beranda') : url('/admin'))
                : route('petugas.masuk'),
        ]);
    }

    private function labelPeriode(?int $hari): string
    {
        return match (true) {
            $hari === null => 'Berkala',
            $hari <= 1     => 'Harian',
            $hari <= 7     => 'Mingguan',
            $hari <= 31    => 'Bulanan',
            $hari <= 93    => 'Tiga bulanan',
            $hari <= 186   => 'Enam bulanan',
            default        => 'Tahunan',
        };
    }
}

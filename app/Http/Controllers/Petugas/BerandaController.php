<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Bagian 7.1 tahap 2: beranda petugas.
 *
 * Isi beranda sengaja dibuat sedikit. Tombol Pindai QR harus bisa ditekan tanpa
 * membaca apa pun lebih dulu, karena petugas memegang ponsel dengan satu tangan
 * sambil berdiri di depan tabung. Ringkasan di bawahnya hanya untuk menjawab
 * "mana yang belum saya kerjakan bulan ini".
 */
class BerandaController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $apar = AssetType::where('slug', 'apar')->first();

        $bulanIni = now()->startOfMonth();

        // Aset yang belum diinspeksi pada periode bulan berjalan.
        $belum = Asset::query()
            ->where('aktif', true)
            ->when($apar, fn ($q) => $q->where('asset_type_id', $apar->id))
            ->whereDoesntHave('inspections', function ($q) use ($bulanIni) {
                $q->where('status', 'final')
                    ->where('inspected_at', '>=', $bulanIni);
            })
            ->orderBy('gedung')
            ->orderBy('lantai')
            ->orderBy('kode')
            ->get();

        $totalAktif = Asset::where('aktif', true)
            ->when($apar, fn ($q) => $q->where('asset_type_id', $apar->id))
            ->count();

        // Inspeksi yang tertinggal sebagai draf, supaya tidak hilang tanpa jejak.
        $draf = Inspection::with('asset')
            ->where('user_id', $user->id)
            ->where('status', 'draf')
            ->latest('updated_at')
            ->get();

        $inspeksiSaya = Inspection::where('user_id', $user->id)
            ->where('status', 'final')
            ->where('inspected_at', '>=', $bulanIni)
            ->count();

        return view('petugas.beranda', [
            'user'         => $user,
            'belum'        => $belum,
            'sudah'        => max($totalAktif - $belum->count(), 0),
            'totalAktif'   => $totalAktif,
            'draf'         => $draf,
            'inspeksiSaya' => $inspeksiSaya,
            'bulanLabel'   => $this->namaBulan($bulanIni->month) . ' ' . $bulanIni->year,
        ]);
    }

    /** Riwayat inspeksi milik petugas yang sedang masuk (Bagian 3.3). */
    public function riwayat(Request $request): View
    {
        $inspeksi = Inspection::with(['asset', 'issues'])
            ->where('user_id', Auth::id())
            ->where('status', 'final')
            ->latest('inspected_at')
            ->paginate(20);

        return view('petugas.riwayat', ['inspeksi' => $inspeksi]);
    }

    private function namaBulan(int $bulan): string
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$bulan] ?? '';
    }
}

<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bagian 7.1 tahap 1: inspektur masuk memakai NIP, bukan surel.
 *
 * Alasan NIP: di lapangan petugas hafal NIP-nya karena dipakai untuk absensi,
 * sedangkan surel dinas jarang diingat dan panjang untuk diketik dengan sarung
 * tangan kerja. Surel tetap tersimpan di basis data untuk keperluan panel admin.
 */
class AuthController extends Controller
{
    /** Bagian 10.3: pembatasan percobaan masuk. */
    private const MAKS_COBA   = 5;
    private const KUNCI_DETIK = 60;

    public function form(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('petugas.beranda');
        }

        return view('petugas.masuk');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nip'      => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ], [], [
            'nip'      => 'NIP',
            'password' => 'kata sandi',
        ]);

        $kunci = 'masuk:' . Str::lower($data['nip']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_COBA)) {
            $sisa = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'nip' => "Terlalu banyak percobaan. Coba lagi dalam {$sisa} detik.",
            ]);
        }

        $berhasil = Auth::attempt(
            ['nip' => $data['nip'], 'password' => $data['password'], 'aktif' => true],
            $request->boolean('ingat'),
        );

        if (! $berhasil) {
            RateLimiter::hit($kunci, self::KUNCI_DETIK);

            // Pesan disatukan: tidak membocorkan apakah NIP terdaftar atau tidak.
            throw ValidationException::withMessages([
                'nip' => 'NIP atau kata sandi tidak cocok, atau akun sudah tidak aktif.',
            ]);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        // Admin dan pemantau yang masuk dari pintu petugas diarahkan ke panelnya.
        if (! $user->isInspektur()) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('petugas.beranda'));
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('petugas.masuk')->with('pesan', 'Anda sudah keluar.');
    }
}

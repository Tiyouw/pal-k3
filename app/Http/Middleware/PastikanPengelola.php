<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga halaman pengelolaan di luar panel Filament: lembar stiker QR dan
 * laporan bulanan.
 *
 * Alasan halaman ini tidak boleh terbuka untuk inspektur: siapa pun yang dapat
 * mencetak stiker dapat memindai kodenya dari mana saja, sehingga pemindaian
 * berhenti menjadi bukti kehadiran di depan objek. Penjagaan berada di peran,
 * bukan pada ketidaktahuan alamat.
 */
class PastikanPengelola
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->aktif && $user->bolehPanel(), 403,
            'Halaman ini hanya untuk Admin K3 dan Pemantau K3LH.');

        return $next($request);
    }
}

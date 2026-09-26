<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjaga antarmuka lapangan agar hanya dibuka pemegang peran Inspektur.
 *
 * Admin dan pemantau yang tersesat ke alamat /petugas diarahkan ke panelnya,
 * bukan diberi galat 403: keduanya pengguna sah, hanya salah pintu. Yang
 * dicegah adalah mereka mengisi checklist atas nama petugas lapangan, karena
 * kolom user_id pada inspeksi dipakai sebagai penanggung jawab di laporan resmi.
 */
class PastikanInspektur
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->aktif) {
            return redirect()->route('petugas.masuk');
        }

        if (! $user->isInspektur()) {
            // Permintaan asinkron tidak boleh dibalas pengalihan: peramban akan
            // menelan HTML panel sebagai jawaban JSON dan galatnya jadi kabur.
            if ($request->expectsJson()) {
                abort(403, 'Antarmuka lapangan hanya untuk Inspektur.');
            }

            return in_array($user->role, User::ROLE_PANEL, true)
                ? redirect('/admin')
                : abort(403, 'Peran Anda tidak berwenang membuka antarmuka lapangan.');
        }

        return $next($request);
    }
}

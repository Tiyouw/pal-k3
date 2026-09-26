<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'inspektur' => \App\Http\Middleware\PastikanInspektur::class,
            'pengelola' => \App\Http\Middleware\PastikanPengelola::class,
        ]);

        // Tamu yang membuka alamat lapangan diarahkan ke pintu masuk petugas,
        // bukan ke /login bawaan Laravel yang tidak ada di aplikasi ini.
        $middleware->redirectGuestsTo(fn () => route('petugas.masuk'));

        /**
         * Aplikasi berada di belakang Cloudflare Tunnel.
         *
         * cloudflared berjalan di mesin yang sama dan meneruskan ke peladen PHP
         * di 127.0.0.1, jadi setiap permintaan sampai sebagai HTTP polos dari
         * localhost. Tanpa ini Laravel menyimpulkan skema "http" dan membuat
         * URL balikan (redirect sesudah masuk, aksi formulir, tautan QR pada
         * stiker) memakai http:// walau pengunjung datang lewat https://.
         *
         * Daftar proxy sengaja dipersempit ke localhost: header X-Forwarded-*
         * hanya boleh dipercaya dari cloudflared di mesin ini, bukan dari
         * sembarang pengirim, supaya skema tidak bisa dipalsukan dari luar.
         */
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

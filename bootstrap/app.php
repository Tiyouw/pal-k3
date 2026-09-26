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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

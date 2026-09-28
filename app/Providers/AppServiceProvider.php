<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Locale Carbon disetel eksplisit karena APP_LOCALE saja tidak
         * memindahkannya. Tanpa ini translatedFormat('F Y') yang dipakai di dua
         * belas tempat (riwayat petugas, laporan PMS, kartu aset) tetap
         * mengeluarkan "September 2026" versi Inggris, dan bulan seperti
         * "October" atau "December" jelas terbaca asing di dokumen K3 yang
         * ditandatangani per divisi.
         */
        $locale = config('app.locale', 'id');

        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        Date::setLocale($locale);
    }
}

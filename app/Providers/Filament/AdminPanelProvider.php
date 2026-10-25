<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Masuk;
use App\Filament\Pages\Dasbor;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Masuk::class)
            ->darkMode(false)
            ->font('Inter Variable', provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarWidth('16rem')
            ->maxContentWidth('full')
            ->brandLogo(fn () => new HtmlString(view('components.admin.merek')->render()))
            ->brandLogoHeight('2.25rem')
            ->navigationGroups(['Kegiatan', 'Data Induk'])
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.pages.kaki-sidebar'))
            // Merek ditulis tetap di sini, tidak diambil dari APP_NAME, supaya
            // judul tab tidak ikut berubah jadi "Laravel" kalau .env di server
            // belum disetel. Sebelum ini judulnya terbaca "Dasbor - Laravel".
            ->brandName('Inspeksi K3 APAR')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dasbor::class,
            ])
            /*
             * Halaman pengelolaan di luar panel disambungkan ke sidebar.
             *
             * Lembar stiker QR dan laporan bulanan adalah rute Blade biasa, bukan
             * Resource Filament, jadi keduanya tidak pernah muncul di navigasi.
             * Akibatnya admin harus mengetik /stiker dan /laporan dari ingatan;
             * pemeriksaan di situs hidup menemukan /stiker malah jalan buntu tanpa
             * satu pun tautan keluar.
             *
             * Wewenangnya tetap dijaga middleware 'pengelola' di routes/web.php;
             * visible() di sini hanya menyembunyikan tautan yang toh akan 403,
             * supaya inspektur tidak disuguhi pintu yang terkunci.
             */
            ->navigationItems([
                NavigationItem::make('Lembar Stiker QR')
                    ->url(fn (): string => route('stiker.index'))
                    ->icon('heroicon-o-qr-code')
                    ->group('Kegiatan')
                    ->sort(8)
                    ->visible(fn (): bool => auth()->user()?->bolehPanel() ?? false),

                NavigationItem::make('Laporan Bulanan')
                    ->url(fn (): string => route('laporan.index'))
                    ->icon('heroicon-o-document-chart-bar')
                    ->group('Kegiatan')
                    ->sort(9)
                    ->visible(fn (): bool => auth()->user()?->bolehPanel() ?? false),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

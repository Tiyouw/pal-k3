<?php

namespace App\Filament\Pages;

use App\Services\RingkasanDasbor;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;

class Dasbor extends Dashboard
{
    use WithPagination;

    protected static string $view = 'filament.pages.dasbor';

    #[Locked]
    public string $tab = 'berat';

    public function getHeading(): string
    {
        return 'Halo, '.auth()->user()->name;
    }

    public function getSubheading(): string
    {
        $tanggal = now()->translatedFormat('l, d F Y').' · Periode '.now()->translatedFormat('F Y');

        return auth()->user()->isPemantau() ? $tanggal.' · Akses hanya baca' : $tanggal;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('laporan')->label('Laporan bulanan')->icon('heroicon-o-document-arrow-down')->url(route('laporan.index'))];
    }

    public function pilihTab(string $tab): void
    {
        abort_unless(in_array($tab, ['berat', 'lewat', 'posisi', 'belum'], true), 404);
        $this->tab = $tab;
        $this->resetPage();
    }

    protected function getViewData(): array
    {
        $ringkasan = app(RingkasanDasbor::class);
        $antrean = $ringkasan->antrean($this->tab)->paginate(6);

        if ($antrean->currentPage() > $antrean->lastPage()) {
            $this->setPage($antrean->lastPage());
            $antrean = $ringkasan->antrean($this->tab)->paginate(6);
        }

        return [...$ringkasan->data(), 'antrean' => $antrean, 'pemantau' => auth()->user()->isPemantau()];
    }

    private function bolehMenulis(): bool
    {
        return (auth()->user()?->aktif ?? false) && (auth()->user()?->isAdmin() ?? false);
    }

    public function tinjauAction(): Action
    {
        return Action::make('tinjau')->label('Tinjau posisi')->modalHeading('Tinjau bukti posisi')
            ->modalDescription('Catat hasil verifikasi kehadiran. Data GPS dan hasil pemeriksaan asli tetap tersimpan.')
            ->authorize(fn (): bool => $this->bolehMenulis())
            ->form([Textarea::make('catatan_review')->label('Alasan atau hasil verifikasi')->required()->minLength(10)->maxLength(2000)->rows(4)])
            ->action(function (array $arguments, array $data): void {
                abort_unless($this->bolehMenulis(), 403);
                $record = app(RingkasanDasbor::class)->tinjauan()->findOrFail($arguments['id'] ?? null);
                $record->update(['catatan_review' => $data['catatan_review'], 'ditinjau_pada' => now(), 'ditinjau_oleh' => auth()->id()]);
                Notification::make()->title('Tinjauan posisi tersimpan')->success()->send();
            });
    }

    public function tenggatAction(): Action
    {
        return Action::make('tenggat')->label('Tetapkan tenggat')->modalHeading('Tetapkan tenggat temuan')
            ->authorize(fn (): bool => $this->bolehMenulis())
            ->fillForm(fn (array $arguments): array => ['target_selesai' => app(RingkasanDasbor::class)->temuan()->findOrFail($arguments['id'] ?? null)->target_selesai?->toDateString()])
            ->form([DatePicker::make('target_selesai')->label('Target penyelesaian')->required()->minDate(today())])
            ->action(function (array $arguments, array $data): void {
                abort_unless($this->bolehMenulis(), 403);
                app(RingkasanDasbor::class)->temuan()->findOrFail($arguments['id'] ?? null)->update(['target_selesai' => $data['target_selesai']]);
                Notification::make()->title('Tenggat tersimpan')->success()->send();
            });
    }

    public function selesaiAction(): Action
    {
        return Action::make('selesai')->label('Tandai selesai')->modalHeading('Selesaikan tindak lanjut')
            ->authorize(fn (): bool => $this->bolehMenulis())
            ->form([Textarea::make('tindak_lanjut')->label('Tindakan yang sudah dilakukan')->required()->minLength(10)->maxLength(2000)->rows(4)])
            ->action(function (array $arguments, array $data): void {
                abort_unless($this->bolehMenulis(), 403);
                app(RingkasanDasbor::class)->temuan()->findOrFail($arguments['id'] ?? null)
                    ->update(['tindak_lanjut' => $data['tindak_lanjut'], 'status' => 'selesai', 'selesai_pada' => now()]);
                Notification::make()->title('Temuan ditandai selesai')->success()->send();
            });
    }
}

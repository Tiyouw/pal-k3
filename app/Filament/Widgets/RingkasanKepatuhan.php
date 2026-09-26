<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Inspection;
use App\Models\Issue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Bagian 10.1: papan kepatuhan periode berjalan.
 *
 * Penyebut diambil dari jumlah aset aktif, bukan dari jumlah inspeksi yang
 * masuk. Kalau penyebutnya inspeksi, angka kepatuhan selalu seratus persen
 * karena aset yang tidak pernah dikunjungi tidak punya baris untuk dihitung.
 */
class RingkasanKepatuhan extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $apar = AssetType::where('slug', 'apar')->first();

        $awal  = now()->startOfMonth();
        $akhir = now()->endOfMonth();

        $total = Asset::where('aktif', true)
            ->when($apar, fn ($q) => $q->where('asset_type_id', $apar->id))
            ->count();

        // distinct asset_id: pemeriksaan ulang pada bulan yang sama tidak boleh
        // dihitung dua kali dan membuat kepatuhan melewati seratus persen.
        $diperiksa = Inspection::where('status', 'final')
            ->whereBetween('inspected_at', [$awal, $akhir])
            ->when($apar, fn ($q) => $q->whereHas('asset', fn ($s) => $s->where('asset_type_id', $apar->id)))
            ->distinct('asset_id')
            ->count('asset_id');

        $persen = $total > 0 ? round($diperiksa / $total * 100, 1) : 0.0;

        $berat = Issue::where('status', '!=', 'selesai')->where('severity', 'berat')->count();

        $lewatTenggat = Issue::where('status', '!=', 'selesai')
            ->whereNotNull('target_selesai')
            ->whereDate('target_selesai', '<', now())
            ->count();

        $perluTinjauan = Inspection::where('status', 'final')
            ->whereIn('gate_status', ['jauh', 'perlu_review', 'gps_lemah'])
            ->whereNull('ditinjau_pada')
            ->count();

        return [
            Stat::make('Kepatuhan bulan ini', $persen . '%')
                ->description("{$diperiksa} dari {$total} unit diperiksa")
                ->descriptionIcon($persen >= 90 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($persen >= 90 ? 'success' : ($persen >= 70 ? 'warning' : 'danger')),

            Stat::make('Belum diperiksa', max($total - $diperiksa, 0) . ' unit')
                ->description('Sisa hari bulan ini: ' . now()->diffInDays($akhir))
                ->descriptionIcon('heroicon-m-clock')
                ->color($total - $diperiksa > 0 ? 'warning' : 'success'),

            Stat::make('Temuan berat terbuka', $berat . ' butir')
                ->description($berat > 0 ? 'Unit turun dari layanan sampai diganti' : 'Tidak ada')
                ->descriptionIcon('heroicon-m-fire')
                ->color($berat > 0 ? 'danger' : 'success'),

            Stat::make('Lewat tenggat', $lewatTenggat . ' butir')
                ->description('Temuan melewati tanggal target penyelesaian')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($lewatTenggat > 0 ? 'danger' : 'success'),

            Stat::make('Perlu tinjauan posisi', $perluTinjauan . ' inspeksi')
                ->description('Bukti GPS meragukan, menunggu keputusan Admin K3')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($perluTinjauan > 0 ? 'warning' : 'success'),
        ];
    }
}

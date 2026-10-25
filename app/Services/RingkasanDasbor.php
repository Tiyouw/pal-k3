<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Inspection;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RingkasanDasbor
{
    public function aset(): Builder
    {
        return Asset::query()->where('aktif', true)->whereHas('assetType', fn (Builder $q) => $q->where('slug', 'apar'));
    }

    public function inspeksi(): Builder
    {
        return Inspection::query()->where('status', 'final')->whereHas('asset', fn (Builder $q) => $q->whereIn('id', $this->aset()->select('id')));
    }

    public function temuan(): Builder
    {
        return Issue::query()->where('status', '!=', 'selesai')->whereHas('asset.assetType', fn (Builder $q) => $q->where('slug', 'apar'));
    }

    public function tinjauan(): Builder
    {
        return Inspection::query()->where('status', 'final')->whereHas('asset.assetType', fn (Builder $q) => $q->where('slug', 'apar'))
            ->whereIn('gate_status', ['jauh', 'perlu_review', 'gps_lemah', 'tanpa_gps'])->whereNull('ditinjau_pada');
    }

    public function belum(): Builder
    {
        return $this->aset()->whereDoesntHave('inspections', fn (Builder $q) => $q->where('status', 'final')->whereBetween('inspected_at', [now()->startOfMonth(), now()->endOfMonth()]));
    }

    public function antrean(string $tab): Builder
    {
        return match ($tab) {
            'lewat' => $this->temuan()->with('asset')->whereDate('target_selesai', '<', today())->orderBy('target_selesai')->orderBy('id'),
            'posisi' => $this->tinjauan()->with(['asset', 'user'])->orderBy('inspected_at')->orderBy('id'),
            'belum' => $this->belum()->orderBy('kode'),
            default => $this->temuan()->with('asset')->where('severity', 'berat')->oldest()->orderBy('id'),
        };
    }

    public function data(): array
    {
        $aset = $this->aset()->with(['inspections' => fn (HasMany $q) => $q->where('status', 'final')
            ->whereBetween('inspected_at', [now()->startOfMonth(), now()->endOfMonth()])->reorder()->orderByDesc('inspected_at')->orderByDesc('id')->limit(1)])
            ->orderBy('kode')->get();
        $total = $aset->count();
        $diperiksa = $aset->filter(fn (Asset $a) => $a->inspections->isNotEmpty())->count();
        $kondisi = collect(Inspection::KESIMPULAN)->map(fn (string $label, string $key) => $aset->filter(fn (Asset $a) => $a->inspections->first()?->kesimpulan === $key)->count());
        $tren = collect(range(5, 0))->map(function (int $offset): array {
            $bulan = now()->startOfMonth()->subMonths($offset);

            return ['bulan' => $bulan->translatedFormat('M'), 'jumlah' => $this->inspeksi()->whereBetween('inspected_at', [$bulan, $bulan->copy()->endOfMonth()])->distinct()->count('asset_id')];
        });

        return [
            'total' => $total,
            'diperiksa' => $diperiksa,
            'persen' => $total ? round($diperiksa / $total * 100, 1) : 0,
            'jumlah' => ['berat' => $this->antrean('berat')->count(), 'lewat' => $this->antrean('lewat')->count(), 'posisi' => $this->tinjauan()->count(), 'belum' => $total - $diperiksa],
            'area' => $aset->groupBy(fn (Asset $a) => implode(' · ', array_filter([$a->gedung ?: 'Area belum diisi', $a->labelLantai()]))),
            'kondisi' => $kondisi,
            'tren' => $tren,
            'hari' => (int) ceil(now()->diffInDays(now()->endOfMonth())),
        ];
    }
}

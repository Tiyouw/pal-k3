<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Inspection;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
            ->whereIn('gate_status', Asset::statusPerluTinjauan())->whereNull('ditinjau_pada');
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
            'area' => $aset->groupBy(fn (Asset $a) => $this->namaArea($a)),
            'kondisi' => $kondisi,
            'tren' => $tren,
            'hari' => (int) ceil(now()->diffInDays(now()->endOfMonth())),
            'peta' => $this->peta($aset),
        ];
    }

    /** Nama area satu baris ("Gedung PIP · Lt. 1"), kunci pengelompokan kartu area dan chip zona peta. */
    public function namaArea(Asset $a): string
    {
        return implode(' · ', array_filter([$a->gedung ?: 'Area belum diisi', $a->labelLantai()]));
    }

    /**
     * Zona peta: "Semua" ditambah satu zona per area. Proyeksi dihitung di sini (bukan di peramban)
     * supaya SVG-nya ikut dirender peladen, bisa diuji, dan tidak ada koordinat mentah yang perlu
     * diolah klien. Yang keluar hanya kode, label, titik, dan status; qr_token tidak pernah ikut.
     *
     * Aset tanpa lat/lng tidak digambar, tapi dihitung di 'tanpa_titik' supaya tidak hilang diam-diam.
     *
     * @param  Collection<int, Asset>  $aset  aset aktif dengan inspeksi final terakhir bulan ini
     * @return array{zona: array<string, array<string, mixed>>, tanpa_titik: int}
     */
    public function peta(Collection $aset, int $lebar = 460, int $tinggi = 320): array
    {
        $perluTinjau = $this->tinjauan()->pluck('asset_id')->flip();

        $titik = $aset->filter(fn (Asset $a) => $a->lat !== null && $a->lng !== null)->map(function (Asset $a) use ($perluTinjau): array {
            $inspeksi = $a->inspections->first();

            return [
                'kode' => $a->kode,
                'area' => $this->namaArea($a),
                'gedung' => $a->gedung ?: 'Area belum diisi',
                'lantai' => $a->labelLantai(),
                'lokasi' => $a->labelLokasi(),
                'lat' => $a->lat,
                'lng' => $a->lng,
                'status' => $inspeksi ? ($inspeksi->kesimpulan ?: 'tanpa_kesimpulan') : 'belum',
                'tinjau' => $perluTinjau->has($a->id),
            ];
        })->values();

        $zona = ['Semua' => $this->zona('Semua', $titik, true, $lebar, $tinggi)];

        foreach ($titik->groupBy('area') as $nama => $isi) {
            $zona[$nama] = $this->zona($nama, $isi->values(), false, $lebar, $tinggi);
        }

        return ['zona' => $zona, 'tanpa_titik' => $aset->count() - $titik->count()];
    }

    /** @return array<string, mixed> */
    private function zona(string $nama, Collection $titik, bool $geserLantai, int $lebar, int $tinggi): array
    {
        $tepiX = 44;
        $tepiY = 40;
        $lat = $titik->pluck('lat');
        $lng = $titik->pluck('lng');
        [$lat0, $lat1] = $this->rentang($lat);
        [$lng0, $lng1] = $this->rentang($lng);

        // Lantai di gedung yang sama sering berbagi koordinat; digeser mendatar supaya tidak bertumpuk.
        $geser = [];
        if ($geserLantai) {
            foreach ($titik->groupBy('gedung') as $gedung => $isi) {
                $lantai = $isi->pluck('lantai')->unique()->sort()->values();
                if ($lantai->count() > 1) {
                    foreach ($lantai as $i => $l) {
                        $geser[$gedung][(string) $l] = ($i - ($lantai->count() - 1) / 2) * 16;
                    }
                }
            }
        }

        $titik = $titik->map(function (array $t) use ($tepiX, $tepiY, $lebar, $tinggi, $lat1, $lat0, $lng0, $lng1, $geser): array {
            $t['x'] = round($tepiX + ($t['lng'] - $lng0) / ($lng1 - $lng0) * ($lebar - 2 * $tepiX) + ($geser[$t['gedung']][(string) $t['lantai']] ?? 0), 1);
            $t['y'] = round($tepiY + ($lat1 - $t['lat']) / ($lat1 - $lat0) * ($tinggi - 2 * $tepiY), 1);

            return $t;
        });

        // Label gedung hanya di zona "Semua", dan hanya kalau titiknya mengelompok (label di atas sebaran luas menyesatkan).
        $label = [];
        if ($nama === 'Semua') {
            foreach ($titik->groupBy('gedung') as $gedung => $isi) {
                $xs = $isi->pluck('x');
                $ys = $isi->pluck('y');
                if ($xs->max() - $xs->min() > $lebar * .45 || $ys->max() - $ys->min() > $tinggi * .45) {
                    continue;
                }
                $label[] = ['teks' => $gedung, 'x' => round(min(max($xs->avg(), 70), $lebar - 70), 1), 'y' => round(max($ys->min() - 17, 14), 1)];
            }
        }

        return [
            'nama' => $nama,
            'titik' => $titik->all(),
            'label' => $label,
            'total' => $titik->count(),
            'diperiksa' => $titik->where('status', '!=', 'belum')->count(),
            'lebar' => $lebar,
            'tinggi' => $tinggi,
        ];
    }

    /** @return array{0: float, 1: float} */
    private function rentang(Collection $nilai): array
    {
        $min = (float) $nilai->min();
        $max = (float) $nilai->max();

        // Satu titik (atau semua sama) tidak boleh membagi dengan nol.
        return $max - $min < 1e-5 ? [$min - 5e-5, $max + 5e-5] : [$min, $max];
    }

    /** Temuan terbuka paling mendesak untuk ringkasan pemantau: berat dulu, lalu yang tenggatnya paling dekat. */
    public function risiko(int $batas = 5): Collection
    {
        return $this->temuan()->with('asset')
            ->orderByRaw("case severity when 'berat' then 0 else 1 end")
            ->orderByRaw('target_selesai is null')->orderBy('target_selesai')->orderBy('id')
            ->limit($batas)->get();
    }

    /** Kalimat ringkasan untuk manajemen, dihitung dari angka yang sama dengan kartu di dasbor. */
    public function narasi(array $data): string
    {
        if ($data['total'] === 0) {
            return 'Belum ada APAR aktif. Ringkasan akan terisi setelah aset dan inspeksi tersedia.';
        }

        if ($data['diperiksa'] === 0) {
            return "Belum ada inspeksi bulan ini. {$data['total']} APAR aktif menunggu pemeriksaan sebelum akhir bulan (sisa {$data['hari']} hari).";
        }

        $persen = number_format($data['persen'], 1, ',', '.');
        $tidakLayak = $data['kondisi']['tidak_layak'] ?? 0;

        return "{$data['diperiksa']} dari {$data['total']} APAR sudah diperiksa bulan ini ({$persen}%). {$tidakLayak} unit dinyatakan tidak layak pakai. "
            ."Ada {$data['jumlah']['berat']} temuan berat dan {$data['jumlah']['lewat']} temuan lewat tenggat yang belum selesai, "
            ."serta {$data['jumlah']['posisi']} inspeksi yang menunggu tinjauan posisi.";
    }
}

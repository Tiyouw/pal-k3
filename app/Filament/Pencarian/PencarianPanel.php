<?php

namespace App\Filament\Pencarian;

use App\Filament\Resources\AssetResource;
use App\Filament\Resources\InspectionResource;
use App\Filament\Resources\IssueResource;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\Issue;
use Filament\Facades\Filament;
use Filament\GlobalSearch\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pencarian "apa saja" di bilah atas panel: halaman, tindakan, aset, temuan, dan inspeksi.
 *
 * Aturan yang tidak boleh dilonggarkan:
 * - qr_token TIDAK PERNAH dipilih, dicari, atau ditampilkan. Kode QR adalah satu-satunya penentu
 *   keaslian pemeriksaan; hasil pencarian tidak boleh menjadi jalan pintas membacanya.
 * - Data Induk (aset) dan tindakan tulis hanya untuk admin, sama seperti menu di sidebar.
 *   Pemantau tetap bisa mencari halaman yang boleh ia lihat, temuan, dan inspeksi.
 * - Wildcard LIKE (% dan _) yang diketik pengguna diperlakukan sebagai huruf biasa.
 * - Pencarian hanya membaca; tidak ada yang ditulis ke basis data.
 */
class PencarianPanel implements GlobalSearchProvider
{
    /** Kata pendek seperti "1" cocok dengan hampir semua baris; paling sedikit dua huruf. */
    public const MIN_HURUF = 2;

    public const BATAS_PER_KELOMPOK = 5;

    /** Karakter escape LIKE. Backslash sengaja dihindari: SQLite dan MySQL menafsirkannya berbeda. */
    private const ESCAPE = '!';

    public function getResults(string $query): ?GlobalSearchResults
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_HURUF) {
            return null;
        }

        $like = '%'.self::escapeLike($query).'%';
        $admin = auth()->user()?->isAdmin() ?? false;

        $hasil = GlobalSearchResults::make();

        foreach ([
            'Halaman' => fn () => $this->halaman($query),
            'Tindakan' => fn () => $admin ? $this->tindakan($query) : collect(),
            'Aset' => fn () => $admin ? $this->aset($like) : collect(),
            'Temuan' => fn () => $this->temuan($like, $admin),
            'Inspeksi' => fn () => $this->inspeksi($like),
        ] as $nama => $ambil) {
            $isi = $ambil();

            if ($isi->isNotEmpty()) {
                $hasil->category($nama, $isi->all());
            }
        }

        return $hasil;
    }

    /** Escape %, _, dan karakter escape itu sendiri agar diketik apa adanya. */
    public static function escapeLike(string $teks): string
    {
        return str_replace([self::ESCAPE, '%', '_'], [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'], $teks);
    }

    private function cocok(string $teks, string $query): bool
    {
        return mb_stripos($teks, $query) !== false;
    }

    /** @return Collection<int, GlobalSearchResult> */
    private function halaman(string $query): Collection
    {
        $hasil = collect();

        foreach (Filament::getNavigation() as $grup) {
            /** @var NavigationGroup $grup */
            foreach ($grup->getItems() as $item) {
                /** @var NavigationItem $item */
                if (! $this->cocok($item->getLabel(), $query) || blank($item->getUrl())) {
                    continue;
                }

                $hasil->push(new GlobalSearchResult(
                    title: $item->getLabel(),
                    url: $item->getUrl(),
                    details: ['Menu' => $grup->getLabel() ?: 'Beranda'],
                ));
            }
        }

        return $hasil->take(self::BATAS_PER_KELOMPOK)->values();
    }

    /** @return Collection<int, GlobalSearchResult> */
    private function tindakan(string $query): Collection
    {
        return collect([
            ['Tambah aset', 'Data Induk', AssetResource::getUrl('create')],
            ['Cetak lembar stiker QR', 'Kegiatan', route('stiker.index')],
        ])->filter(fn (array $a) => $this->cocok($a[0], $query))
            ->map(fn (array $a) => new GlobalSearchResult(title: $a[0], url: $a[2], details: ['Menu' => $a[1]]))
            ->values();
    }

    /** @return Collection<int, GlobalSearchResult> */
    private function aset(string $like): Collection
    {
        // qr_token tidak ikut dipilih. Hanya kolom yang dibutuhkan untuk judul dan rincian.
        return Asset::query()
            ->select(['id', 'kode', 'gedung', 'lantai', 'lokasi_teks', 'aktif'])
            ->where(function (Builder $q) use ($like) {
                foreach (['kode', 'gedung', 'lantai', 'lokasi_teks'] as $kolom) {
                    $q->orWhereRaw("{$kolom} like ? escape '".self::ESCAPE."'", [$like]);
                }
            })
            ->orderBy('kode')->limit(self::BATAS_PER_KELOMPOK)->get()
            ->map(fn (Asset $a) => new GlobalSearchResult(
                title: $a->kode.' · '.$a->labelLokasi(),
                url: AssetResource::getUrl('edit', ['record' => $a]),
                details: array_filter(['Lokasi' => $a->lokasi_teks, 'Status' => $a->aktif ? null : 'Nonaktif']),
            ));
    }

    /** @return Collection<int, GlobalSearchResult> */
    private function temuan(string $like, bool $admin): Collection
    {
        return Issue::query()
            ->with('asset:id,kode,gedung,lantai')
            ->where(function (Builder $q) use ($like) {
                $q->whereRaw("item like ? escape '".self::ESCAPE."'", [$like])
                    ->orWhereHas('asset', fn (Builder $a) => $a->whereRaw("kode like ? escape '".self::ESCAPE."'", [$like]));
            })
            ->orderByRaw("case status when 'selesai' then 1 else 0 end")->orderByDesc('id')
            ->limit(self::BATAS_PER_KELOMPOK)->get()
            ->map(fn (Issue $i) => new GlobalSearchResult(
                title: $i->item,
                // Pemantau hanya baca: halaman ubah ditolak untuknya, jadi diarahkan ke daftar.
                url: $admin ? IssueResource::getUrl('edit', ['record' => $i]) : IssueResource::getUrl('index'),
                details: array_filter(['Unit' => $i->asset?->kode, 'Tingkat' => ucfirst((string) $i->severity), 'Status' => ucfirst((string) $i->status)]),
            ));
    }

    /** @return Collection<int, GlobalSearchResult> */
    private function inspeksi(string $like): Collection
    {
        return Inspection::query()
            ->with(['asset:id,kode,gedung,lantai', 'user:id,name'])
            ->where(function (Builder $q) use ($like) {
                $q->whereHas('asset', fn (Builder $a) => $a->whereRaw("kode like ? escape '".self::ESCAPE."'", [$like]))
                    ->orWhereHas('user', fn (Builder $u) => $u->whereRaw("name like ? escape '".self::ESCAPE."'", [$like]));
            })
            ->orderByRaw('inspected_at is null')->orderByDesc('inspected_at')->orderByDesc('id')
            ->limit(self::BATAS_PER_KELOMPOK)->get()
            ->map(fn (Inspection $i) => new GlobalSearchResult(
                title: 'Inspeksi '.($i->asset?->kode ?? '-'),
                url: InspectionResource::getUrl('view', ['record' => $i]),
                details: array_filter([
                    'Petugas' => $i->user?->name,
                    'Waktu' => $i->inspected_at?->translatedFormat('d M Y, H.i') ?? 'Draf',
                    'Kesimpulan' => $i->kesimpulan ? Inspection::KESIMPULAN[$i->kesimpulan] ?? null : null,
                ]),
            ));
    }
}

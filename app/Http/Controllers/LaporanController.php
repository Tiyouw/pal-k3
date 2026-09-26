<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\ChecklistItem;
use App\Models\Inspection;
use App\Models\Issue;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Bagian 9: laporan bulanan format PMS.
 *
 * Tiga keluaran, satu sumber data:
 *   1. Lembar PMS   - matriks aset x butir periksa dengan empat simbol.
 *   2. Kartu kontrol - riwayat satu aset sepanjang tahun, untuk digantung di tabung.
 *   3. Rekap temuan  - daftar temuan terbuka diurutkan dari yang terberat.
 *
 * Simbol mengikuti lampiran laporan yang sudah dipakai Divisi K3LH, dan dua
 * dimensinya dijaga tetap terpisah: tanda centang dan tanda silang berbicara
 * soal fungsi, huruf O dan O bergaris berbicara soal kondisi. Menggabungkan
 * keduanya menjadi satu kolom "baik/tidak" akan menghapus perbedaan antara
 * alat yang masih menyemburkan isi walau badannya berkarat dengan alat yang
 * mulus tetapi tidak menyembur.
 */
class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->saring($request);

        return view('laporan.indeks', $this->rakit($data) + [
            'pilihanTipe'  => AssetType::orderBy('urut')->get(),
            'pilihanTahun' => $this->tahunTersedia(),
        ]);
    }

    /** Lembar PMS sebagai PDF, siap ditandatangani tiga kolom. */
    public function pms(Request $request): Response
    {
        $data = $this->saring($request);
        $isi  = $this->rakit($data);

        // Lanskap: matriks selebar sepuluh butir periksa tidak masuk di potret.
        $pdf = Pdf::loadView('laporan.pms', $isi)
            ->setPaper('a4', 'landscape');

        $nama = sprintf('PMS-%s-%s-%02d.pdf', strtoupper($data['tipe']), $data['tahun'], $data['bulan']);

        return $pdf->stream($nama);
    }

    /** Rekapitulasi temuan terbuka. */
    public function temuan(Request $request): Response
    {
        $data = $this->saring($request);
        $isi  = $this->rakit($data);

        $pdf = Pdf::loadView('laporan.temuan', $isi)->setPaper('a4', 'portrait');

        return $pdf->stream(sprintf('Rekap-Temuan-%s-%02d.pdf', $data['tahun'], $data['bulan']));
    }

    /**
     * Kartu kontrol satu aset: dua belas kolom bulan pada satu tahun.
     *
     * Kartu ini yang digantung di tabung dan menjadi rujukan pertama pengawas
     * saat berkeliling, jadi isinya disengaja ringkas: tanggal, petugas, dan
     * kesimpulan. Rincian butir periksa ada di lembar PMS.
     */
    public function kartu(Request $request, Asset $asset): Response
    {
        $tahun = (int) ($request->integer('tahun') ?: now()->year);

        $inspeksi = Inspection::with('user')
            ->where('asset_id', $asset->id)
            ->where('status', 'final')
            ->whereYear('inspected_at', $tahun)
            ->orderBy('inspected_at')
            ->get()
            ->groupBy(fn (Inspection $i) => (int) $i->inspected_at->month);

        $pdf = Pdf::loadView('laporan.kartu', [
            'asset'    => $asset->load('assetType', 'division'),
            'tahun'    => $tahun,
            'perBulan' => $inspeksi,
            'namaBulan' => $this->namaBulan(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Kartu-Kontrol-{$asset->kode}-{$tahun}.pdf");
    }

    /* ----------------------------------------------------------------- */

    /** @return array{tipe:string,tahun:int,bulan:int,gedung:?string} */
    private function saring(Request $request): array
    {
        $v = $request->validate([
            'tipe'   => ['nullable', 'string'],
            'tahun'  => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'bulan'  => ['nullable', 'integer', 'min:1', 'max:12'],
            'gedung' => ['nullable', 'string'],
        ]);

        return [
            'tipe'   => $v['tipe']  ?? 'apar',
            'tahun'  => (int) ($v['tahun'] ?? now()->year),
            'bulan'  => (int) ($v['bulan'] ?? now()->month),
            'gedung' => $v['gedung'] ?? null,
        ];
    }

    /**
     * Susun seluruh bahan laporan satu periode.
     *
     * Aset diambil dari keadaan sekarang, bukan dari daftar inspeksi. Akibatnya
     * aset yang TIDAK diinspeksi tetap muncul sebagai baris kosong. Itu memang
     * yang dicari: baris kosong adalah temuan kepatuhan, dan laporan yang hanya
     * memuat aset yang sudah dikerjakan selalu tampak seratus persen patuh.
     */
    private function rakit(array $data): array
    {
        $tipe = AssetType::where('slug', $data['tipe'])->firstOrFail();

        $awal  = Carbon::create($data['tahun'], $data['bulan'], 1)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $aset = Asset::query()
            ->with('division')
            ->where('asset_type_id', $tipe->id)
            ->where('aktif', true)
            ->when($data['gedung'], fn ($q, $g) => $q->where('gedung', $g))
            ->orderBy('gedung')->orderBy('lantai')->orderBy('kode')
            ->get();

        $butir = ChecklistItem::query()
            ->whereHas('group', fn ($q) => $q->where('asset_type_id', $tipe->id))
            ->with('group')
            ->where('aktif', true)
            ->get()
            ->sortBy([fn ($a, $b) => $a->group->urut <=> $b->group->urut, fn ($a, $b) => $a->urut <=> $b->urut])
            ->values();

        // Satu inspeksi final per aset per periode: yang terakhir dipakai bila
        // ada pemeriksaan ulang dalam bulan yang sama.
        $inspeksi = Inspection::query()
            ->with(['user', 'answers'])
            ->whereIn('asset_id', $aset->pluck('id'))
            ->where('status', 'final')
            ->whereBetween('inspected_at', [$awal, $akhir])
            ->orderBy('inspected_at')
            ->get()
            ->keyBy('asset_id');

        $temuan = Issue::query()
            ->with(['asset', 'inspection'])
            ->whereIn('asset_id', $aset->pluck('id'))
            ->where('status', '!=', 'selesai')
            ->get()
            ->sortBy([
                fn ($a, $b) => (['berat' => 0, 'sedang' => 1, 'ringan' => 2][$a->severity] ?? 3)
                    <=> (['berat' => 0, 'sedang' => 1, 'ringan' => 2][$b->severity] ?? 3),
                fn ($a, $b) => $a->created_at <=> $b->created_at,
            ])
            ->values();

        $diperiksa = $inspeksi->count();
        $total     = $aset->count();

        return [
            'tipe'     => $tipe,
            'aset'     => $aset,
            'butir'    => $butir,
            'grup'     => $butir->groupBy(fn ($i) => $i->group->nama),
            'inspeksi' => $inspeksi,
            'temuan'   => $temuan,
            'periode'  => $awal,
            'filter'   => $data,
            'gedung'   => Asset::whereNotNull('gedung')->distinct()->orderBy('gedung')->pluck('gedung'),
            'ringkas'  => [
                'total'      => $total,
                'diperiksa'  => $diperiksa,
                'belum'      => max($total - $diperiksa, 0),
                'kepatuhan'  => $total > 0 ? round($diperiksa / $total * 100, 1) : 0.0,
                'layak'         => $inspeksi->where('kesimpulan', 'layak')->count(),
                'layakCatatan'  => $inspeksi->where('kesimpulan', 'layak_catatan')->count(),
                'tidakLayak'    => $inspeksi->where('kesimpulan', 'tidak_layak')->count(),
                'beratTerbuka'  => $temuan->where('severity', 'berat')->count(),
                'perluTinjauan' => $inspeksi->whereIn('gate_status', ['jauh', 'perlu_review', 'gps_lemah'])->count(),
            ],
            'namaBulan' => $this->namaBulan(),
        ];
    }

    private function tahunTersedia(): Collection
    {
        $tahun = Inspection::query()
            ->where('status', 'final')
            ->selectRaw("CAST(strftime('%Y', inspected_at) AS INTEGER) AS th")
            ->distinct()
            ->pluck('th');

        return $tahun->push(now()->year)->unique()->sortDesc()->values();
    }

    private function namaBulan(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }
}

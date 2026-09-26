<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\Schedule;
use App\Services\IssueGenerator;
use App\Services\PhotoStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Alur lapangan Bagian 7.1, tahap 3 sampai 10.
 *
 * Prinsip yang dipegang sepanjang berkas ini:
 *
 * 1. QR adalah gerbang penentu aset. GPS tidak punya sumbu tegak, sedangkan tiga
 *    lantai Gedung PIP bertumpuk pada satu koordinat, jadi aset TIDAK PERNAH
 *    ditentukan dari posisi.
 * 2. GPS adalah bukti, bukan pemblokir. Semua keadaan gerbang tetap diterima,
 *    yang berbeda hanya penandaan dan apakah masuk antrean tinjauan.
 * 3. Waktu yang dipercaya adalah waktu peladen. Waktu perangkat ikut disimpan
 *    hanya sebagai pembanding, supaya jam ponsel yang diputar balik terlihat.
 */
class InspeksiController extends Controller
{
    public function __construct(
        private readonly IssueGenerator $issues,
        private readonly PhotoStore $photos,
    ) {}

    /** Tahap 3: halaman pemindai kamera. */
    public function pemindai(): View
    {
        return view('petugas.pindai');
    }

    /**
     * Tahap 4 sampai 6: token QR ditukar menjadi inspeksi draf.
     *
     * Rute ini juga yang terbuka kalau stiker dipindai memakai aplikasi kamera
     * bawaan ponsel, karena isi QR adalah URL penuh ke alamat ini. Petugas yang
     * belum masuk akan diarahkan ke halaman masuk lalu kembali ke sini sendiri.
     */
    public function mulai(Request $request, string $token): RedirectResponse|View
    {
        $asset = Asset::with(['assetType', 'division'])
            ->where('qr_token', $token)
            ->first();

        // Stiker rusak, token dicabut, atau aset dihapus.
        if (! $asset) {
            return view('petugas.gagal', [
                'judul' => 'Stiker tidak dikenali',
                'pesan' => 'Kode pada stiker ini tidak terdaftar. Kemungkinan stiker sudah diganti '
                    . 'atau aset dinonaktifkan. Laporkan ke Admin K3 dengan menyebut lokasi tabung.',
            ]);
        }

        if (! $asset->aktif) {
            return view('petugas.gagal', [
                'judul' => 'Aset tidak aktif',
                'pesan' => "Aset {$asset->kode} sudah dinonaktifkan dan tidak perlu diinspeksi.",
                'asset' => $asset,
            ]);
        }

        /**
         * Draf yang sudah ada dipakai ulang, tidak dibuat baru.
         *
         * Tanpa ini, petugas yang aplikasinya tertutup di tengah pengisian lalu
         * memindai ulang stiker yang sama akan meninggalkan draf yatim berisi
         * separuh jawaban, dan jawaban yang sudah dia isi hilang dari layar.
         */
        $inspeksi = Inspection::firstOrCreate(
            [
                'asset_id' => $asset->id,
                'user_id'  => Auth::id(),
                'status'   => 'draf',
            ],
            [
                'mulai_pada'  => now(),
                'qr_verified' => true,
                'ua'          => substr((string) $request->userAgent(), 0, 255),
            ],
        );

        return redirect()->route('petugas.inspeksi.isi', $inspeksi);
    }

    /** Tahap 6 dan 7: formulir checklist. */
    public function isi(Inspection $inspeksi): View|RedirectResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        if ($inspeksi->status === 'final') {
            return redirect()->route('petugas.inspeksi.selesai', $inspeksi);
        }

        $inspeksi->load(['asset.assetType', 'asset.division', 'answers', 'photos']);

        $groups = $inspeksi->asset->assetType
            ->checklistGroups()
            ->with(['items' => fn ($q) => $q->where('aktif', true)->orderBy('urut')])
            ->orderBy('urut')
            ->get();

        return view('petugas.isi', [
            'inspeksi'  => $inspeksi,
            'asset'     => $inspeksi->asset,
            'groups'    => $groups,
            'jawaban'   => $inspeksi->answers->keyBy('checklist_item_id'),
            'usulan'    => $this->issues->usulKesimpulan($inspeksi),
        ]);
    }

    /**
     * Tahap 5: koordinat dikirim peramban secara asinkron setelah izin lokasi
     * diberikan, lalu gerbang dinilai di peladen.
     *
     * Dipisah dari pengiriman akhir dengan sengaja: pengambilan posisi di dalam
     * gedung beton bisa memakan sepuluh detik atau lebih, dan petugas tidak perlu
     * menunggu itu selesai sebelum mulai mengisi checklist.
     */
    public function lokasi(Request $request, Inspection $inspeksi): JsonResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        $data = $request->validate([
            'lat'      => ['required', 'numeric', 'between:-90,90'],
            'lng'      => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $asset = $inspeksi->asset;

        $gerbang = $asset->evaluasiGerbang(
            (float) $data['lat'],
            (float) $data['lng'],
            isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
        );

        $inspeksi->update([
            'gps_lat'      => $data['lat'],
            'gps_lng'      => $data['lng'],
            'gps_accuracy' => isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
            'jarak_m'      => $gerbang['jarak_m'],
            'gate_status'  => $gerbang['status'],
        ]);

        return response()->json([
            'status'  => $gerbang['status'],
            'label'   => $inspeksi->fresh()->labelGerbang(),
            'jarak_m' => $gerbang['jarak_m'],
            'radius_m'=> $gerbang['radius_m'],
            'pesan'   => $this->pesanGerbang($gerbang['status'], $gerbang['jarak_m'], $gerbang['radius_m']),
        ]);
    }

    /**
     * Penyimpanan otomatis satu jawaban.
     *
     * Bagian 7.1 tahap 6: sinyal di dalam gedung galangan sering hilang. Jawaban
     * disimpan per item begitu ditekan, bukan menunggu tombol kirim, supaya
     * kehilangan sinyal di item kesembilan tidak menghapus delapan yang pertama.
     */
    public function simpanJawaban(Request $request, Inspection $inspeksi): JsonResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        if ($inspeksi->status === 'final') {
            return response()->json(['pesan' => 'Inspeksi sudah dikirim.'], 422);
        }

        $idItemSah = $this->idItemSah($inspeksi);

        $data = $request->validate([
            'checklist_item_id' => ['required', 'integer', Rule::in($idItemSah)],
            'nilai'             => ['nullable', 'string', 'max:120'],
            'nilai_angka'       => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'catatan'           => ['nullable', 'string', 'max:500'],
        ]);

        $jawaban = $inspeksi->answers()->updateOrCreate(
            ['checklist_item_id' => $data['checklist_item_id']],
            [
                'nilai'       => $data['nilai'] ?? null,
                'nilai_angka' => $data['nilai_angka'] ?? null,
                'catatan'     => $data['catatan'] ?? null,
            ],
        );

        $jawaban->load('item');
        $inspeksi->load('answers.item');

        return response()->json([
            'tersimpan' => true,
            'temuan'    => $jawaban->isTemuan(),
            'severity'  => $jawaban->severity(),
            'terisi'    => $inspeksi->answers->filter(fn ($a) => $a->nilai !== null || $a->nilai_angka !== null)->count(),
            'total'     => count($idItemSah),
            'usulan'    => $this->issues->usulKesimpulan($inspeksi),
        ]);
    }

    /** Tahap 7: unggah foto. */
    public function unggahFoto(Request $request, Inspection $inspeksi): JsonResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        if ($inspeksi->status === 'final') {
            return response()->json(['pesan' => 'Inspeksi sudah dikirim.'], 422);
        }

        $request->validate([
            // 12 MB menampung keluaran kamera ponsel kelas menengah tanpa
            // menolak berkas yang sah; pengecilan terjadi setelah ini.
            'foto' => ['required', 'file', 'image', 'max:12288'],
        ], [], ['foto' => 'foto']);

        try {
            $foto = $this->photos->simpan($inspeksi, $request->file('foto'));
        } catch (\RuntimeException $e) {
            return response()->json(['pesan' => $e->getMessage()], 422);
        }

        return response()->json([
            'id'     => $foto->id,
            'url'    => $foto->url(),
            'jumlah' => $inspeksi->photos()->count(),
        ]);
    }

    public function hapusFoto(Inspection $inspeksi, int $foto): JsonResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        $berkas = $inspeksi->photos()->findOrFail($foto);
        $this->photos->hapus($berkas);

        return response()->json(['jumlah' => $inspeksi->photos()->count()]);
    }

    /**
     * Tahap 8 dan 9: finalisasi.
     *
     * Semua penulisan dibungkus satu transaksi. Kalau pembangkitan temuan gagal
     * di tengah jalan, inspeksi tidak boleh tertinggal berstatus final tanpa
     * baris temuan, karena laporan bulanan akan mencetak tabung rusak sebagai
     * aman.
     */
    public function kirim(Request $request, Inspection $inspeksi): RedirectResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        if ($inspeksi->status === 'final') {
            return redirect()->route('petugas.inspeksi.selesai', $inspeksi);
        }

        $inspeksi->load(['answers.item', 'asset.assetType']);

        $data = $request->validate([
            'kesimpulan'  => ['required', Rule::in(array_keys(Inspection::KESIMPULAN))],
            'catatan'     => ['nullable', 'string', 'max:1000'],
            'rekomendasi' => ['nullable', 'string', 'max:1000'],
            'device_time' => ['nullable', 'date'],
        ], [], [
            'kesimpulan'  => 'kesimpulan',
            'rekomendasi' => 'rekomendasi',
        ]);

        // Bagian 6.1: seluruh item wajib harus terisi sebelum boleh dikirim.
        $belum = $this->itemWajibBelumTerisi($inspeksi);

        if ($belum->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['kesimpulan' => 'Masih ada pemeriksaan yang belum diisi: ' . $belum->implode(', ')]);
        }

        // Bagian 3.2: foto wajib, minimal satu. Tanpa foto, laporan inspeksi
        // tidak punya bukti apa pun bahwa tabung benar-benar dilihat.
        if ($inspeksi->photos()->count() === 0) {
            return back()
                ->withInput()
                ->withErrors(['kesimpulan' => 'Foto wajib diunggah minimal satu sebelum inspeksi dikirim.']);
        }

        // Bagian 6.1: temuan berat tidak boleh disimpulkan layak pakai.
        $usulan = $this->issues->usulKesimpulan($inspeksi);

        if ($usulan === 'tidak_layak' && $data['kesimpulan'] === 'layak') {
            return back()
                ->withInput()
                ->withErrors(['kesimpulan' => 'Terdapat temuan berat, kesimpulan tidak dapat diisi Layak pakai.']);
        }

        // Bagian 6.1: kalau ada temuan, rekomendasi wajib ditulis.
        if ($usulan !== 'layak' && blank($data['rekomendasi'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['rekomendasi' => 'Ada temuan pada inspeksi ini, rekomendasi wajib diisi.']);
        }

        DB::transaction(function () use ($inspeksi, $data) {
            // Bagian 5.7: waktu peladen yang dipercaya, bukan waktu perangkat.
            $sekarang = now();

            $inspeksi->update([
                'status'       => 'final',
                'inspected_at' => $sekarang,
                'durasi_detik' => $inspeksi->mulai_pada
                    ? max($inspeksi->mulai_pada->diffInSeconds($sekarang), 0)
                    : null,
                'device_time'  => $data['device_time'] ?? null,
                'kesimpulan'   => $data['kesimpulan'],
                'catatan'      => $data['catatan'] ?? null,
                'rekomendasi'  => $data['rekomendasi'] ?? null,
            ]);

            $this->issues->bangun($inspeksi);

            $inspeksi->asset->update(['terakhir_dicek' => $sekarang->toDateString()]);

            Schedule::pastikanAda($inspeksi->asset, $sekarang)->tandaiSelesai($inspeksi);
        });

        return redirect()->route('petugas.inspeksi.selesai', $inspeksi);
    }

    /** Tahap 10: ringkasan hasil, dengan tombol lanjut ke aset berikutnya. */
    public function selesai(Inspection $inspeksi): View
    {
        $this->pastikanMilikSendiri($inspeksi);

        abort_unless($inspeksi->status === 'final', 404);

        $inspeksi->load(['asset', 'answers.item.group', 'photos', 'issues']);

        return view('petugas.selesai', [
            'inspeksi' => $inspeksi,
            'asset'    => $inspeksi->asset,
            'temuan'   => $inspeksi->issues->sortBy(fn ($i) => ['berat' => 0, 'sedang' => 1, 'ringan' => 2][$i->severity] ?? 3),
        ]);
    }

    /** Buang draf yang tidak ingin dilanjutkan, beserta foto yang sudah terunggah. */
    public function batal(Inspection $inspeksi): RedirectResponse
    {
        $this->pastikanMilikSendiri($inspeksi);

        abort_if($inspeksi->status === 'final', 403, 'Inspeksi yang sudah dikirim tidak dapat dibatalkan.');

        DB::transaction(function () use ($inspeksi) {
            foreach ($inspeksi->photos as $foto) {
                $this->photos->hapus($foto);
            }

            $inspeksi->answers()->delete();
            $inspeksi->delete();
        });

        return redirect()->route('petugas.beranda')->with('pesan', 'Draf inspeksi dibatalkan.');
    }

    /** Label item wajib yang belum punya jawaban. */
    private function itemWajibBelumTerisi(Inspection $inspeksi): \Illuminate\Support\Collection
    {
        $terisi = $inspeksi->answers
            ->filter(fn ($a) => $a->nilai !== null || $a->nilai_angka !== null)
            ->pluck('checklist_item_id')
            ->all();

        return $inspeksi->asset->assetType
            ->checklistGroups()
            ->with(['items' => fn ($q) => $q->where('aktif', true)->where('wajib', true)])
            ->get()
            ->flatMap->items
            ->reject(fn ($item) => in_array($item->id, $terisi, true))
            ->pluck('label')
            ->values();
    }

    private function pesanGerbang(string $status, ?int $jarak, ?int $radius): string
    {
        return match ($status) {
            'sesuai'        => 'Lokasi sesuai.',
            'perlu_review'  => "Anda berjarak {$jarak} m dari titik aset (batas {$radius} m). Inspeksi tetap dapat dikirim dan akan ditinjau Admin K3.",
            'jauh'          => "Anda berjarak {$jarak} m dari titik aset, jauh di luar batas {$radius} m. Pastikan stiker yang dipindai benar.",
            'gps_lemah'     => 'Sinyal GPS lemah, posisi tidak dapat dipastikan. Ini tidak dihitung sebagai pelanggaran.',
            'tidak_berlaku' => 'Objek bergerak, pemeriksaan lokasi tidak diberlakukan.',
            default         => 'Lokasi tidak terdeteksi. Inspeksi tetap dapat dikirim.',
        };
    }

    /** Daftar id item checklist yang sah untuk inspeksi ini. */
    private function idItemSah(Inspection $inspeksi): array
    {
        return $inspeksi->asset->assetType
            ->checklistGroups()
            ->with(['items' => fn ($q) => $q->where('aktif', true)])
            ->get()
            ->flatMap->items
            ->pluck('id')
            ->all();
    }

    /**
     * Inspeksi draf hanya boleh disentuh pembuatnya.
     *
     * Tanpa penjagaan ini, id inspeksi pada URL bisa diganti angka lain dan
     * petugas menulis jawaban ke draf milik rekannya.
     */
    private function pastikanMilikSendiri(Inspection $inspeksi): void
    {
        abort_unless($inspeksi->user_id === Auth::id(), 403, 'Inspeksi ini bukan milik Anda.');
    }
}

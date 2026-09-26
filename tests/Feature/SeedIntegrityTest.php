<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\Division;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeedIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterSeeder::class);
        $this->seed(AparSeeder::class);
    }

    #[Test]
    public function empat_tipe_objek_terpasang(): void
    {
        $this->assertSame(4, AssetType::count());

        foreach (['apar', 'hydrant', 'p3k', 'damkar'] as $slug) {
            $this->assertDatabaseHas('asset_types', ['slug' => $slug]);
        }
    }

    /** Mobil pemadam satu-satunya objek bergerak, jadi tidak bisa diverifikasi pakai titik tetap. */
    #[Test]
    public function hanya_mobil_pemadam_yang_bergerak(): void
    {
        $this->assertTrue(AssetType::where('slug', 'damkar')->first()->is_mobile);
        $this->assertSame(3, AssetType::where('is_mobile', false)->count());
    }

    /** Periode APAR 30 hari mengikuti NFPA 10, bukan Permenaker (6/12 bulan). */
    #[Test]
    public function periode_apar_tiga_puluh_hari(): void
    {
        $this->assertSame(30, AssetType::where('slug', 'apar')->first()->periode_hari);
        $this->assertSame(7, AssetType::where('slug', 'damkar')->first()->periode_hari);
    }

    #[Test]
    public function tiga_puluh_lima_apar_masuk_dari_csv(): void
    {
        $apar = AssetType::where('slug', 'apar')->first();

        $this->assertSame(35, Asset::where('asset_type_id', $apar->id)->count());
    }

    #[Test]
    public function setiap_aset_punya_token_unik(): void
    {
        $this->assertSame(
            Asset::count(),
            Asset::distinct('qr_token')->count('qr_token'),
        );
    }

    /** Token tidak boleh memuat nomor aset; kalau bocor, orang bisa menebak aset lain. */
    #[Test]
    public function token_tidak_memuat_nomor_aset(): void
    {
        foreach (Asset::all() as $aset) {
            $this->assertMatchesRegularExpression('/^PAL-K3-[0-9a-f]{32}$/', $aset->qr_token);
            $this->assertStringNotContainsString($aset->kode, substr($aset->qr_token, 7));
        }
    }

    #[Test]
    public function semua_aset_punya_koordinat_dan_radius(): void
    {
        $this->assertSame(0, Asset::whereNull('lat')->orWhereNull('lng')->count());

        /*
         * radius_m sengaja dibiarkan kosong kalau radiusnya sama dengan bawaan
         * jenis lokasi (Bagian 5.5): kolom itu OVERRIDE per aset, bukan salinan
         * nilai bawaan. Kalau diisi semua, perubahan kebijakan radius harus
         * menyentuh 35 baris. Yang wajib ada adalah radius EFEKTIF.
         */
        foreach (Asset::all() as $aset) {
            $this->assertNotNull(
                $aset->radiusEfektif(),
                "Aset {$aset->kode} tidak punya radius efektif.",
            );
            $this->assertGreaterThan(0, $aset->radiusEfektif());
        }

        // lokasi_tipe wajib terisi, karena dialah sumber radius bawaan.
        $this->assertSame(0, Asset::whereNull('lokasi_tipe')->count());
    }

    /**
     * Bukti inti rancangan: tiga lantai bertumpuk memakai satu koordinat.
     * GPS tidak punya sumbu vertikal, jadi QR yang menentukan aset.
     */
    #[Test]
    public function ada_koordinat_yang_dipakai_lebih_dari_satu_aset(): void
    {
        $bertumpuk = Asset::query()
            ->selectRaw('lat, lng, count(*) as jumlah')
            ->groupBy('lat', 'lng')
            ->havingRaw('count(*) > 1')
            ->get();

        $this->assertCount(8, $bertumpuk);
        $this->assertTrue($bertumpuk->every(fn ($r) => $r->jumlah === 3));
    }

    #[Test]
    public function aset_satu_titik_beda_lantai(): void
    {
        $satuTitik = Asset::whereIn('kode', ['01D', '09D', '17D'])->get();

        $this->assertCount(3, $satuTitik);
        $this->assertCount(1, $satuTitik->map(fn ($a) => $a->lat . ',' . $a->lng)->unique());
        $this->assertCount(3, $satuTitik->pluck('lantai')->unique());
    }

    #[Test]
    public function atribut_json_apar_terbaca_sebagai_angka(): void
    {
        $aset = Asset::where('kode', '01D')->first();

        $this->assertSame('Dry Powder', $aset->attributes['tipe']);
        // CSV lokal ID menulis "4,5"; harus jadi 4.5 bukan 45 atau 4.
        $this->assertSame(4.5, $aset->attributes['kapasitas_kg']);
    }

    #[Test]
    public function checklist_apar_sepuluh_item_empat_grup(): void
    {
        $apar = AssetType::where('slug', 'apar')->first();

        $this->assertSame(4, ChecklistGroup::where('asset_type_id', $apar->id)->count());
        $this->assertSame(10, ChecklistItem::count());
    }

    #[Test]
    public function setiap_item_checklist_punya_dasar_hukum(): void
    {
        $this->assertSame(0, ChecklistItem::whereNull('dasar_hukum')->count());
    }

    /** APAR hanya dilihat, tidak dioperasikan, jadi tak ada butir berdimensi fungsi. */
    #[Test]
    public function checklist_apar_tanpa_dimensi_fungsi(): void
    {
        /*
         * APAR tidak dioperasikan saat inspeksi, hanya dilihat, jadi TIDAK ADA
         * butir berdimensi 'fungsi' di checklist APAR. Dimensi fungsi baru
         * muncul di Hydrant (kran dibuka) dan Mobil Pemadam (mesin dinyalakan).
         *
         * Pembagian nyata: 7 boolean (ada / tidak ada) + 3 select berperingkat.
         * Ketiga select itu ada karena satu nilai benar/salah tidak cukup:
         * tingkat karat dan arah simpangan tekanan menentukan berat temuan.
         */
        $this->assertSame(0, ChecklistItem::where('answer_type', 'fungsi')->count());
        $this->assertSame(7, ChecklistItem::where('answer_type', 'boolean')->count());
        $this->assertSame(3, ChecklistItem::where('answer_type', 'select')->count());

        // Semua butir boolean dicetak sebagai dua nilai di laporan PMS.
        foreach (ChecklistItem::where('answer_type', 'boolean')->get() as $item) {
            $this->assertTrue($item->isDuaNilai(), "Butir {$item->label} harus dua nilai.");
        }

        // Setiap select wajib punya options, kalau tidak layar isian jadi kosong.
        foreach (ChecklistItem::where('answer_type', 'select')->get() as $item) {
            $this->assertNotEmpty($item->daftarPilihan(), "Select {$item->label} tanpa pilihan.");
        }
    }

    #[Test]
    public function urutan_item_checklist_satu_sampai_sepuluh(): void
    {
        $this->assertSame(
            range(1, 10),
            ChecklistItem::orderBy('urut')->pluck('urut')->all(),
        );
    }

    #[Test]
    public function seeder_idempoten_tidak_menggandakan_data(): void
    {
        $this->seed(MasterSeeder::class);
        $this->seed(AparSeeder::class);

        $this->assertSame(35, Asset::count());
        $this->assertSame(10, ChecklistItem::count());
        $this->assertSame(4, AssetType::count());
        $this->assertSame(3, Division::count());
    }

    #[Test]
    public function aset_belum_pernah_dicek_dianggap_jatuh_tempo(): void
    {
        $aset = Asset::with('assetType')->where('kode', '01D')->first();

        $this->assertNull($aset->terakhir_dicek);
        $this->assertTrue($aset->jatuhTempo());
    }
}

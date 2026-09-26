<?php

namespace Tests\Unit;

use App\Models\Asset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Gerbang GPS diuji tanpa basis data: model cukup diisi atribut. */
class AssetGateTest extends TestCase
{
    private const LAT = -8.186185;
    private const LNG = 113.706195;

    private function aset(int $radius = 75): Asset
    {
        return new Asset([
            'lat'      => self::LAT,
            'lng'      => self::LNG,
            'radius_m' => $radius,
        ]);
    }

    #[Test]
    public function jarak_nol_saat_di_titik_yang_sama(): void
    {
        $this->assertSame(0, $this->aset()->jarakDari(self::LAT, self::LNG));
    }

    #[Test]
    public function jarak_haversine_masuk_akal_untuk_pergeseran_kecil(): void
    {
        // 0.00009 derajat bujur di lintang -8.19 kira-kira 9,9 m.
        $jarak = $this->aset()->jarakDari(self::LAT, self::LNG + 0.00009);

        $this->assertNotNull($jarak);
        $this->assertGreaterThanOrEqual(9, $jarak);
        $this->assertLessThanOrEqual(11, $jarak);
    }

    #[Test]
    public function jarak_null_kalau_koordinat_perangkat_kosong(): void
    {
        $this->assertNull($this->aset()->jarakDari(null, null));
    }

    #[Test]
    public function jarak_null_kalau_aset_belum_punya_koordinat(): void
    {
        $tanpaTitik = new Asset(['radius_m' => 75]);

        $this->assertNull($tanpaTitik->jarakDari(self::LAT, self::LNG));
    }

    #[Test]
    public function radius_bawaan_dipakai_kalau_kolom_kosong(): void
    {
        $this->assertSame(Asset::RADIUS_BAWAAN_M, (new Asset)->radiusEfektif());
        $this->assertSame(60, (new Asset(['radius_m' => 60]))->radiusEfektif());
    }

    /**
     * GPS adalah bukti, bukan pemblokir: di luar radius tetap tersimpan,
     * hanya ditandai perlu_review supaya inspektur tidak balik ke kertas.
     */
    #[Test]
    #[DataProvider('kasusGerbang')]
    public function evaluasi_gerbang(string $harap, ?float $lat, ?float $lng, ?int $accuracy): void
    {
        $hasil = $this->aset()->evaluasiGerbang($lat, $lng, $accuracy);

        $this->assertSame($harap, $hasil['status']);
    }

    public static function kasusGerbang(): array
    {
        return [
            'di titik, akurasi bagus'     => ['lolos',        self::LAT,           self::LNG,            8],
            'geser 10 m'                  => ['lolos',        self::LAT,           self::LNG + 0.00009,  8],
            'geser 50 m masih dalam area' => ['lolos',        self::LAT - 0.00045, self::LNG,            10],
            'jauh 200 m'                  => ['perlu_review', self::LAT - 0.0018,  self::LNG,            10],
            'akurasi lemah dalam beton'   => ['gps_lemah',    self::LAT,           self::LNG,            120],
            'tanpa GPS'                   => ['tanpa_gps',    null,                null,                 null],
        ];
    }

    /** Akurasi buruk tidak boleh dihitung sebagai pelanggaran lokasi. */
    #[Test]
    public function akurasi_lemah_menang_atas_jarak_jauh(): void
    {
        $hasil = $this->aset()->evaluasiGerbang(self::LAT - 0.0018, self::LNG, 200);

        $this->assertSame('gps_lemah', $hasil['status']);
        $this->assertNotNull($hasil['jarak_m']);
    }

    /** Akurasi longgar menambah toleransi ambang, bukan menolak. */
    #[Test]
    public function akurasi_menambah_toleransi_ambang(): void
    {
        $aset = $this->aset(30);

        // 40 m dari titik, radius 30 m -> di luar kalau akurasi diabaikan.
        $lat = self::LAT - 0.00036;

        $this->assertSame('perlu_review', $aset->evaluasiGerbang($lat, self::LNG, 1)['status']);
        $this->assertSame('lolos',        $aset->evaluasiGerbang($lat, self::LNG, 20)['status']);
    }

    #[Test]
    public function token_qr_acak_dan_tidak_memuat_nomor_aset(): void
    {
        $a = Asset::buatToken();
        $b = Asset::buatToken();

        $this->assertNotSame($a, $b);
        $this->assertStringStartsWith('PAL-K3-', $a);
        $this->assertSame(39, strlen($a));
        $this->assertMatchesRegularExpression('/^PAL-K3-[0-9a-f]{32}$/', $a);
    }
}

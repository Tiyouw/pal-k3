<?php

namespace Tests\Unit;

use App\Models\Asset;
use PHPUnit\Framework\TestCase;

/**
 * Menjaga bunyi label lokasi aset.
 *
 * QA menemukan stiker fisik mencetak "APAR 31D Pos 1 &middot; Lt. -" dan dasbor
 * mencetak "GEDUNG PIP LT. LT. 1". Dua sebabnya: kolom lantai di CSV sumber
 * sudah berawalan "Lt." sendiri, dan aset area terbuka memakai "-" sebagai
 * penanda tanpa lantai. Test ini memakai model tanpa menyentuh basis data
 * karena yang diuji murni pengolahan teks.
 */
class LabelLokasiAsetTest extends TestCase
{
    private function aset(array $atribut): Asset
    {
        $a = new Asset;
        foreach ($atribut as $kunci => $nilai) {
            $a->{$kunci} = $nilai;
        }

        return $a;
    }

    public function test_lantai_yang_sudah_berawalan_tidak_diawali_lagi(): void
    {
        $a = $this->aset(['lantai' => 'Lt. 1']);

        // Bukan "Lt. Lt. 1" seperti yang tercetak sebelum perbaikan.
        $this->assertSame('Lt. 1', $a->labelLantai());
    }

    public function test_lantai_angka_saja_tetap_diawali(): void
    {
        $this->assertSame('Lt. 2', $this->aset(['lantai' => '2'])->labelLantai());
    }

    public function test_penanda_tanpa_lantai_menghasilkan_null(): void
    {
        foreach (['-', '', '  ', '–'] as $penanda) {
            $this->assertNull(
                $this->aset(['lantai' => $penanda])->labelLantai(),
                "Lantai '{$penanda}' seharusnya dianggap tanpa lantai."
            );
        }
    }

    public function test_label_lokasi_menggabungkan_titik_dan_lantai(): void
    {
        $a = $this->aset([
            'lokasi_teks' => 'Lorong Timur titik 1',
            'gedung' => 'Gedung PIP',
            'lantai' => 'Lt. 1',
        ]);

        $this->assertSame('Lorong Timur titik 1 · Lt. 1', $a->labelLokasi());
    }

    public function test_label_lokasi_area_terbuka_tanpa_ekor_lantai(): void
    {
        $a = $this->aset([
            'lokasi_teks' => 'Pos 1',
            'gedung' => 'Area Terbuka',
            'lantai' => '-',
        ]);

        // Sebelum perbaikan ekornya "· Lt. -" dan ikut tercetak di stiker.
        $this->assertSame('Pos 1', $a->labelLokasi());
    }

    public function test_label_lokasi_jatuh_ke_gedung_saat_titik_kosong(): void
    {
        $a = $this->aset([
            'lokasi_teks' => null,
            'gedung' => 'Workshop Fabrikasi',
            'lantai' => '3',
        ]);

        $this->assertSame('Workshop Fabrikasi · Lt. 3', $a->labelLokasi());
    }

    public function test_label_lokasi_tak_pernah_kosong(): void
    {
        $this->assertSame('-', $this->aset(['lokasi_teks' => null, 'gedung' => null, 'lantai' => null])->labelLokasi());
    }
}

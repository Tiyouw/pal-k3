<?php

namespace Tests\Unit;

use App\Models\ChecklistItem;
use App\Models\InspectionAnswer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Laporan PMS resmi memakai empat simbol dalam dua dimensi terpisah:
 * fungsi  -> centang / X
 * kondisi -> O / O bergaris
 * Web lama hanya menyimpan Baik/Tidak Baik, jadi tidak bisa dicetak jadi laporan PMS.
 */
class InspectionAnswerTest extends TestCase
{
    private function jawaban(string $answerType, array $atribut, ?int $jumlahBaku = null): InspectionAnswer
    {
        $item = new ChecklistItem([
            'label'       => 'uji',
            'answer_type' => $answerType,
            'jumlah_baku' => $jumlahBaku,
        ]);

        $jawaban = new InspectionAnswer($atribut);
        $jawaban->setRelation('item', $item);

        return $jawaban;
    }

    #[Test]
    public function simbol_dimensi_fungsi_memakai_centang_dan_silang(): void
    {
        $this->assertSame("\u{2713}", $this->jawaban('fungsi', ['nilai' => 'ok'])->simbol());
        $this->assertSame('X',        $this->jawaban('fungsi', ['nilai' => 'tidak'])->simbol());
    }

    #[Test]
    public function simbol_dimensi_kondisi_memakai_o_dan_o_bergaris(): void
    {
        $this->assertSame('O',        $this->jawaban('kondisi', ['nilai' => 'ok'])->simbol());
        $this->assertSame("\u{00D8}", $this->jawaban('kondisi', ['nilai' => 'tidak'])->simbol());
    }

    #[Test]
    public function empat_simbol_pms_tidak_saling_tertukar(): void
    {
        $simbol = [
            InspectionAnswer::SIMBOL_FUNGSI_OK,
            InspectionAnswer::SIMBOL_FUNGSI_TIDAK,
            InspectionAnswer::SIMBOL_KONDISI_OK,
            InspectionAnswer::SIMBOL_KONDISI_TDK,
        ];

        $this->assertCount(4, array_unique($simbol));
        // O dan O-bergaris harus beda byte, bukan sekadar beda tampilan.
        $this->assertNotSame(
            bin2hex(InspectionAnswer::SIMBOL_KONDISI_OK),
            bin2hex(InspectionAnswer::SIMBOL_KONDISI_TDK),
        );
    }

    #[Test]
    public function simbol_number_menampilkan_angka_hasil_hitung(): void
    {
        $this->assertSame('14', $this->jawaban('number', ['nilai_angka' => 14], 40)->simbol());
        $this->assertSame('-',  $this->jawaban('number', [], 40)->simbol());
    }

    #[Test]
    public function nilai_tidak_dihitung_sebagai_temuan(): void
    {
        $this->assertTrue($this->jawaban('kondisi', ['nilai' => 'tidak'])->isTemuan());
        $this->assertFalse($this->jawaban('kondisi', ['nilai' => 'ok'])->isTemuan());
    }

    /** Kotak P3K: jumlah kurang dari baku Permenakertrans 15/2008 = temuan otomatis. */
    #[Test]
    public function jumlah_kurang_dari_baku_jadi_temuan(): void
    {
        $kurang = $this->jawaban('number', ['nilai_angka' => 14], 40);
        $cukup  = $this->jawaban('number', ['nilai_angka' => 40], 40);
        $lebih  = $this->jawaban('number', ['nilai_angka' => 45], 40);

        $this->assertTrue($kurang->isTemuan());
        $this->assertFalse($cukup->isTemuan());
        $this->assertFalse($lebih->isTemuan());
    }

    #[Test]
    public function kekurangan_dihitung_otomatis(): void
    {
        $this->assertSame(26, $this->jawaban('number', ['nilai_angka' => 14], 40)->kekurangan());
        $this->assertNull($this->jawaban('number', ['nilai_angka' => 40], 40)->kekurangan());
        $this->assertNull($this->jawaban('number', ['nilai_angka' => 50], 40)->kekurangan());
    }

    #[Test]
    public function kekurangan_null_untuk_tipe_non_angka(): void
    {
        $this->assertNull($this->jawaban('kondisi', ['nilai' => 'tidak'])->kekurangan());
    }

    #[Test]
    public function tanpa_jumlah_baku_angka_tidak_bisa_jadi_temuan(): void
    {
        $this->assertFalse($this->jawaban('number', ['nilai_angka' => 3])->isTemuan());
    }

    #[Test]
    public function item_dua_nilai_dikenali(): void
    {
        $this->assertTrue((new ChecklistItem(['answer_type' => 'fungsi']))->isDuaNilai());
        $this->assertTrue((new ChecklistItem(['answer_type' => 'kondisi']))->isDuaNilai());
        $this->assertFalse((new ChecklistItem(['answer_type' => 'number']))->isDuaNilai());
        $this->assertFalse((new ChecklistItem(['answer_type' => 'text']))->isDuaNilai());
    }
}

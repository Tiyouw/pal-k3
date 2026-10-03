<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Database\Seeders\AparSeeder;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Halaman muka membaca tabel asset_types untuk menyusun kartu modul, jadi tes
 * ini wajib memakai RefreshDatabase + seeder. Versi bawaan Laravel tidak
 * menyentuh basis data dan gagal dengan "no such table: asset_types".
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** Emoji dan piktograf, termasuk pemilih variasi dan penyambung emoji. */
    private const POLA_EMOJI = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterSeeder::class);
        $this->seed(AparSeeder::class);
    }

    /**
     * Kata "APAR" juga ada di judul hero dan label wordmark, jadi keberadaan
     * kartu diperiksa di dalam bagian #modul saja, dengan nama dari data induk.
     */
    #[Test]
    public function halaman_muka_merender_dan_memuat_empat_kartu_modul(): void
    {
        $nama = AssetType::query()->orderBy('urut')->pluck('nama')->all();
        $this->assertCount(4, $nama, 'MasterSeeder seharusnya mengisi empat tipe objek.');

        $kartu = $this->kartuModul($this->get('/')->assertOk()->getContent());

        $this->assertSame($nama, array_keys($kartu));
    }

    /** Modul yang sudah punya checklist "Sudah beroperasi"; sisanya "Tahap berikutnya". */
    #[Test]
    public function kartu_modul_menandai_status_sesuai_checklist(): void
    {
        $tipe = AssetType::query()->orderBy('urut')->get();

        // Penjaga: seeder memberi checklist hanya ke APAR, jadi kedua label teruji.
        $this->assertSame(
            ['apar'],
            $tipe->filter(fn (AssetType $t) => $t->checklistGroups()->exists())->pluck('slug')->all(),
        );

        $kartu = $this->kartuModul($this->get('/')->assertOk()->getContent());

        foreach ($tipe as $t) {
            [$label, $bukan] = $t->checklistGroups()->exists()
                ? ['Sudah beroperasi', 'Tahap berikutnya']
                : ['Tahap berikutnya', 'Sudah beroperasi'];

            $this->assertStringContainsString($label, $kartu[$t->nama], "Kartu {$t->nama}");
            $this->assertStringNotContainsString($bukan, $kartu[$t->nama], "Kartu {$t->nama}");
        }
    }

    /** Tamu di halaman muka diarahkan ke halaman masuk petugas, bukan panel admin. */
    #[Test]
    public function tamu_melihat_tautan_masuk_petugas(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertEqualsCanonicalizing(
            ['Masuk', 'Masuk petugas'],
            array_values(array_unique($this->labelTautanKe($html, route('petugas.masuk')))),
        );
        $this->assertTautanTidakMenujuKe($html, [route('petugas.beranda'), url('/admin')]);
    }

    /**
     * Pengguna yang sudah masuk tidak ditawari halaman masuk lagi: inspektur ke
     * beranda petugas, administrator dan pemantau ke panel.
     */
    #[Test]
    #[DataProvider('penggunaMasuk')]
    public function pengguna_masuk_melihat_tautan_ke_aplikasinya(string $peran, ?string $rute): void
    {
        $tujuan = $rute ? route($rute) : url('/admin');
        $lain = array_values(array_diff([route('petugas.masuk'), route('petugas.beranda'), url('/admin')], [$tujuan]));

        $html = $this->actingAs(User::factory()->{$peran}()->create())
            ->get('/')
            ->assertOk()
            ->getContent();

        $this->assertEqualsCanonicalizing(
            ['Buka aplikasi', 'Lanjutkan pekerjaan'],
            array_values(array_unique($this->labelTautanKe($html, $tujuan))),
        );
        $this->assertTautanTidakMenujuKe($html, $lain);
    }

    public static function penggunaMasuk(): array
    {
        return [
            'inspektur'     => ['inspektur', 'petugas.beranda'],
            'administrator' => ['admin', null],
            'pemantau'      => ['pemantau', null],
        ];
    }

    /**
     * Halaman muka terbuka tanpa masuk. Token QR adalah satu-satunya penentu
     * keaslian pemeriksaan, dan sebaran titik APAR tidak layak diumumkan.
     */
    #[Test]
    public function halaman_muka_tidak_membuka_token_qr_maupun_lokasi_aset(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/PAL-K3-[0-9a-f]{32}/', $html);

        $rahasia = Asset::all()
            ->flatMap(fn (Asset $a) => [$a->qr_token, $a->lokasi_teks, $a->lat, $a->lng])
            ->filter(fn ($nilai) => filled($nilai))
            ->map(fn ($nilai) => (string) $nilai);

        $this->assertNotEmpty($rahasia, 'Seeder APAR seharusnya mengisi aset untuk diperiksa.');

        foreach ($rahasia as $nilai) {
            $this->assertStringNotContainsString($nilai, $html);
        }
    }

    /**
     * Keluhan pengguna atas versi lama: emoji membuat halaman terasa asal jadi.
     * Halaman lama menulis emoji sebagai entitas (&#128680;), jadi HTML didekode
     * dulu sebelum dicocokkan.
     */
    #[Test]
    public function halaman_muka_tanpa_emoji(): void
    {
        $this->assertMatchesRegularExpression(self::POLA_EMOJI, $this->dekode('&#128680;'));

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(self::POLA_EMOJI, $this->dekode($html));
    }

    private function dekode(string $html): string
    {
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Teks polos dari potongan HTML, spasi dirapatkan. */
    private function teks(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', $this->dekode(strip_tags($html))));
    }

    /**
     * Kartu di bagian #modul sebagai [judul kartu => teks kartu]. Bagian halaman
     * tidak bersarang, jadi </section> pertama sesudah pembukanya adalah penutupnya.
     */
    private function kartuModul(string $html): array
    {
        $mulai = strpos($html, '<section id="modul"');
        $this->assertNotFalse($mulai, 'Bagian #modul tidak ditemukan.');
        $bagian = substr($html, $mulai, strpos($html, '</section>', $mulai) - $mulai);

        preg_match_all('/<li\b[^>]*>(.*?)<\/li>/s', $bagian, $cocok);

        $kartu = [];
        foreach ($cocok[1] as $isi) {
            if (preg_match('/<h3\b[^>]*>(.*?)<\/h3>/s', $isi, $judul)) {
                $kartu[$this->teks($judul[1])] = $this->teks($isi);
            }
        }

        return $kartu;
    }

    /** Label tiap tautan <a> yang menuju $alamat, urut kemunculan. */
    private function labelTautanKe(string $html, string $alamat): array
    {
        preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/s', $html, $cocok, PREG_SET_ORDER);

        $label = [];
        foreach ($cocok as [, $atribut, $isi]) {
            if (preg_match('/\bhref="([^"]*)"/', $atribut, $href) && $this->dekode($href[1]) === $alamat) {
                $label[] = $this->teks($isi);
            }
        }

        return $label;
    }

    private function assertTautanTidakMenujuKe(string $html, array $alamat): void
    {
        preg_match_all('/\bhref="([^"]*)"/', $html, $cocok);
        $href = array_map($this->dekode(...), $cocok[1]);

        foreach ($alamat as $a) {
            $this->assertNotContains($a, $href, "Ada tautan ke {$a}.");
        }
    }
}

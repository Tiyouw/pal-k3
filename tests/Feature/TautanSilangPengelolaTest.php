<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji bahwa halaman pengelolaan saling terhubung.
 *
 * Alasan berkas ini ada: pemeriksaan di situs hidup menemukan /stiker sama sekali
 * tidak punya tautan keluar (nol elemen href), jadi begitu dibuka satu-satunya
 * jalan kembali adalah tombol mundur peramban. Lembar stiker dan laporan bulanan
 * adalah rute Blade biasa, bukan Resource Filament, sehingga keduanya juga tidak
 * pernah muncul di sidebar panel dan harus diketik dari ingatan.
 *
 * Test masuk lewat POST /masuk, bukan actingAs(), supaya sesi sungguhan terbaca;
 * lihat catatan yang sama di HalamanGalatTest.
 */
class TautanSilangPengelolaTest extends TestCase
{
    use RefreshDatabase;

    private const SANDI_UJI = 'sandi-uji-yang-panjang';

    private function buatPengguna(string $peran, string $nip): User
    {
        return User::create([
            'nip' => $nip,
            'name' => "Uji {$peran}",
            'email' => "{$nip}@uji.local",
            'password' => bcrypt(self::SANDI_UJI),
            'role' => $peran,
            'jabatan' => "Uji {$peran}",
            'aktif' => true,
        ]);
    }

    private function masukSebagai(string $peran, string $nip): User
    {
        $pengguna = $this->buatPengguna($peran, $nip);

        $this->post('/masuk', [
            'nip' => $nip,
            'password' => self::SANDI_UJI,
        ])->assertRedirect();

        $this->assertAuthenticatedAs($pengguna);

        return $pengguna;
    }

    /** Lembar stiker butuh minimal satu aset, kalau tidak halamannya kosong. */
    private function buatAset(): Asset
    {
        $tipe = AssetType::create([
            'slug' => 'apar',
            'nama' => 'APAR',
            'periode_hari' => 30,
            'aktif' => true,
        ]);

        $div = Division::create([
            'nama' => 'Divisi Uji',
            'kode' => 'UJI',
            'aktif' => true,
        ]);

        // qr_token sengaja tak diisi: Asset::booted() menerbitkannya sendiri.
        return Asset::create([
            'asset_type_id' => $tipe->id,
            'division_id' => $div->id,
            'kode' => 'UJI-01',
            'gedung' => 'Gedung Uji',
            'lantai' => 'Lt. 1',
            'lokasi_teks' => 'Titik uji',
            'aktif' => true,
        ]);
    }

    public function test_lembar_stiker_punya_jalan_keluar_ke_panel_dan_laporan(): void
    {
        $this->buatAset();
        $this->masukSebagai(User::ROLE_ADMIN, '1021');

        $res = $this->get('/stiker');

        $res->assertOk();
        $res->assertSee('Panel admin');
        $res->assertSee('Laporan bulanan');
        $res->assertSee('href="/admin"', false);
        $res->assertSee('href="' . route('laporan.index') . '"', false);
    }

    public function test_laporan_punya_jalan_keluar_ke_panel_dan_stiker(): void
    {
        $this->buatAset();
        $this->masukSebagai(User::ROLE_ADMIN, '1022');

        $res = $this->get('/laporan');

        $res->assertOk();
        $res->assertSee('href="/admin"', false);
        $res->assertSee('href="' . route('stiker.index') . '"', false);
    }

    public function test_kepala_navigasi_stiker_disembunyikan_saat_dicetak(): void
    {
        $this->buatAset();
        $this->masukSebagai(User::ROLE_ADMIN, '1023');

        // Kalau aturan cetak ini hilang, kepala biru ikut tercetak dan mendorong
        // baris label pertama turun sehingga stiker tak lagi pas di kertas.
        $this->get('/stiker')->assertSee('.panel, .kepala { display: none; }', false);
    }

    public function test_pemantau_juga_melihat_tautan_silang(): void
    {
        $this->buatAset();
        $this->masukSebagai(User::ROLE_PEMANTAU, '3021');

        // Pemantau termasuk peran panel: tugasnya membaca laporan, jadi jalan
        // antar halaman pengelolaan harus tetap terbuka untuknya.
        $this->get('/stiker')->assertOk()->assertSee('Laporan bulanan');
    }

    public function test_inspektur_tetap_ditolak_di_halaman_pengelolaan(): void
    {
        $this->buatAset();
        $this->masukSebagai(User::ROLE_INSPEKTUR, '2021');

        // Tautan silang tidak boleh berubah menjadi celah wewenang: yang menjaga
        // tetap middleware 'pengelola', bukan ada atau tidaknya tautan.
        $this->get('/stiker')->assertForbidden();
        $this->get('/laporan')->assertForbidden();
    }
}

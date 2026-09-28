<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji halaman galat berbahasa Indonesia dan jalan pulangnya.
 *
 * QA menemukan alamat tak dikenal membalas halaman bawaan Laravel: berbahasa
 * Inggris, tanpa satu pun tautan keluar. Petugas yang salah pindai jadi
 * terjebak di halaman buntu sambil memegang tabung.
 *
 * Yang diuji bukan cuma kode status, tetapi isi halamannya: tujuan tombol
 * pulang berbeda per peran, dan halaman harus tetap terbentuk untuk tamu yang
 * belum masuk (kasus paling sering, sebab galat banyak muncul saat sesi habis).
 */
class HalamanGalatTest extends TestCase
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

    /**
     * Masuk lewat POST /masuk, bukan actingAs().
     *
     * Alasannya penting: actingAs() menyuntik pengguna langsung ke container
     * sehingga halaman 404 tetap "melihat" pengguna walau sesi belum dibaca.
     * Versi awal test ini memakai actingAs() dan memberi hijau palsu, sementara
     * di situs hidup petugas yang sudah masuk tetap disuguhi tombol "Masuk
     * dengan NIP". Masuk lewat sesi nyata membuat test menangkap kelas galat
     * itu, termasuk kalau rute penadah 404 hilang lagi dari routes/web.php.
     */
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

    public function test_alamat_tak_dikenal_membalas_404_berbahasa_indonesia(): void
    {
        $res = $this->get('/alamat-yang-tidak-pernah-ada');

        $res->assertStatus(404);
        $res->assertSee('Halaman tidak ditemukan');
        $res->assertDontSee('Not Found', false);
    }

    public function test_404_untuk_tamu_menawarkan_pintu_masuk(): void
    {
        // Tamu tak punya beranda petugas, jadi jalan pulangnya halaman masuk.
        $res = $this->get('/alamat-yang-tidak-pernah-ada');

        $res->assertSee('Masuk dengan NIP');
        $res->assertSee(route('petugas.masuk'), false);
    }

    public function test_404_untuk_inspektur_menawarkan_daftar_tugas_dan_pemindai(): void
    {
        $this->masukSebagai(User::ROLE_INSPEKTUR, '2091');

        $res = $this->get('/alamat-yang-tidak-pernah-ada');

        $res->assertSee('Kembali ke daftar tugas');
        $res->assertSee(route('petugas.pindai'), false);
    }

    public function test_404_untuk_pemantau_menawarkan_panel(): void
    {
        $this->masukSebagai(User::ROLE_PEMANTAU, '3091');

        $res = $this->get('/alamat-yang-tidak-pernah-ada');

        $res->assertSee('Kembali ke panel');
        // Pemantau bukan inspektur, jadi tombol pemindai tak boleh ditawarkan.
        $res->assertDontSee('Buka pemindai QR');
    }

    public function test_inspektur_yang_membuka_lembar_stiker_dapat_403_yang_menjelaskan(): void
    {
        $this->masukSebagai(User::ROLE_INSPEKTUR, '2092');

        $res = $this->get('/stiker');

        // 403 di sini disengaja: kalau inspektur bisa melihat lembar QR, ia
        // dapat memindai dari mana saja dan pemindaian berhenti jadi bukti
        // kehadiran di depan objek.
        $res->assertStatus(403);
        $res->assertSee('Tidak punya wewenang');
        $res->assertSee('Kembali ke daftar tugas');
    }
}

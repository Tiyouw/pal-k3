<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji batas wewenang peran di panel /admin.
 *
 * Alasan berkas ini ada: QA menemukan pemantau (peran yang seharusnya hanya
 * membaca papan pantau dan laporan) melihat tombol Simpan dan Hapus di form
 * ubah aset. Repo tak punya app/Policies dan AssetResource tak punya
 * canEdit/canDelete/canCreate, jadi Filament memakai bawaannya: izinkan semua.
 * Test ini membuktikan apakah itu benar-benar bocor di sisi server, bukan
 * cuma tombol yang tampil, dan jadi penjaga supaya tak balik bocor.
 *
 * Catatan skema: asset_types.slug wajib (NOT NULL unik) dan users masih
 * memakai email unik dari tabel bawaan Laravel di samping nip.
 */
class OtorisasiPanelTest extends TestCase
{
    use RefreshDatabase;

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

    private function buatPengguna(string $peran, string $nip): User
    {
        return User::create([
            'nip' => $nip,
            'name' => "Uji {$peran}",
            'email' => "{$nip}@uji.local",
            'password' => bcrypt('sandi-uji-yang-panjang'),
            'role' => $peran,
            'jabatan' => "Uji {$peran}",
            'aktif' => true,
        ]);
    }

    public function test_pemantau_tidak_boleh_menulis_data_aset(): void
    {
        $aset = $this->buatAset();
        $this->actingAs($this->buatPengguna(User::ROLE_PEMANTAU, '3009'));

        $this->assertFalse(
            AssetResource::canEdit($aset),
            'Pemantau seharusnya TIDAK boleh mengubah aset.'
        );
        $this->assertFalse(
            AssetResource::canDelete($aset),
            'Pemantau seharusnya TIDAK boleh menghapus aset.'
        );
        $this->assertFalse(
            AssetResource::canCreate(),
            'Pemantau seharusnya TIDAK boleh membuat aset.'
        );
    }

    public function test_pemantau_masih_boleh_melihat_daftar_aset(): void
    {
        $aset = $this->buatAset();
        $this->actingAs($this->buatPengguna(User::ROLE_PEMANTAU, '3010'));

        // Pemantau tetap perlu membaca: tugasnya memantau, bukan mengubah.
        $this->assertTrue(
            AssetResource::canViewAny(),
            'Pemantau harus tetap boleh melihat daftar aset.'
        );
    }

    public function test_admin_tetap_boleh_menulis_data_aset(): void
    {
        $aset = $this->buatAset();
        $this->actingAs($this->buatPengguna(User::ROLE_ADMIN, '1009'));

        $this->assertTrue(
            AssetResource::canEdit($aset),
            'Admin harus tetap boleh mengubah aset.'
        );
        $this->assertTrue(
            AssetResource::canCreate(),
            'Admin harus tetap boleh membuat aset.'
        );
    }
}

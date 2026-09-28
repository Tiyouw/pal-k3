<?php

namespace Tests\Feature;

use App\Models\Asset;
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
 * Test ini memastikan apakah itu benar-benar bocor di server, bukan cuma
 * tombol yang tampil, dan menjadi penjaga supaya tak balik bocor.
 */
class OtorisasiPanelTest extends TestCase
{
    use RefreshDatabase;

    private function buatAset(): Asset
    {
        $div = Division::create([
            'nama' => 'Divisi Uji', 'kode' => 'UJI', 'aktif' => true,
        ]);

        return Asset::create([
            'kode' => 'UJI-01',
            'asset_type_id' => \App\Models\AssetType::create([
                'nama' => 'APAR', 'kode' => 'APAR', 'aktif' => true,
            ])->id,
            'division_id' => $div->id,
            'gedung' => 'Gedung Uji',
            'lantai' => 'Lt. 1',
            'lokasi_teks' => 'Titik uji',
            'aktif' => true,
        ]);
    }

    private function buatPengguna(string $peran): User
    {
        return User::create([
            'nip' => $peran === User::ROLE_PEMANTAU ? '3009' : '1009',
            'name' => "Uji {$peran}",
            'password' => bcrypt('sandi-uji-panjang'),
            'role' => $peran,
            'aktif' => true,
        ]);
    }

    public function test_pemantau_tidak_boleh_mengubah_aset(): void
    {
        $aset = $this->buatAset();
        $pemantau = $this->buatPengguna(User::ROLE_PEMANTAU);

        $this->actingAs($pemantau);

        // Filament menilai wewenang lewat Resource; kalau tak ada aturan,
        // pemantau dianggap boleh menulis.
        $bolehUbah = \App\Filament\Resources\AssetResource::canEdit($aset);
        $bolehHapus = \App\Filament\Resources\AssetResource::canDelete($aset);
        $bolehBuat = \App\Filament\Resources\AssetResource::canCreate();

        $this->assertFalse(
            $bolehUbah,
            'Pemantau seharusnya TIDAK boleh mengubah aset.'
        );
        $this->assertFalse(
            $bolehHapus,
            'Pemantau seharusnya TIDAK boleh menghapus aset.'
        );
        $this->assertFalse(
            $bolehBuat,
            'Pemantau seharusnya TIDAK boleh membuat aset.'
        );
    }

    public function test_admin_tetap_boleh_mengubah_aset(): void
    {
        $aset = $this->buatAset();
        $admin = $this->buatPengguna(User::ROLE_ADMIN);

        $this->actingAs($admin);

        $this->assertTrue(
            \App\Filament\Resources\AssetResource::canEdit($aset),
            'Admin harus tetap boleh mengubah aset.'
        );
        $this->assertTrue(
            \App\Filament\Resources\AssetResource::canCreate(),
            'Admin harus tetap boleh membuat aset.'
        );
    }
}

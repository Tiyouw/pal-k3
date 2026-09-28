<?php

namespace Tests\Feature;

use App\Filament\Resources\AssetResource;
use App\Filament\Resources\AssetTypeResource;
use App\Filament\Resources\ChecklistGroupResource;
use App\Filament\Resources\ChecklistItemResource;
use App\Filament\Resources\DivisionResource;
use App\Filament\Resources\InspectionResource;
use App\Filament\Resources\IssueResource;
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
 * ubah aset. Repo tak punya app/Policies dan Resource tak punya
 * canEdit/canDelete/canCreate, jadi Filament memakai bawaannya: izinkan semua.
 * Test ini membuktikan apakah itu benar-benar bocor di sisi server, bukan
 * cuma tombol yang tampil, dan jadi penjaga supaya tak balik bocor.
 *
 * Seluruh Resource diuji dalam satu putaran, bukan cuma aset, supaya Resource
 * baru yang lupa memakai trait HanyaAdminBolehMenulis langsung memerahkan
 * suite alih-alih diam-diam terbuka.
 *
 * Catatan skema: asset_types.slug wajib (NOT NULL unik) dan users masih
 * memakai email unik dari tabel bawaan Laravel di samping nip.
 */
class OtorisasiPanelTest extends TestCase
{
    use RefreshDatabase;

    /** Semua Resource panel yang jalur tulisnya hanya untuk admin. */
    private const RESOURCE_PANEL = [
        AssetResource::class,
        AssetTypeResource::class,
        ChecklistGroupResource::class,
        ChecklistItemResource::class,
        DivisionResource::class,
        InspectionResource::class,
        IssueResource::class,
    ];

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

    public function test_pemantau_tidak_boleh_menulis_di_semua_resource(): void
    {
        $this->actingAs($this->buatPengguna(User::ROLE_PEMANTAU, '3009'));

        foreach (self::RESOURCE_PANEL as $resource) {
            $nama = class_basename($resource);

            $this->assertFalse(
                $resource::canCreate(),
                "Pemantau seharusnya TIDAK boleh membuat data di {$nama}."
            );
            $this->assertFalse(
                $resource::canDeleteAny(),
                "Pemantau seharusnya TIDAK boleh hapus massal di {$nama}."
            );
        }
    }

    public function test_pemantau_tidak_boleh_mengubah_atau_menghapus_aset(): void
    {
        $aset = $this->buatAset();
        $this->actingAs($this->buatPengguna(User::ROLE_PEMANTAU, '3011'));

        // Dicek per baris, bukan cuma per kelas: inilah jalur yang QA temukan
        // bocor lewat /admin/assets/1/edit.
        $this->assertFalse(
            AssetResource::canEdit($aset),
            'Pemantau seharusnya TIDAK boleh mengubah aset.'
        );
        $this->assertFalse(
            AssetResource::canDelete($aset),
            'Pemantau seharusnya TIDAK boleh menghapus aset.'
        );
    }

    public function test_pemantau_masih_boleh_melihat_daftar_aset(): void
    {
        $this->buatAset();
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
        $this->assertTrue(
            AssetResource::canDelete($aset),
            'Admin harus tetap boleh menghapus aset.'
        );
    }

    public function test_inspeksi_tak_bisa_dibuat_dari_panel_bahkan_oleh_admin(): void
    {
        $this->actingAs($this->buatPengguna(User::ROLE_ADMIN, '1010'));

        // Bukti bahwa canCreate() milik kelas menang atas trait: inspeksi hanya
        // lahir dari pindaian QR di lapangan, bukan diketik dari belakang meja.
        $this->assertFalse(
            InspectionResource::canCreate(),
            'Inspeksi tak boleh dibuat dari panel, termasuk oleh admin.'
        );
    }
}

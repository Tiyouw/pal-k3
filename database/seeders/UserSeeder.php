<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Bagian 13 rancangan: sembilan petugas untuk data awal pengembangan.
 *
 * Bagian 11.4 (kebijakan data pribadi): nama di sini adalah nama peran, BUKAN nama
 * pegawai PT PAL yang sebenarnya. Penggantian ke nama asli cukup dilakukan pada
 * berkas data awal ini, tidak menyentuh kode.
 *
 * Kata sandi awal sengaja seragam dan lemah karena ini lingkungan uji coba yang
 * diisi data sementara. Sebelum dipakai dengan data nyata PT PAL, kata sandi
 * wajib diganti dan pendaftaran petugas dilakukan lewat panel admin.
 *
 * Peran (Bagian 3.3):
 *   inspektur -> ponsel, mengisi checklist, melihat riwayat inspeksinya sendiri
 *   admin     -> komputer, data induk + laporan + tinjauan
 *   pemantau  -> komputer, hanya melihat papan pantau dan laporan
 */
class UserSeeder extends Seeder
{
    private const SANDI_AWAL = 'SANDI-DICABUT-DARI-RIWAYAT';

    public function run(): void
    {
        $k3lh = Division::where('kode', 'K3LH')->first();
        $ti   = Division::where('kode', 'TI')->first();
        $pip  = Division::where('kode', 'PIP')->first();

        $data = [
            // NIP, nama peran, surel, peran, jabatan
            ['1001', 'Admin K3',              'admin@pal.test',       'admin',     'Staf Administrasi K3LH'],
            ['1002', 'Kepala Seksi K3',       'kasi.k3@pal.test',     'admin',     'Kepala Seksi K3LH'],
            ['2001', 'Inspektur Gedung PIP',  'inspektur1@pal.test',  'inspektur', 'Petugas Inspeksi APAR'],
            ['2002', 'Inspektur Workshop',    'inspektur2@pal.test',  'inspektur', 'Petugas Inspeksi APAR'],
            ['2003', 'Inspektur Area Luar',   'inspektur3@pal.test',  'inspektur', 'Petugas Inspeksi APAR'],
            ['2004', 'Inspektur Pengganti',   'inspektur4@pal.test',  'inspektur', 'Petugas Inspeksi APAR'],
            ['2005', 'Inspektur Shift Malam', 'inspektur5@pal.test',  'inspektur', 'Petugas Inspeksi APAR'],
            ['3001', 'Pemantau Manajemen',    'pemantau@pal.test',    'pemantau',  'Manajer Infrastruktur'],
            ['3002', 'Pemantau TI',           'pemantau.ti@pal.test', 'pemantau',  'Staf Teknologi Informasi'],
        ];

        $divisiPeran = [
            'admin'     => $k3lh?->id,
            'inspektur' => $pip?->id,
            'pemantau'  => $ti?->id,
        ];

        foreach ($data as [$nip, $nama, $email, $role, $jabatan]) {
            $adaSebelumnya = User::where('nip', $nip)->exists();

            $atribut = [
                'name'    => $nama,
                'email'   => $email,
                'role'    => $role,
                'jabatan' => $jabatan,
                'aktif'   => true,
            ];

            // Sandi disertakan pada payload baris baru, bukan diisi sesudahnya.
            // users.password berstatus NOT NULL, jadi menyimpan dulu lalu
            // menambal sandi membuat INSERT pertama gagal sebelum ada baris
            // untuk ditambal. Pada baris yang sudah ada, sandi tidak disentuh
            // supaya seed ulang tidak menimpa sandi yang diganti admin.
            if (! $adaSebelumnya) {
                $atribut['password'] = Hash::make(self::SANDI_AWAL);
            }

            $user = User::updateOrCreate(['nip' => $nip], $atribut);

            // division_id opsional: kolom ini belum tentu ada di tabel users.
            if ($user->isFillable('division_id') && isset($divisiPeran[$role])) {
                $user->update(['division_id' => $divisiPeran[$role]]);
            }
        }

        $this->command?->info('Petugas diseed: ' . count($data) . ' (sandi awal: ' . self::SANDI_AWAL . ')');
    }
}

<?php

namespace Database\Seeders;

use App\Models\AssetType;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use App\Models\Division;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->divisi();
        $this->tipeObjek();
        $this->checklistApar();
    }

    private function divisi(): void
    {
        $data = [
            ['nama' => 'Manajemen Aset/Infrastruktur & K3LH', 'kode' => 'K3LH'],
            ['nama' => 'Teknologi Informasi',                 'kode' => 'TI'],
            ['nama' => 'Gedung PIP',                           'kode' => 'PIP'],
        ];

        foreach ($data as $row) {
            Division::updateOrCreate(['kode' => $row['kode']], $row);
        }
    }

    /**
     * periode_hari APAR = 30 mengikuti NFPA 10 (inspeksi bulanan).
     * Permenaker 4/1980 hanya mewajibkan 6 dan 12 bulan, jadi kolom ini bisa diubah admin.
     */
    private function tipeObjek(): void
    {
        $data = [
            ['slug' => 'apar',    'nama' => 'APAR',            'is_mobile' => false, 'periode_hari' => 30,  'urut' => 1],
            ['slug' => 'hydrant', 'nama' => 'Hydrant',         'is_mobile' => false, 'periode_hari' => 30,  'urut' => 2],
            ['slug' => 'p3k',     'nama' => 'Kotak P3K',       'is_mobile' => false, 'periode_hari' => 30,  'urut' => 3],
            ['slug' => 'damkar',  'nama' => 'Mobil Pemadam',   'is_mobile' => true,  'periode_hari' => 7,   'urut' => 4],
        ];

        foreach ($data as $row) {
            AssetType::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }

    /**
     * Checklist APAR 10 item, turunan Lampiran 03 PT PAL.
     * Aturan dimensi: objek yang dioperasikan -> answer_type fungsi (centang/silang).
     * Objek yang hanya dilihat -> answer_type kondisi (O / O-bergaris).
     * APAR tidak dioperasikan saat inspeksi, jadi mayoritas kondisi.
     */
    private function checklistApar(): void
    {
        $apar = AssetType::where('slug', 'apar')->firstOrFail();

        $struktur = [
            ['kode' => 'A', 'nama' => 'Isi & Tekanan', 'urut' => 1, 'items' => [
                [
                    'label'            => 'Volume isi tabung',
                    'answer_type'      => 'select',
                    'options'          => ['Penuh', 'Tidak penuh'],
                    // Bagian 6.3: volume tidak penuh = berat, tabung diturunkan dari layanan.
                    'severity_map'     => ['Tidak penuh' => 'berat'],
                    'severity_default' => 'berat',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)a',
                    'keterangan'       => 'Bagian 6.2: dipisah dari tekanan karena keduanya hal berbeda dan dapat berbeda hasil.',
                ],
                [
                    'label'            => 'Tekanan manometer',
                    'answer_type'      => 'select',
                    'options'          => ['Zona hijau', 'Kurang', 'Berlebih', 'Tanpa manometer'],
                    // Di luar zona hijau = berat. "Tanpa manometer" bukan kerusakan:
                    // APAR CO2 memang tidak bermanometer, ditimbang bukan dibaca jarum.
                    'severity_map'     => ['Kurang' => 'berat', 'Berlebih' => 'berat'],
                    'severity_default' => 'berat',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)a',
                ],
                [
                    'label'            => 'Segel dan mekanik penembus',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'sedang',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)a',
                ],
            ]],
            ['kode' => 'B', 'nama' => 'Kondisi Fisik', 'urut' => 2, 'items' => [
                [
                    'label'            => 'Badan tabung',
                    'answer_type'      => 'select',
                    'options'          => ['Mulus', 'Berkarat ringan', 'Berkarat berat', 'Penyok', 'Harus diganti'],
                    // Tingkat karat dipisah supaya temuan dapat diperingkat (Bagian 6.2).
                    'severity_map'     => [
                        'Berkarat ringan' => 'ringan',
                        'Berkarat berat'  => 'sedang',
                        'Penyok'          => 'sedang',
                        'Harus diganti'   => 'berat',
                    ],
                    'severity_default' => 'sedang',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)b',
                ],
                [
                    'label'            => 'Handle dan tuas',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'sedang',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)b',
                ],
                [
                    'label'            => 'Label dan petunjuk pakai',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'ringan',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)b',
                ],
            ]],
            ['kode' => 'C', 'nama' => 'Saluran Pancar', 'urut' => 3, 'items' => [
                [
                    'label'            => 'Mulut pancar tidak tersumbat',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'berat',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)c',
                ],
                [
                    'label'            => 'Pipa atau selang pancar tidak retak',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'sedang',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 12(1)c',
                ],
            ]],
            ['kode' => 'D', 'nama' => 'Penempatan', 'urut' => 4, 'items' => [
                [
                    'label'            => 'Keterjangkauan dan ketinggian',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'ringan',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 4-8 (tinggi 120 cm, jarak antar-APAR maks 15 m)',
                ],
                [
                    'label'            => 'Tanda pemasangan dan kartu kontrol',
                    'answer_type'      => 'boolean',
                    'severity_default' => 'ringan',
                    'dasar_hukum'      => 'Permenaker 4/1980 Pasal 4(1); NFPA 10',
                ],
            ]],
        ];

        $urutGlobal = 0;

        foreach ($struktur as $g) {
            $group = ChecklistGroup::updateOrCreate(
                ['asset_type_id' => $apar->id, 'kode' => $g['kode']],
                ['nama' => $g['nama'], 'urut' => $g['urut']],
            );

            foreach ($g['items'] as $item) {
                $urutGlobal++;

                /**
                 * Dikunci pada (group, urut), BUKAN pada label.
                 *
                 * Label item bisa berubah saat rancangan direvisi, misalnya
                 * "Mulut pancar" menjadi "Mulut pancar tidak tersumbat". Kalau
                 * dikunci pada label, seed ulang akan membuat item kedua dan
                 * checklist berisi 12 baris untuk 10 pemeriksaan. Nomor urut
                 * item inilah yang stabil, karena dia mengikuti Lampiran 03.
                 */
                ChecklistItem::updateOrCreate(
                    ['checklist_group_id' => $group->id, 'urut' => $urutGlobal],
                    [
                        'label'            => $item['label'],
                        'answer_type'      => $item['answer_type'],
                        'options'          => $item['options'] ?? null,
                        'severity_map'     => $item['severity_map'] ?? null,
                        'severity_default' => $item['severity_default'],
                        'dasar_hukum'      => $item['dasar_hukum'],
                        'keterangan'       => $item['keterangan'] ?? null,
                        'wajib'            => true,
                        'aktif'            => true,
                    ],
                );
            }
        }

        $this->bersihkanItemUsang($apar->id, $urutGlobal);
    }

    /**
     * Buang item sisa seed versi lama yang urutnya di luar rentang sekarang,
     * termasuk duplikat berlabel lama dari seed sebelum penguncian urut.
     * Item yang sudah punya jawaban tersimpan tidak dihapus, hanya dinonaktifkan,
     * supaya inspeksi yang sudah berjalan tidak kehilangan riwayatnya.
     */
    private function bersihkanItemUsang(int $assetTypeId, int $urutMaks): void
    {
        $groupIds = ChecklistGroup::where('asset_type_id', $assetTypeId)->pluck('id');

        $usang = ChecklistItem::whereIn('checklist_group_id', $groupIds)
            ->where('urut', '>', $urutMaks)
            ->get();

        foreach ($usang as $item) {
            if ($item->answers()->exists()) {
                $item->update(['aktif' => false]);

                continue;
            }

            $item->delete();
        }
    }
}

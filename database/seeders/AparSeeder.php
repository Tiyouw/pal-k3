<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Division;
use Illuminate\Database\Seeder;
use RuntimeException;

class AparSeeder extends Seeder
{
    /** Berkas sumber: kode,tipe,kapasitas_kg,gedung,lantai,lokasi_teks,lat,lng,radius_m */
    private const CSV = 'database/seed/pal_seed_apar_35.csv';

    public function run(): void
    {
        $path = base_path(self::CSV);

        if (! is_readable($path)) {
            throw new RuntimeException("Berkas seed tidak terbaca: {$path}");
        }

        $apar = AssetType::where('slug', 'apar')->firstOrFail();
        $pip  = Division::where('kode', 'PIP')->first();

        $fh = fopen($path, 'r');

        // CSV dibuat di Windows/Sheets -> CRLF. Header dibersihkan dari BOM dan \r.
        $header = fgetcsv($fh);
        $header = array_map(
            fn ($h) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)),
            $header ?: []
        );

        /**
         * qr_token TIDAK ada di CSV dan tidak boleh ditambahkan.
         *
         * Token adalah rahasia: dia satu-satunya gerbang penentu aset di
         * InspeksiController::mulai(). Kalau token ikut disimpan di berkas seed
         * yang masuk repo, siapa pun yang membaca repo bisa mengirim inspeksi
         * tanpa datang ke lokasi, dan premis QR sebagai bukti kehadiran fisik
         * runtuh. Token diterbitkan acak oleh Asset::booted() saat baris dibuat.
         */
        $wajib = ['kode', 'tipe', 'kapasitas_kg', 'gedung', 'lantai', 'lokasi_teks', 'lat', 'lng', 'radius_m'];
        $hilang = array_diff($wajib, $header);

        if ($hilang !== []) {
            throw new RuntimeException('Kolom CSV hilang: ' . implode(', ', $hilang));
        }

        $masuk = 0;

        while (($baris = fgetcsv($fh)) !== false) {
            if ($baris === [null] || $baris === []) {
                continue;
            }

            $row = array_combine($header, array_map(fn ($v) => trim((string) $v), $baris));

            if (($row['kode'] ?? '') === '') {
                continue;
            }

            $lokasiTipe = $this->lokasiTipe($row['gedung']);
            $radiusCsv  = (int) $row['radius_m'];
            $radiusJenis = Asset::RADIUS_PER_LOKASI[$lokasiTipe] ?? null;

            /**
             * radius_m adalah OVERRIDE per aset (Bagian 5.5), bukan tempat
             * menyalin nilai bawaan. Kalau radius di CSV sama dengan radius
             * jenis lokasinya, kolom dibiarkan kosong supaya perubahan
             * kebijakan radius cukup dilakukan di satu tempat dan langsung
             * berlaku untuk seluruh aset sejenis.
             */
            $override = ($radiusCsv > 0 && $radiusCsv !== $radiusJenis) ? $radiusCsv : null;

            Asset::updateOrCreate(
                ['asset_type_id' => $apar->id, 'kode' => $row['kode']],
                [
                    'division_id'  => $pip?->id,
                    'gedung'       => $row['gedung'],
                    'lantai'       => $row['lantai'],
                    'lokasi_teks'  => $row['lokasi_teks'],
                    'lokasi_tipe'  => $lokasiTipe,
                    'lat'          => (float) $row['lat'],
                    'lng'          => (float) $row['lng'],
                    'radius_m'     => $override,
                    'attributes'   => [
                        'tipe'         => $row['tipe'],
                        'kapasitas_kg' => $this->angka($row['kapasitas_kg']),
                    ],
                    'aktif'        => true,
                ],
            );

            $masuk++;
        }

        fclose($fh);

        $this->command?->info("APAR diseed: {$masuk}");
    }

    /** Sheets lokal ID menulis 4,5 bukan 4.5. */
    private function angka(string $v): float
    {
        return (float) str_replace(',', '.', $v);
    }

    /**
     * Bagian 5.5: jenis lokasi menentukan radius gerbang GPS.
     * Diturunkan dari nama gedung karena CSV data awal belum punya kolom sendiri.
     * Setelah data koordinat asli PT PAL diperoleh, kolom lokasi_tipe cukup
     * ditambahkan ke CSV dan pemetaan ini tidak dipakai lagi.
     */
    private function lokasiTipe(string $gedung): string
    {
        $g = mb_strtolower($gedung);

        return match (true) {
            str_contains($g, 'workshop'), str_contains($g, 'gudang'), str_contains($g, 'bengkel') => 'bengkel',
            str_contains($g, 'terbuka'),  str_contains($g, 'halaman'), str_contains($g, 'parkir') => 'area_terbuka',
            default => 'dalam_gedung',
        };
    }
}

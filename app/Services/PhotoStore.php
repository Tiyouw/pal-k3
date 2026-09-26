<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bagian 3.2 + 7.1 rancangan: foto wajib, diubah ukuran di peladen agar hemat ruang.
 *
 * Lebar maksimum 1600 piksel. Nomor tabung harus tetap terbaca pada ukuran itu,
 * sementara berkas 4 MB dari kamera ponsel turun ke ratusan kilobyte. Dengan 35
 * aset dikali 12 bulan, selisihnya menentukan apakah disk peladen cukup.
 *
 * Pengubahan ukuran memakai ekstensi GD yang sudah menyatu dengan PHP, bukan paket
 * tambahan: satu ketergantungan lebih sedikit untuk disiapkan saat penyebaran.
 */
class PhotoStore
{
    public const LEBAR_MAKS = 1600;
    public const MUTU_JPEG  = 82;
    public const DISK       = 'public';

    /** Tipe yang diterima. HEIC ditolak karena GD tidak bisa membacanya. */
    public const MIME_DIIZINKAN = ['image/jpeg', 'image/png', 'image/webp'];

    public function simpan(Inspection $inspection, UploadedFile $file): InspectionPhoto
    {
        $mime = $file->getMimeType();

        if (! in_array($mime, self::MIME_DIIZINKAN, true)) {
            throw new \RuntimeException(
                'Format foto tidak didukung (' . $mime . '). Gunakan kamera bawaan, bukan berkas HEIC.'
            );
        }

        $folder = 'inspeksi/' . $inspection->id;
        $nama   = Str::uuid() . '.jpg';
        $tujuan = $folder . '/' . $nama;

        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory($folder);

        $absolut = $disk->path($tujuan);
        $bytes   = $this->ubahUkuran($file->getRealPath(), $absolut, $mime);

        return $inspection->photos()->create([
            'path'     => $tujuan,
            'taken_at' => now(),
            'lat'      => $inspection->gps_lat,
            'lng'      => $inspection->gps_lng,
            'bytes'    => $bytes,
        ]);
    }

    /**
     * Kembalikan ukuran berkas hasil dalam byte.
     * Foto yang sudah lebih kecil dari batas tidak diperbesar, hanya dikonversi JPEG.
     */
    protected function ubahUkuran(string $sumber, string $tujuan, string $mime): int
    {
        $img = match ($mime) {
            'image/png'  => imagecreatefrompng($sumber),
            'image/webp' => imagecreatefromwebp($sumber),
            default      => imagecreatefromjpeg($sumber),
        };

        if ($img === false) {
            throw new \RuntimeException('Berkas foto tidak dapat dibaca.');
        }

        // Kamera ponsel menyimpan orientasi di EXIF, bukan pada piksel.
        // Tanpa koreksi ini foto potret tampil miring 90 derajat di laporan.
        $img = $this->koreksiOrientasi($img, $sumber, $mime);

        $lebarAsal  = imagesx($img);
        $tinggiAsal = imagesy($img);

        if ($lebarAsal > self::LEBAR_MAKS) {
            $lebar  = self::LEBAR_MAKS;
            $tinggi = (int) round($tinggiAsal * (self::LEBAR_MAKS / $lebarAsal));

            $kecil = imagecreatetruecolor($lebar, $tinggi);
            imagecopyresampled($kecil, $img, 0, 0, 0, 0, $lebar, $tinggi, $lebarAsal, $tinggiAsal);
            imagedestroy($img);
            $img = $kecil;
        }

        imagejpeg($img, $tujuan, self::MUTU_JPEG);
        imagedestroy($img);

        clearstatcache(true, $tujuan);

        return (int) filesize($tujuan);
    }

    protected function koreksiOrientasi(\GdImage $img, string $sumber, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $img;
        }

        $exif = @exif_read_data($sumber);
        $or   = $exif['Orientation'] ?? null;

        $derajat = match ($or) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($derajat === 0) {
            return $img;
        }

        $diputar = imagerotate($img, $derajat, 0);

        if ($diputar === false) {
            return $img;
        }

        imagedestroy($img);

        return $diputar;
    }

    public function hapus(InspectionPhoto $photo): void
    {
        Storage::disk(self::DISK)->delete($photo->path);
        $photo->delete();
    }
}

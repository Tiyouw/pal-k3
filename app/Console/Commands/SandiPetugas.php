<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Terbitkan kata sandi baru untuk satu petugas, atau untuk semua petugas.
 *
 * Alat ini ada karena repositori bersifat publik sehingga tidak ada satu pun
 * kata sandi yang boleh tertulis di dalam kode atau berkas data awal. Akibatnya
 * admin butuh jalan resmi untuk menerbitkan sandi tanpa membuka tinker dan
 * menempel potongan kode Hash::make secara manual.
 *
 * Sandi hanya ditampilkan sekali di layar. Nilai yang disimpan ke basis data
 * adalah hasil hash, jadi sandi asli tidak bisa dibaca ulang dari mana pun,
 * termasuk oleh admin. Kalau catatan hilang, terbitkan lagi.
 *
 * Contoh:
 *   php artisan petugas:sandi 2001
 *   php artisan petugas:sandi 1001 --sandi="kata sandi pilihan sendiri"
 *   php artisan petugas:sandi --semua
 */
class SandiPetugas extends Command
{
    protected $signature = 'petugas:sandi
                            {nip? : NIP petugas yang sandinya diganti}
                            {--semua : Ganti sandi seluruh petugas, satu sandi acak per orang}
                            {--sandi= : Tentukan sandi sendiri, hanya berlaku bila NIP diisi}';

    protected $description = 'Terbitkan kata sandi baru untuk petugas dan tampilkan sekali di layar';

    /** Panjang sandi acak. Tanpa simbol supaya aman disalin lewat WhatsApp dan diketik di ponsel. */
    private const PANJANG = 14;

    public function handle(): int
    {
        $nip    = $this->argument('nip');
        $semua  = (bool) $this->option('semua');
        $pilih  = (string) ($this->option('sandi') ?? '');

        if ($semua && $nip !== null) {
            $this->error('Pilih salah satu: NIP tertentu, atau --semua. Bukan keduanya.');

            return self::FAILURE;
        }

        if (! $semua && $nip === null) {
            $this->error('Sebutkan NIP, atau pakai --semua.');

            return self::FAILURE;
        }

        // --sandi pada mode --semua akan menyeragamkan sandi seluruh petugas.
        // Itu justru kelemahan yang sedang dihapus, jadi kombinasi ini ditolak.
        if ($semua && $pilih !== '') {
            $this->error('--sandi tidak berlaku bersama --semua. Sandi seragam untuk semua orang adalah risiko.');

            return self::FAILURE;
        }

        $daftar = $semua
            ? User::orderBy('nip')->get()
            : User::where('nip', $nip)->get();

        if ($daftar->isEmpty()) {
            $this->error($semua ? 'Belum ada petugas di basis data.' : "Petugas dengan NIP {$nip} tidak ditemukan.");

            return self::FAILURE;
        }

        $baris = [];

        foreach ($daftar as $user) {
            $sandi = $pilih !== '' ? $pilih : Str::password(self::PANJANG, symbols: false);

            // forceFill dipakai supaya penerbitan sandi tidak bergantung pada
            // daftar atribut yang boleh diisi massal; cast 'hashed' di model
            // User sudah menangani hashing, tetapi Hash::make ditulis eksplisit
            // agar perintah ini tetap benar kalau cast itu suatu saat diubah.
            $user->forceFill(['password' => Hash::make($sandi)])->save();

            $baris[] = [$user->nip, $user->name, User::LABEL_ROLE[$user->role] ?? $user->role, $sandi];
        }

        $this->newLine();
        $this->table(['NIP', 'Nama', 'Peran', 'Sandi baru'], $baris);
        $this->warn('Catat sekarang. Sandi di atas tidak disimpan dalam bentuk terbaca dan tidak bisa ditampilkan ulang.');
        $this->newLine();

        return self::SUCCESS;
    }
}

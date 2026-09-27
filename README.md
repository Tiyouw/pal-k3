# Inspeksi K3 APAR

Aplikasi pemeriksaan rutin Alat Pemadam Api Ringan (APAR). Petugas memindai
stiker QR yang tertempel di tabung, mengisi daftar periksa dari ponsel, lalu
hasilnya langsung terkumpul jadi laporan bulanan untuk bagian K3.

**Coba langsung:** [rifaldy.tiyoouw.app](https://rifaldy.tiyoouw.app)

---

## Untuk apa aplikasi ini

Pemeriksaan APAR biasanya dicatat di kartu kertas yang digantung di tabung.
Kartu itu gampang hilang, tulisannya sulit dibaca, dan tidak ada cara memastikan
petugas benar-benar datang ke lokasi. Rekapitulasi bulanan jadi pekerjaan
menyalin ulang dari puluhan kartu.

Aplikasi ini memindahkan catatan itu ke ponsel dengan tiga pengaman:

- **Stiker QR per tabung.** Tiap tabung punya kode acak sendiri. Formulir
  pemeriksaan hanya terbuka setelah stiker di tabung itu dipindai, jadi petugas
  tidak bisa mengisi dari rumah.
- **Pencocokan lokasi.** Ponsel mengirim titik GPS saat pengisian. Kalau jarak
  ke tabung melebihi batas wajar, hasilnya ditandai "perlu ditinjau" supaya bisa
  diperiksa atasan, bukan langsung ditolak.
- **Foto bukti.** Kondisi tabung difoto langsung dari formulir.

Temuan kerusakan otomatis masuk daftar tindak lanjut sampai dinyatakan selesai.

## Tiga jenis pengguna

| Pengguna | Alat | Yang bisa dilakukan |
| --- | --- | --- |
| Inspektur | Ponsel | Pindai QR, isi daftar periksa, lihat riwayat pemeriksaannya sendiri |
| Administrator | Komputer | Kelola data tabung dan petugas, cetak stiker, terbitkan laporan, tinjau hasil |
| Pemantau | Komputer | Hanya melihat papan pantau dan laporan, tidak bisa mengubah data |

## Alur pemakaian sehari-hari

1. Administrator memasukkan data tabung APAR, lalu mencetak lembar stiker QR
   dari menu **Lembar Stiker** dan menempelkannya di tiap tabung.
2. Petugas membuka aplikasi di ponsel, memindai stiker di tabung, mengisi
   sepuluh butir pemeriksaan, memotret kondisi tabung, lalu mengirim.
3. Butir yang bermasalah otomatis menjadi temuan dan masuk daftar tindak lanjut.
4. Akhir bulan, administrator membuka menu laporan dan mengunduh rekap PDF.

## Dasar penyusunan daftar periksa

Butir pemeriksaan tidak disusun sendiri, tapi mengikuti ketentuan yang berlaku:

- **Permenaker No. 04/MEN/1980** tentang syarat pemasangan dan pemeliharaan APAR
- **NFPA 10** untuk pemeriksaan berkala tiap 30 hari

Setiap butir menyimpan rujukan dasar hukumnya, sehingga laporan bisa
dipertanggungjawabkan saat audit.

---

## Catatan tentang data contoh

Repositori ini **tidak memuat data asli** siapa pun:

- Nama petugas pada data contoh adalah nama peran ("Inspektur Workshop"),
  bukan nama pegawai.
- Titik koordinat pada data contoh berada di wilayah Jember dan dipakai untuk
  uji coba lapangan. Titik itu bukan tata letak fasilitas industri mana pun.
- Kode QR dan kata sandi **tidak disimpan** di dalam repositori. Keduanya
  diterbitkan acak saat aplikasi dipasang. Alasannya, kode QR adalah satu-satunya
  penentu keaslian pemeriksaan; kalau kodenya bisa dibaca dari repositori,
  pemeriksaan bisa dipalsukan tanpa datang ke lokasi.

---

## Untuk yang ingin menjalankan sendiri

Butuh PHP 8.3, Composer, dan Node.js.

```bash
git clone https://github.com/Tiyouw/pal-k3.git
cd pal-k3
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed     # sandi awal tercetak sekali di layar, catat
php artisan serve
```

Buka `http://localhost:8000`. Masuk memakai **NIP**, bukan alamat surel. Data
contoh menyediakan NIP `1001` (administrator), `2001` (inspektur), dan `3001`
(pemantau).

Sandi awal ditampilkan sekali oleh perintah `migrate --seed`. Kalau catatannya
hilang, terbitkan yang baru:

```bash
php artisan petugas:sandi 2001      # satu petugas
php artisan petugas:sandi --semua   # semua petugas, sandi acak per orang
```

Jalankan pemeriksaan otomatis:

```bash
php artisan test
```

## Bangunan teknis

Laravel 13 dengan panel administrasi Filament 3, basis data SQLite, laporan PDF
memakai dompdf, dan kode QR memakai simple-qrcode. Antarmuka lapangan dibuat
terpisah dari panel administrasi karena dipakai satu tangan di lapangan, kadang
memakai sarung tangan kerja.

## Lisensi

MIT. Lihat berkas [LICENSE](LICENSE).

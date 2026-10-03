---
inclusion: always
---

# Inspeksi K3 APAR — Konteks Proyek

Aplikasi pemeriksaan rutin Alat Pemadam Api Ringan (APAR). Petugas memindai
stiker QR di tabung, mengisi daftar periksa dari ponsel, dan hasilnya terkumpul
jadi laporan bulanan untuk bagian K3. Versi live: `rifaldy.tiyoouw.app`.

## Tujuan dan tiga pengaman keaslian

Pemeriksaan harus bisa dibuktikan benar-benar dilakukan di lokasi. Tiga pengaman
ini adalah inti aplikasi, jangan dilemahkan tanpa pertimbangan:

- **Stiker QR per tabung** — tiap tabung punya kode acak sendiri. Formulir
  pemeriksaan hanya terbuka setelah stiker tabung itu dipindai.
- **Pencocokan lokasi (GPS)** — ponsel mengirim titik GPS saat pengisian. Jika
  jarak ke tabung melebihi batas wajar, hasil ditandai **"perlu ditinjau"**
  (bukan ditolak), supaya bisa diperiksa atasan.
- **Foto bukti** — kondisi tabung difoto langsung dari formulir.

Butir pemeriksaan yang bermasalah otomatis menjadi **temuan (Issue)** dan masuk
daftar tindak lanjut sampai dinyatakan selesai.

## Tiga peran pengguna

Login memakai **NIP**, bukan alamat surel.

| Peran | Alat | Wewenang |
| --- | --- | --- |
| Administrator | Komputer | Kelola data tabung & petugas, cetak stiker, terbitkan laporan, tinjau hasil |
| Inspektur | Ponsel | Pindai QR, isi daftar periksa, lihat riwayat pemeriksaannya sendiri |
| Pemantau | Komputer | Hanya melihat papan pantau & laporan — **tidak boleh mengubah data** |

NIP data contoh: `1001` (administrator), `2001` (inspektur), `3001` (pemantau).

## Tumpukan teknis

- **Laravel 13**, PHP 8.3, Composer, Node.js (Vite).
- **Filament 3** untuk panel administrasi (komputer).
- Antarmuka lapangan (ponsel) dibuat **terpisah** dari panel Filament karena
  dipakai satu tangan di lapangan, kadang bersarung tangan kerja — jaga
  pemisahan ini.
- Basis data **SQLite**.
- Laporan PDF memakai **dompdf**; kode QR memakai **simple-qrcode**.
- Trusted proxy localhost diaktifkan agar skema https dari Cloudflare Tunnel
  terbaca.
- Halaman publik (saat ini halaman depan) memakai **Tailwind v4 lewat Vite**:
  token desain di `@theme` pada `resources/css/app.css`, Inter di-host sendiri
  (`@fontsource-variable/inter`), layout `resources/views/layouts/situs.blade.php`,
  komponen `<x-situs.foto>`, JS vanilla di `resources/js/app.js`. Antarmuka
  lapangan dan panel belum dimigrasi ke fondasi ini.

## Peta direktori penting

- `app/Filament/Resources/` — resource panel: Asset, AssetType, ChecklistGroup,
  ChecklistItem, Division, Inspection, Issue.
- `app/Filament/Widgets/RingkasanKepatuhan.php` — widget ringkasan kepatuhan.
- `app/Filament/Concerns/HanyaAdminBolehMenulis.php` — trait pembatas tulis
  (pemantau hanya baca).
- `app/Http/Controllers/` — `LandingController`, `LaporanController`,
  `StikerController`, dan `Petugas/{Auth,Beranda,Inspeksi}Controller`.
- `app/Http/Middleware/` — `PastikanInspektur`, `PastikanPengelola`.
- `app/Console/Commands/SandiPetugas.php` — perintah `petugas:sandi`.
- `routes/` — definisi rute. `resources/views/` — tampilan (lapangan vs admin).
- `lang/` — berkas bahasa. `database/seeders/` — data awal & daftar periksa.
- `tests/` — pemeriksaan otomatis. `scripts/` dan `tests/qa-browser/` — smoke HTTP
  dan harness QA peramban.
- `public/media/pal/` — foto/video milik PT PAL Indonesia (Persero); sumbernya di
  `SUMBER.md`, **tidak** tercakup lisensi MIT repo.
- `docs/ui/<halaman>/` — tangkapan layar sebelum/sesudah perubahan UI.

## Dasar hukum daftar periksa

Butir pemeriksaan mengikuti **Permenaker No. 04/MEN/1980** dan **NFPA 10**
(pemeriksaan berkala tiap 30 hari). Setiap butir menyimpan rujukan dasar
hukumnya agar laporan bisa dipertanggungjawabkan saat audit — pertahankan
rujukan ini saat mengubah daftar periksa.

## Perintah umum

```bash
composer install
npm ci && npm run build           # wajib juga saat deploy: public/build di-gitignore
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # sandi awal tercetak sekali — catat
php artisan serve                 # http://localhost:8000
php artisan test                  # jalankan pemeriksaan otomatis

php artisan petugas:sandi 2001    # terbitkan ulang sandi satu petugas
php artisan petugas:sandi --semua # sandi acak baru untuk semua petugas
```

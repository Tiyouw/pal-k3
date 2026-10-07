# Sketsa redesain panel `/admin`: varian A2 dan D

Dokumen ini menjelaskan dua usulan tampilan dasbor panel admin. Ditulis supaya orang atau AI lain bisa paham konteksnya tanpa membaca riwayat obrolan.

Status: **sketsa statis, belum diimplementasikan**. Panel `/admin` yang sedang live masih tampilan Filament bawaan. Belum ada keputusan final A2 atau D. Rekomendasi sementara: A2, lalu peta mini D diletakkan di posisi kartu "Sebaran per area".

## Latar singkat sistem

- Aplikasi inspeksi K3 APAR (alat pemadam api ringan) PT PAL Indonesia, untuk skripsi. Stack: Laravel 13 + Filament v3. Arsitektur disiapkan untuk Hydrant/P3K/Damkar nanti.
- Alur petugas di `/petugas`:
  1. Pindai QR stiker unit (token acak).
  2. GPS diambil sebagai bukti area. Hasil GPS hanya ditandai, tidak memblokir inspeksi.
  3. Isi checklist 10 butir (mengacu Permenaker 4/1980).
  4. Foto wajib.
- Laporan bulanan memakai format PMS resmi dengan simbol ✓ / X / O / Ø.
- Peran di panel `/admin`:
  - **Admin K3**: baca dan tulis penuh. Bisa menerima/menolak posisi, menetapkan tenggat, menandai temuan selesai, dan mengelola Data Induk.
  - **Pemantau**: baca saja. Bisa melihat dan mengunduh laporan. Menu Data Induk dan tombol aksi disembunyikan atau dikunci.
- Masalah dasbor sekarang: isinya hanya angka ringkasan. Admin belum diberi tahu keputusan apa yang menunggu dia hari ini.

## Asal desain

Referensi user adalah dasbor SaaS bergaya "vault":
- sidebar gelap,
- 5 kartu KPI, kartu pertama gelap dengan aksen,
- grafik garis, peta ber-pin, donut, dan tabel.

Ada 3 varian awal:
- **A Konsol**: setia ke referensi.
- **B Peta**: 35 titik di denah.
- **C Antrean**: tugas lebih dulu, grafik belakangan.

User suka A karena ilustrasi APAR, warna sidebar, dan gaya kartunya. Hasilnya ada dua varian lanjutan:

| | A2 · Konsol Plus | D · Antrean + Peta Mini |
|---|---|---|
| Kerangka | A (KPI di atas, grafik, lalu baris aksi) | C (antrean di kiri, kolom ringkasan di kanan) |
| Warna | sidebar `#031e25`, aksen amber `#f5b700`, Plus Jakarta Sans | biru korporat `#0f3c68`, sidebar terang, Inter |
| Antrean keputusan | menggantikan tabel "Inspeksi terbaru" | blok utama halaman |
| Peta | tidak ada (diganti blok sebaran per area) | peta mini 35 titik di kolom kanan, klik untuk menyorot unit |
| Ilustrasi APAR | kartu KPI, kartu tenggat sidebar, foto temuan, thumbnail antrean | thumbnail antrean temuan |
| KPI | bisa diklik dan membuka tab antrean terkait | angka di kepala halaman |

## Keputusan desain dan alasannya

- **Antrean keputusan dengan 4 tab**: Temuan berat, Lewat tenggat, Tinjauan posisi, Belum diperiksa. Setiap butir punya aksi langsung, misalnya *Terima posisi*, *Minta ulang*, *Tetapkan tenggat*, *Tandai selesai*, *Ingatkan petugas*. Alasannya, dasbor seharusnya menjawab "apa yang harus saya putuskan sekarang", bukan cuma menampilkan statistik.
- **Tab default di D adalah Temuan berat**. Risiko keselamatan didahulukan di atas soal bukti posisi GPS.
- **Ilustrasi APAR ikut kondisi temuan** (`apar.js`, `aparUntuk()`):
  - manometer rusak: jarum merah,
  - segel hilang: segel putus,
  - selang/pipa bermasalah: selang retak,
  - karat: bercak karat.
  - Admin bisa menangkap jenis masalah sekilas sebelum membuka foto.
- **Bentuk titik peta berbeda per status**: wajik = tidak layak, cincin putus-putus = tinjau GPS, lingkaran berongga = belum diperiksa. Alasannya, peta tetap terbaca oleh pengguna buta warna.
- **Mode Pemantau mengubah tata letak**, bukan hanya mematikan tombol. Kartu laporan naik ke atas, dan aksi tulis (`.tulis-saja`) disembunyikan.
- **Aksen lime referensi diganti amber `#f5b700`** supaya tetap nyambung dengan tema Filament sekarang dan warna K3.
- **Tanpa mode gelap.** Panel dipakai di siang hari dan di layar LCD murah.
- **Teks Bahasa Indonesia, tanpa lorem ipsum**. Data mengikuti seed asli.

## Data dalam sketsa

`data.js` dibangun dari `database/seed/pal_seed_apar_35.csv`, yaitu 35 unit nyata:
- 01D–24D: Gedung PIP Lt. 1–3.
- 25D–30C: Workshop Fabrikasi Bay 1–6.
- 31D–35D: Area Terbuka.

Hasil inspeksinya **karangan**, karena produksi belum punya inspeksi nyata:
- kepatuhan 62,9% (22 dari 35 unit), 13 unit belum diperiksa,
- temuan berat: 14D dan 32D,
- lewat tenggat: 06D,
- tinjauan GPS: 07D, 12D, 30C,
- tenggat periode 30 Okt 2026.

Simbol penting di `data.js`:
- `UNITS`, `AREA`, `perArea()`, `GERBANG`, `BERAT`, `LEWAT`, `TREN`, `RINGKAS`,
- `LABEL_KES`, `LABEL_GERBANG`,
- `esc()` untuk escape HTML.

## Cara membuka

Buka langsung `sketsa/varian-a2-konsol-plus.html` atau `sketsa/varian-d-antrean-peta.html` di browser. Tidak perlu server atau build. Font diambil dari Google Fonts.

Tombol **Admin / Pemantau** di bilah atas mengganti peran. Tangkapan layar ada di `shots/`:
- meja 1440×900,
- ponsel 390×844,
- mode pemantau.

Struktur folder:

```
docs/ui/admin/
├── README.md        ← dokumen ini
├── data.js          ← data bersama (seed asli + hasil karangan)
├── apar.js          ← aparSVG() + aparUntuk(): ilustrasi APAR SVG
├── sketsa/          ← satu HTML per varian, memuat ../data.js dan ../apar.js
└── shots/           ← hasil render
```

## Catatan untuk implementasi ke Filament

Belum dikerjakan. Rencananya:

1. Tema kustom lewat `->viteTheme()` di `app/Providers/Filament/AdminPanelProvider.php`. Isinya warna sidebar, radius kartu, dan font.
2. Halaman Dasbor kustom berupa Filament Page yang menggantikan widget `app/Filament/Widgets/RingkasanKepatuhan.php`.
3. Antrean dibuat sebagai widget Livewire dengan tab. Aksi memakai Filament Action, dan aksi tulis dijaga policy, jadi bukan cuma disembunyikan di UI.
4. `apar.js` dipindah ke komponen Blade atau SVG.
5. Peta mini (kalau D atau gabungan dipilih) memakai lat/lng aset dari DB, bukan konstanta.
6. Semua angka antrean dihitung dari query. Request GET tidak boleh menulis ke DB.

Masalah kecil yang diketahui:
- Di peta D, titik Gedung PIP rapat. Sebaiknya ada zoom per lantai.
- Di ponsel D, tab antrean digeser horizontal di dalam wadahnya. Halaman sendiri tidak luber.

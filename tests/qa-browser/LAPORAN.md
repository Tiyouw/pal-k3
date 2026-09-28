# Laporan QA UI/UX — Inspeksi K3 APAR (pal-k3)

Sasaran: `https://rifaldy.tiyoouw.app` · 28 Sep 2026
Metode: Chromium 153 + Playwright, 3 peran, viewport ponsel 390×844 dan meja 1440×900.
42 tangkapan layar, hash dibandingkan agar tak ada bukti kembar yang menyesatkan.

Produksi utuh sesudah uji: 35 aset, 35 token unik, 9 petugas, 0 inspeksi.
Cadangan pra-uji: `~/cadangan/pal-sebelum-qa-1790562418.sqlite` di AWS.

## Ringkasan

| Keparahan | Jumlah |
|---|---|
| Kritis | 1 |
| Tinggi | 3 |
| Sedang | 6 |
| Rendah | 3 |

Satu temuan kritis bersifat keamanan, bukan kosmetik. Sisanya cacat penyelesaian
yang menjelaskan kenapa aplikasi "terasa aneh": bukan tata letaknya salah, tapi
banyak jejak mentah yang lolos ke layar pengguna.

---

## K-1 · KRITIS · Keamanan — Pemantau bisa mengubah dan menghapus data induk

Peran `pemantau` dirancang hanya membaca papan pantau dan laporan. Kenyataannya
ia bisa menulis.

Terbukti di server, bukan sekadar tombol yang tampil:

```
php artisan test --filter=OtorisasiPanelTest
⨯ pemantau tidak boleh menulis data aset
  Pemantau seharusnya TIDAK boleh mengubah aset.
  Failed asserting that true is false.
✓ pemantau masih boleh melihat daftar aset
✓ admin tetap boleh menulis data aset
```

`AssetResource::canEdit()` mengembalikan `true` untuk pemantau.

Akar masalah: tak ada `app/Policies` sama sekali, dan 6 dari 7 Resource tak punya
`canEdit` / `canCreate` / `canDelete`. Filament memakai bawaannya: izinkan semua.

| Resource | Aturan wewenang |
|---|---|
| InspectionResource | ada (`canCreate` → `false`) |
| AssetResource | **tidak ada** |
| AssetTypeResource | **tidak ada** |
| ChecklistGroupResource | **tidak ada** |
| ChecklistItemResource | **tidak ada** |
| DivisionResource | **tidak ada** |
| IssueResource | **tidak ada** |

Bukti dari browser sebagai pemantau (NIP 3001) di `/admin/assets/1/edit`:
18 kolom masukan, tombol `Simpan`, `Hapus`, `Hapus baris` semua tampil.

Catatan penting: aksi **"Ganti token"** justru sudah benar — dikunci
`->visible(fn () => auth()->user()?->isAdmin())` dan punya dialog konfirmasi
dengan peringatan jelas. Itu sebabnya pemantau tak melihatnya. Pola ini yang
seharusnya dipakai di seluruh resource.

MEDIA:/workspace/qa-pal-k3/shots/cek-pmt-edit-aset.png

---

## T-1 · TINGGI · Konten — `&middot;` bocor sebagai teks mentah

Entitas HTML tercetak apa adanya, bukan jadi titik pemisah `·`.

- Dasbor petugas: `NIP 2001 &middot; Petugas Inspeksi APAR`
- Lembar stiker: `APAR 31D Pos 1 &middot; Lt. -`

Yang terakhir lebih serius: teks ini ikut **tercetak di 35 stiker fisik** yang
ditempel di dinding. Sebabnya lolos ganda (`e`-escape atas string yang sudah
mengandung entitas).

MEDIA:/workspace/qa-pal-k3/shots/insp-06-sesudah-masuk.png

---

## T-2 · TINGGI · Konten — Label lantai dobel dan kosong

Dasbor petugas menampilkan:

```
GEDUNG PIP LT. LT. 1
AREA TERBUKA LT. - · 5
```

`LT.` muncul dua kali karena awalan ditambahkan padahal nilai kolom sudah
memuat `Lt. 1`. Untuk aset luar ruang yang tak punya lantai, hasilnya `LT. -`.

---

## T-3 · TINGGI · Konten — Angka mentah di dasbor admin

```
Sisa hari bulan ini: 2.8832669012153
```

Selisih tanggal tak dibulatkan. Staf K3 membaca "2,88 hari".

MEDIA:/workspace/qa-pal-k3/shots/adm-02-dasbor.png

---

## S-1 · SEDANG · UX — Panel admin masih beridentitas Laravel

- Judul tab: `Dasbor - Laravel`, `Aset - Laravel`, `Issue - Laravel`
- Footer: `v3.3.55 · Dokumentasi · GitHub` — tautan pengembang terlihat pengguna akhir
- Sidebar campur bahasa: `Asset Type`, `Checklist Group`, `Checklist Item`,
  `Division`, `Issue` berdampingan dengan `Kegiatan Inspeksi`, `Data Induk`
- Isi halaman ikut campur: `Daftar Issue`, `Buat issue`

Ini penyebab terbesar kesan "belum dijahit" di sisi meja kerja.

---

## S-2 · SEDANG · UX — Validasi login campur dua bahasa

Submit kolom kosong menghasilkan:

```
The NIP field is required.
The kata sandi field is required.
```

Kerangka pesan Inggris, nama kolom Indonesia. Bandingkan dengan pesan gagal
login yang sudah bagus: *"NIP atau kata sandi tidak cocok, atau akun sudah
tidak aktif."*

MEDIA:/workspace/qa-pal-k3/shots/insp-04-kolom-kosong.png

---

## S-3 · SEDANG · Visual — Tombol melayang menutupi isi

Tombol `Pindai QR` bersifat sticky tanpa ruang bawah pada kontainer, sehingga
chip terakhir (`30C`) tertutup. Daftar tampak terpotong.

---

## S-4 · SEDANG · Aksesibilitas — Chip kode tabung terlalu kecil dan pudar

Grid 5 kolom di layar 390px. Tinggi chip di bawah ~36px, teks abu-abu di latar
abu-abu muda. Berisiko gagal WCAG AA dan salah tekan, padahal dipakai sambil
berdiri satu tangan.

---

## S-5 · SEDANG · UX — Halaman 404 gundul

`/halaman-tidak-ada-xyz` → teks `404 Not Found` telanjang, tanpa jalan pulang.
`/laporan/kartu/99999` dan `/laporan/kartu/abc` juga jatuh ke sana.

Kontras: halaman stiker tak dikenali sudah sangat baik —
*"Kode pada stiker ini tidak terdaftar. Kemungkinan stiker sudah diganti atau
aset dinonaktifkan. Laporkan ke Admin K3 dengan menyebut lokasi tabung."*
plus dua tombol lanjutan. Standar itu belum merata.

MEDIA:/workspace/qa-pal-k3/shots/insp-15-404.png

---

## S-6 · SEDANG · UX — Halaman terputus satu sama lain

`/stiker` dan `/laporan` hidup di luar kerangka Filament. Dari `/admin` tak ada
tautan ke keduanya; `/laporan` punya tautan balik, `/stiker` tidak. Pengguna
harus mengetik URL.

---

## R-1 · RENDAH · UX — Aksi riwayat ganda

Ikon riwayat di pojok kanan atas dan tombol `Riwayat inspeksi saya` di bawah
menuju tempat sama. Pojok kanan atas sulit dijangkau jempol.

## R-2 · RENDAH · Konsol — 403 memicu error konsol

`/stiker` sebagai inspektur mencatat `Failed to load resource: 403`. Perilaku
gerbangnya benar, hanya bocor ke konsol.

## R-3 · RENDAH · Visual — Halaman 403 minimalis

`403 · Halaman ini hanya untuk Admin K3 dan Pemantau K3LH.` Pesannya tepat,
tapi tanpa tombol kembali.

---

## Yang sudah bagus (jangan diubah)

- Halaman depan menjelaskan aplikasi dengan bahasa manusia, bukan istilah teknis
- Pesan stiker tak dikenali: informatif dan menyebut tindakan lanjutan
- Riwayat kosong punya keadaan kosong yang benar, bukan tabel hampa
- Instruksi di `/stiker`: tempel di dinding bukan badan tabung, karena tabung
  bisa ditukar saat isi ulang — ini pemahaman domain yang matang
- Gerbang GPS ditolak tetap mengizinkan kirim, lalu ditandai untuk ditinjau
  Admin K3. Petugas tak terkunci di lapangan
- Checklist mengutip dasar hukum per butir (`Permenaker 4/1980 Pasal 12(1)a`)
- Kamera tak ada → langsung menawarkan kode cadangan
- Sandi lama `admin123` terbukti ditolak
- Halaman laporan cetak (`/laporan/pms`, `/laporan/temuan`, `/laporan/kartu/1`)
  **bukan rusak** — ketiganya PDF (terdeteksi `pdf_embedder.css`), 200 wajar

## Tak teruji

- Pemindaian QR dengan kamera sungguhan (headless tak punya kamera)
- Unggah foto bukti
- Gerbang GPS saat izin lokasi diberikan (uji ini selalu ditolak)
- Alur kirim checklist sampai tuntas — dihentikan agar tak mengotori produksi
- Hasil cetak fisik stiker di atas kertas label

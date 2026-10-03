---
inclusion: manual
---

# Arsitektur Domain & Risiko Diketahui

Rangkuman investigasi baca-saja pada 2 Okt 2026 (HEAD `517f3e8`). Panggil
berkas ini saat mengerjakan alur inspeksi, otorisasi, laporan, atau saat
memperbaiki risiko di bawah. Periksa ulang kodenya dulu: sebagian butir mungkin
sudah diperbaiki sejak itu.

## Model inti (`app/Models`)

- **Asset** — tabung/objek. Punya `qr_token` (`PAL-K3-<32hex>`, diisi otomatis
  di `booted()`, tak pernah dari CSV), `lokasi_tipe`, lat/lng/radius_m,
  `attributes` (JSON). Logika kunci: `evaluasiGerbang(lat,lng,accuracy)`,
  `jarakDari()` (haversine), `radiusEfektif()`. Konstanta: `RADIUS_BAWAAN_M=75`,
  `AKURASI_LEMAH_M=100`, `RADIUS_PER_LOKASI`.
- **Inspection** — bukti pemeriksaan. `status` = **`draf` / `final`**;
  `gate_status` ∈ `sesuai, perlu_review, jauh, gps_lemah, tanpa_gps,
  tidak_berlaku`. Tidak bisa dibuat/dihapus dari panel (bukti, bukan data CRUD).
- **InspectionAnswer** — jawaban per butir; simbol PMS ✓/X/O/Ø; `severity()`.
- **InspectionPhoto** — foto bukti (disk `public`); event `deleting` hapus berkas.
- **Issue** — temuan otomatis dari butir gagal; status terbuka/proses/selesai.
- **ChecklistGroup/ChecklistItem** — 10 butir APAR dalam 4 grup, tiap butir
  menyimpan `dasar_hukum` (Permenaker 4/1980, NFPA 10).
- **Schedule** — jatuh tempo per aset per bulan; `pastikanAda()`, `tandaiSelesai()`.
- **User** — peran DB bernilai **`admin`** (bukan `administrator`), `pemantau`,
  `inspektur`. `ROLE_PANEL=[admin,pemantau]`. Login lapangan via NIP.

## Alur inspeksi lapangan (`InspeksiController`)

`mulai($token)` → `firstOrCreate` draf → `isi` → GPS (POST `lokasi`,
`evaluasiGerbang`, **tak pernah menolak** — hanya menandai) → `simpanJawaban`
(auto-save) → `unggahFoto` → `kirim` (validasi: semua butir wajib terisi, min 1
foto, temuan berat ⇒ kesimpulan ≠ layak, ada temuan ⇒ rekomendasi wajib; lalu
transaksi: status `final`, `IssueGenerator::bangun()`, update `terakhir_dicek`,
`Schedule`).

## Prinsip desain yang tidak boleh dilanggar

- QR = penentu keaslian aset. GPS = **bukti**, bukan penolak kiriman.
- Waktu yang dipercaya adalah waktu peladen (`inspected_at = now()` saat kirim).
- Inspeksi adalah bukti: tidak dibuat/dihapus dari panel; pemantau hanya baca.

## Risiko diketahui (belum diperbaiki — konfirmasi dulu ke pengguna)

**Tinggi**
- **R1** `lokasi()` tidak menolak inspeksi `final` → GPS bisa ditimpa setelah
  kirim. Perlu guard `status==='final'` → 422 + tes. (`InspeksiController::lokasi`)
- **R2** `hapusFoto()` juga tidak menolak inspeksi `final` → foto bukti bisa
  dihapus setelah kirim. (`InspeksiController::hapusFoto`)
- **R3 (operasional)** Sandi awal lama dan `qr_token` lama masih terbaca di
  riwayat git sebelum `fbc9ae3`. Ganti token aset yang pernah di-seed dengan
  data lama, dan pastikan sandi lama itu tidak dipakai di produksi. Jangan
  menulis ulang nilai sandi atau token itu di berkas mana pun.

**Sedang**
- **R4** `/i/{token}` hanya ber-middleware `auth` → admin/pemantau yang memindai
  membuat draf yatim atas nama mereka.
- **R5** Definisi "perlu tinjauan" tak konsisten: widget `RingkasanKepatuhan`
  memasukkan `gps_lemah`, tapi antrean/lencana/aksi hanya `[perlu_review, jauh]`
  → angka widget tak pernah turun.
- **R6** Pesan UI saat izin lokasi ditolak menjanjikan akan ditinjau Admin,
  padahal `tanpa_gps` tidak masuk antrean. (`petugas/isi.blade.php`)
- **R7** `Schedule::tandaiSelesai` menandai "telat" untuk inspeksi pada hari
  jatuh tempo (bandingkan dengan `endOfDay`).
- **R8** Seeder menulis `attributes.tipe`, tapi `Asset::labelMedia()` membaca
  `jenis` → media selalu tampil "APAR" alih-alih "Dry Powder".
- **R9** Tidak ada `UserResource` meski README menyebut admin mengelola petugas.
- **R10** `UserSeeder` memakai satu sandi acak sama untuk semua akun baru.

**Rendah / Info**
- **R11** `ChecklistItemResource` & `IssueResource` masih scaffold mentah
  (TextInput untuk FK/enum).
- **R12** Filter `tanpa_titik` `orWhereNull` tanpa pengelompokan.
- **R13** SQL khas SQLite (`strftime`, `date('now',…)`) menghambat pindah DB.
- **R14** Default DB `inspections.status='draft'` vs kode `'draf'`; dua migrasi
  prefiks `100007`; `config/app.php` locale default `en`.
- **R15** Login Filament pakai surel, tapi `lang/id/auth.php` menyebut "NIP";
  komentar menyebut Policy yang tak ada (penerapan lewat trait).
- **R16** Pemantau bisa membuka `/stiker` dan melihat semua QR.
- **R17** GPS diambil sekali saat halaman dibuka, bukan saat kirim.
- **R18** Sisa skeleton: `welcome.blade.php` tak terpakai, `tests/Unit/ExampleTest`
  hanya `true`, AGENTS.md/CLAUDE.md hanya bootstrap Boost, tanpa CI/scheduler.
  (Vite/Tailwind kini dipakai halaman depan.)

## Celah cakupan tes

Belum ada tes untuk: `kirim` (aturan bisnis + transaksi), `simpanJawaban`,
foto, `IssueGenerator`, `Schedule`, `batal`, 403 lintas petugas, rate-limit
login, keluaran PDF `LaporanController`, `petugas:sandi`.

## Menjalankan tes

`vendor/` belum tentu terpasang. `composer install && php artisan test`
(phpunit.xml memakai SQLite in-memory; tidak perlu `.env` atau `npm`).
`tests/TestCase.php` memanggil `withoutVite()`, jadi tes tidak butuh `public/build`.

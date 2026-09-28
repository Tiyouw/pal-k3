# Harness QA peramban

Skrip di direktori ini menjalankan aplikasi yang sudah terpasang lewat Chromium
sungguhan, bukan lewat test HTTP. Keduanya saling melengkapi dan keduanya perlu:

`php artisan test` memeriksa perilaku sisi peladen. Harness ini memeriksa apa
yang benar-benar sampai ke layar petugas.

## Kenapa ini ada

Dua kelas galat lolos dari suite PHPUnit dan hanya tertangkap di sini:

1. **Hijau palsu dari `actingAs()`.** `HalamanGalatTest` semula memakai
   `actingAs()`, yang menyuntik pengguna langsung ke container dan melewati
   sesi. Test lulus, sementara di situs hidup petugas yang sudah masuk tetap
   disuguhi tombol "Masuk dengan NIP" di halaman 404. Sebabnya: alamat yang tak
   cocok rute mana pun tidak pernah melewati middleware `web`, jadi sesi belum
   dibaca. Perbaikannya `Route::fallback()` di akhir `routes/web.php`.

2. **Jejak mentah di layar.** `&middot;` yang tercetak sebagai teks, `LT. LT. 1`,
   `Sisa hari bulan ini: 2.8832669012153`. Semuanya lolos assertion karena
   halamannya memang membalas 200.

Harness juga mencatat MD5 setiap tangkapan dan melaporkan tangkapan kembar.
Sebelumnya pernah dihasilkan 8 PNG dengan hash identik — memotret halaman yang
sama berulang tanpa sadar, lalu ditarik kesimpulan dari bukti palsu.

## Rahasia

Skrip TIDAK memuat kredensial. Sandi dan token QR dibaca dari berkas di luar
repo, karena repo ini publik: kalau ikut ter-commit, keduanya terbit permanen di
riwayat git dan pemulihannya berarti `git filter-repo` plus mencetak ulang
seluruh stiker fisik.

```bash
cp tests/qa-browser/rahasia.contoh.env ~/.pal-k3-qa.env
chmod 600 ~/.pal-k3-qa.env
$EDITOR ~/.pal-k3-qa.env
```

Jalur lain bisa ditunjuk lewat `QA_PAL_RAHASIA`. Variabel lingkungan `QA_*`
menang atas isi berkas.

## Menjalankan

```bash
pip install playwright && playwright install chromium
cd tests/qa-browser

python3 verifikasi_tautan.py      # tautan silang halaman pengelolaan
python3 verifikasi_perbaikan.py   # 10 pemeriksaan lintas tiga peran
python3 ukur_ui.py                # ukur kontras WCAG, kotak sentuh, luber
python3 run_inspektur.py          # jelajah lane inspektur di ukuran ponsel
python3 run_admin_pemantau.py     # jelajah lane admin dan pemantau
```

Berkas `verifikasi_*.py` keluar dengan kode bukan-nol kalau ada pemeriksaan
gagal, jadi aman dipakai sebagai gerbang penyebaran.

## Yang sengaja tidak di-commit

`shots/` dan `hasil_*.json` tinggal di luar repo. Tangkapan `/stiker` merender
35 kode QR asli dan berkas JSON memuat token `PAL-K3-*` dalam bentuk teks.
Menerbitkannya sama dengan menerbitkan kunci masuk setiap tabung.

## Pantangan

- Jangan klik **Ganti token stiker QR** di panel. Tombol itu mematikan seluruh
  stiker fisik yang sudah tertempel di lapangan.
- Jangan menghapus baris dari basis data produksi.
- Proses latar belakang tidak mewarisi `LD_LIBRARY_PATH`. Jalankan dengan
  `env -u LD_LIBRARY_PATH python3 ...` bila Chromium gagal memuat
  `libglib-2.0.so.0`.

---
inclusion: always
---

# Konvensi Proyek

## Bahasa

- **Seluruh pesan commit, nama berkas peran, label antarmuka, pesan sistem,
  dan dokumentasi memakai bahasa Indonesia.** Jangan mencampur bahasa Inggris
  pada teks yang dilihat pengguna.
- Nama kelas/metode PHP mengikuti kelaziman Laravel/Filament (Inggris, PascalCase/
  camelCase) **kecuali** sudah ada pola berbahasa Indonesia di repo
  (mis. `PastikanInspektur`, `HanyaAdminBolehMenulis`, `SandiPetugas`,
  `petugas:sandi`). Ikuti pola yang sudah ada di sekitarnya, jangan buat gaya baru.

## Pesan commit

Gaya **imperatif berbahasa Indonesia**, satu baris ringkas yang menjelaskan
tujuan perubahan. Contoh dari riwayat:

- `Beranda tampilkan urgensi tenggat: terlewat, mepet, dan sisa hari`
- `Tutup jalur tulis panel untuk pemantau`
- `Halaman galat berbahasa Indonesia dengan jalan pulang per peran`

## Keamanan & data

- **Jangan pernah menaruh kode QR atau sandi di dalam repositori.** Keduanya
  diterbitkan acak saat seeding/instalasi. Kode QR adalah satu-satunya penentu
  keaslian pemeriksaan.
- Data contoh tidak boleh memakai data asli: nama memakai nama peran
  (mis. "Inspektur Workshop"), koordinat memakai titik uji di wilayah Jember.
- Jangan menghapus atau melonggarkan tiga pengaman keaslian (QR, GPS, foto)
  tanpa alasan jelas dari pengguna.

## Wewenang peran

- Pemantau **hanya baca**. Setiap resource/jalur tulis baru di panel harus
  menghormati batas ini (lihat `HanyaAdminBolehMenulis`).
- Jaga pemisahan antara antarmuka lapangan (ponsel, di luar Filament) dan panel
  administrasi (Filament, komputer).

## Perubahan UI

- Ikuti sistem desain di `.kiro/steering/design.md` (otomatis terpakai untuk
  berkas di `resources/**`). Tanpa emoji dan tanpa ikon dekoratif.
- Setiap perubahan UI disertai tangkapan layar sebelum/sesudah (desktop 1440 px
  dan ponsel 390 px) di `docs/ui/<halaman>/`, dan tangkapan itu **ditunjukkan
  langsung ke pemilik repo di percakapan** saat hasil dilaporkan — jangan hanya
  menautkan PR.

## Pengujian

- Tambahkan/suaikan uji di `tests/` saat mengubah perilaku, terutama batas
  wewenang peran dan alur inspeksi. Jalankan `php artisan test` sebelum selesai.

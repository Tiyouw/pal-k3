# Rencana setelah integrasi UI

## Rilis demonstrasi APAR

- Gabungkan `pipeline/ui-admin` ke `main` dan gunakan satu versi kode yang sama di server.
- Sediakan akun demo admin, pemantau, dan inspektur yang mudah dicoba stakeholder.
- Pastikan admin dapat mengelola petugas, aset, stiker, tinjauan, temuan, dan laporan.
- Pastikan pemantau dapat membaca dashboard dan laporan tanpa mengubah data.
- Uji alur inspektur: login, QR, GPS, jawaban, foto, kirim, riwayat, dan laporan.
- Lindungi foto dan koordinat inspeksi final dari perubahan.
- Samakan antrean tinjauan, perbaiki penentuan tenggat, dan verifikasi hasil PDF.
- Backup database serta unggahan sebelum deploy; pertahankan token QR yang ada.
- Verifikasi URL HTTPS live dan login ketiga peran dengan sesi browser sungguhan.

## Pekerjaan berikutnya

1. **Uji penerimaan stakeholder.** Jalankan skenario satu APAR baik dan satu APAR
   bermasalah dari ponsel. Cocokkan nama menu, istilah, laporan PMS, dan alur
   tindak lanjut bersama pengelola. Hasilnya menjadi daftar perbaikan prioritas.
2. **Siapkan uji lapangan.** Masukkan penempatan serta koordinat sebenarnya,
   cetak stiker, uji GPS di dalam gedung, dan pastikan foto tetap terbaca pada
   jaringan lemah. Gunakan akun individu saat mulai mengumpulkan data nyata.
3. **Operasional rutin.** Jadwalkan backup database dan foto, pemulihan cadangan,
   pengingat jatuh tempo, pemantauan galat, serta pemeriksaan otomatis saat perubahan kode.
4. **Perluas modul secara berurutan.** Mulai dari Hydrant, lalu P3K, dan Mobil
   Pemadam. Setiap modul perlu daftar periksa, aset contoh, pengujian aturan
   pemeriksaan, dan laporan yang disetujui sebelum ditampilkan sebagai aktif.

Prioritas setelah demo: uji penerimaan alur APAR yang sudah berjalan, kemudian
uji lapangan. Modul baru mengikuti hasil kedua tahap tersebut.

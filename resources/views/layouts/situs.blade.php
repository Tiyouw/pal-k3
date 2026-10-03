<!DOCTYPE html>
{{--
    Layout halaman publik dengan sistem desain Arc (Tailwind v4 lewat Vite,
    token di resources/css/app.css). Antarmuka lapangan tetap memakai
    layouts/petugas, panel memakai Filament.

    Bagian yang bisa diisi:
      @section('judul')      judul tab, diakhiri "— PT PAL Indonesia"
      @section('deskripsi')  meta description
      @push('kepala')        tambahan <head>, mis. preload gambar hero
      @section('navigasi')   kepala situs, di luar <main>
      @section('isi')        isi utama, di dalam <main id="isi">
      @section('kaki')       kaki situs, di luar <main>
      @push('skrip')         skrip tambahan di akhir <body>
--}}
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#031e25">
    <meta name="description" content="@yield('deskripsi', 'Sistem inspeksi K3 Divisi K3LH PT PAL Indonesia.')">
    {{-- Halaman publik tidak boleh terindeks: isinya keterangan internal
         perusahaan, meski tanpa angka aset. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('judul', 'Inspeksi K3') &mdash; PT PAL Indonesia</title>

    {{-- Kelas js dipasang sebelum CSS dimuat supaya elemen reveal tidak
         berkedip. Kelas ini menyembunyikan isi [data-muncul] sampai app.js
         membukanya, membuat kepala situs fixed, dan memunculkan tombol menu.
         Gagal-terbuka: bila app.js tidak menandai siap (data-situs-siap) dalam
         ±3 detik — gagal dimuat, diblokir, atau pemasangnya melempar galat —
         kelas dicabut lagi dan halaman kembali ke tata letak tanpa JavaScript:
         semua isi tampil dan kepala situs ikut tergulir di atas hero. --}}
    <script>
        (function (akar) {
            akar.classList.add('js');
            setTimeout(function () {
                if (!akar.dataset.situsSiap) akar.classList.remove('js');
            }, 3000);
        })(document.documentElement);
    </script>

    @stack('kepala')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#isi" class="label-meta fixed left-4 top-4 z-[60] -translate-y-24 rounded-md bg-paper px-4 py-3 text-charcoal focus-visible:translate-y-0">
        Langsung ke isi
    </a>

    @yield('navigasi')

    <main id="isi">
        @yield('isi')
    </main>

    @yield('kaki')

    @stack('skrip')
</body>
</html>

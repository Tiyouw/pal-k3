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

    {{-- Ditandai sebelum CSS dimuat supaya elemen reveal tidak berkedip.
         Tanpa JavaScript, kelas ini tidak ada dan semua isi langsung tampil. --}}
    <script>document.documentElement.classList.add('js')</script>

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

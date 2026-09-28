{{--
    Rangka halaman galat.

    Sengaja berdiri sendiri, tidak memakai layouts/petugas, karena layout itu
    menyiapkan bilah kepala dan menu yang mengandalkan pengguna yang sedang
    masuk. Halaman galat justru sering muncul ketika sesi habis, sehingga
    layout tersebut bisa gagal di tengah penanganan galat dan menghasilkan
    galat baru.

    Yang dijamin ada di sini: satu penjelasan dalam bahasa manusia dan satu
    tombol pulang yang menyesuaikan peran, supaya petugas di lapangan tak
    terjebak di halaman buntu sambil memegang tabung.
--}}
@php
    $pengguna = auth()->user();

    // Tujuan pulang mengikuti peran: inspektur kembali ke daftar tugasnya,
    // pengelola ke panel, tamu ke halaman masuk.
    if ($pengguna?->isInspektur()) {
        $tujuan = route('petugas.beranda');
        $labelTujuan = 'Kembali ke daftar tugas';
    } elseif ($pengguna?->bolehPanel()) {
        $tujuan = url('/admin');
        $labelTujuan = 'Kembali ke panel';
    } else {
        $tujuan = route('petugas.masuk');
        $labelTujuan = 'Masuk dengan NIP';
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} &mdash; PT PAL Indonesia</title>
    <style>
        *, *::before, *::after { box-sizing: border-box }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #0f172a;
            color: #e2e8f0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.55;
        }
        .kotak {
            width: 100%;
            max-width: 460px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 28px 24px;
        }
        .kode {
            font-size: .78rem;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 6px;
        }
        h1 { font-size: 1.32rem; margin: 0 0 10px; color: #f8fafc }
        p { margin: 0 0 18px; color: #cbd5e1 }
        .saran { margin: 0 0 22px; padding-left: 20px; color: #cbd5e1; font-size: .94rem }
        .saran li { margin-bottom: 6px }
        /* Tinggi 48px: tombol tetap bisa ditekan dengan sarung tangan kerja. */
        .tombol {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 18px;
            border-radius: 10px;
            background: #f59e0c;
            color: #1c1917;
            font-weight: 600;
            text-decoration: none;
        }
        .tombol.sekunder {
            background: transparent;
            border: 1px solid #475569;
            color: #e2e8f0;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <main class="kotak">
        <p class="kode">Galat {{ $kode }}</p>
        <h1>{{ $judul }}</h1>
        <p>{{ $pesan }}</p>

        @isset($saran)
            <ul class="saran">
                @foreach ($saran as $butir)
                    <li>{{ $butir }}</li>
                @endforeach
            </ul>
        @endisset

        <a class="tombol" href="{{ $tujuan }}">{{ $labelTujuan }}</a>

        @if ($pengguna?->isInspektur())
            <a class="tombol sekunder" href="{{ route('petugas.pindai') }}">Buka pemindai QR</a>
        @endif
    </main>
</body>
</html>

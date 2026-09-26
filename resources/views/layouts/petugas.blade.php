<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    {{--
        viewport-fit=cover + user-scalable tetap diizinkan.
        Pembesaran TIDAK dimatikan meski ini antarmuka lapangan: petugas berusia
        lanjut perlu memperbesar label, dan mematikannya melanggar WCAG 1.4.4.
    --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f3c68">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Inspeksi K3') &mdash; PT PAL Indonesia</title>

    <style>
        /*
         * Gaya ditulis langsung tanpa proses bangun aset.
         *
         * Alasan: peladen produksi hanya menjalankan PHP, dan setiap tambahan
         * langkah npm menambah titik gagal saat penyebaran. Berkas ini cukup
         * kecil untuk dimuat sekali dan disimpan cache peramban.
         */
        :root {
            --biru:      #0f3c68;  /* biru korporat PT PAL */
            --biru-tua:  #0a2a49;
            --biru-muda: #e8f1f9;
            --hijau:     #157347;
            --hijau-md:  #e6f4ec;
            --kuning:    #9a6700;
            --kuning-md: #fff6e0;
            --merah:     #b02a37;
            --merah-md:  #fbeaec;
            --abu:       #5c636a;
            --abu-md:    #e9ecef;
            --abu-terang:#f6f7f9;
            --garis:     #d7dbe0;
            --radius:    14px;
            /* Tombol aksi utama 56 px: dioperasikan dengan sarung tangan kerja
               (Bagian 7.2). Angka ini di atas anjuran 44 px WCAG 2.5.5. */
            --sentuh:    56px;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        html, body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;   /* jangan diturunkan: 16 px mencegah iOS memperbesar saat isian difokus */
            line-height: 1.5;
            color: #1b1f23;
            background: var(--abu-terang);
        }

        body { padding-bottom: env(safe-area-inset-bottom); }

        .bingkai { max-width: 560px; margin: 0 auto; padding: 0 16px 96px; }

        /* ---------- kepala halaman ---------- */
        .kepala {
            position: sticky; top: 0; z-index: 20;
            background: var(--biru); color: #fff;
            padding: calc(env(safe-area-inset-top) + 12px) 16px 12px;
            display: flex; align-items: center; gap: 12px;
        }
        .kepala h1 { font-size: 1.05rem; margin: 0; font-weight: 600; letter-spacing: .2px; }
        .kepala .sub { font-size: .8rem; opacity: .85; }
        .kepala a, .kepala button {
            color: #fff; background: rgba(255,255,255,.14); border: 0;
            border-radius: 10px; min-width: 44px; min-height: 44px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1rem; cursor: pointer; text-decoration: none; padding: 0 12px;
        }

        /* ---------- kartu ---------- */
        .kartu {
            background: #fff; border: 1px solid var(--garis);
            border-radius: var(--radius); padding: 16px; margin-top: 12px;
        }
        .kartu h2 { font-size: .95rem; margin: 0 0 10px; color: var(--biru-tua); }

        /* ---------- tombol ---------- */
        .tbl {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: var(--sentuh); padding: 0 20px; width: 100%;
            border: 0; border-radius: 12px; font-size: 1.05rem; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: filter .15s;
        }
        .tbl:active { filter: brightness(.92); }
        .tbl[disabled] { opacity: .5; cursor: not-allowed; }
        .tbl-utama  { background: var(--biru); color: #fff; }
        .tbl-hijau  { background: var(--hijau); color: #fff; }
        .tbl-garis  { background: #fff; color: var(--biru); border: 2px solid var(--biru); }
        .tbl-abu    { background: var(--abu-md); color: #1b1f23; }
        .tbl-kecil  { min-height: 44px; font-size: .9rem; padding: 0 14px; width: auto; }

        /* ---------- lencana ---------- */
        .lencana {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 999px;
            font-size: .78rem; font-weight: 600;
        }
        .l-hijau  { background: var(--hijau-md); color: var(--hijau); }
        .l-kuning { background: var(--kuning-md); color: var(--kuning); }
        .l-merah  { background: var(--merah-md); color: var(--merah); }
        .l-abu    { background: var(--abu-md); color: var(--abu); }
        .l-biru   { background: var(--biru-muda); color: var(--biru); }

        /* ---------- isian ---------- */
        label.judul { display: block; font-weight: 600; font-size: .92rem; margin-bottom: 6px; }
        input[type=text], input[type=password], input[type=number], textarea, select {
            width: 100%; min-height: 52px; padding: 12px 14px;
            border: 1.5px solid var(--garis); border-radius: 10px;
            font-size: 1rem; font-family: inherit; background: #fff;
        }
        textarea { min-height: 88px; resize: vertical; }
        input:focus, textarea:focus, select:focus {
            outline: 3px solid rgba(15,60,104,.35); outline-offset: 1px; border-color: var(--biru);
        }
        .galat { color: var(--merah); font-size: .85rem; margin-top: 6px; }

        /* ---------- pesan ---------- */
        .pesan { border-radius: 12px; padding: 12px 14px; margin-top: 12px; font-size: .9rem; }
        .p-hijau  { background: var(--hijau-md); color: #0b4a2e; border: 1px solid #b7dfc8; }
        .p-kuning { background: var(--kuning-md); color: #6b4700; border: 1px solid #f0d79a; }
        .p-merah  { background: var(--merah-md); color: #7a1c26; border: 1px solid #efc2c8; }
        .p-biru   { background: var(--biru-muda); color: var(--biru-tua); border: 1px solid #bcd7ec; }

        .baris { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .redup { color: var(--abu); font-size: .85rem; }
        .mono  { font-variant-numeric: tabular-nums; }

        /* ---------- palang bawah tetap ---------- */
        .palang {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 30;
            background: rgba(255,255,255,.97); border-top: 1px solid var(--garis);
            padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
            backdrop-filter: blur(8px);
        }
        .palang .bingkai-dalam { max-width: 560px; margin: 0 auto; }

        /* Pengguna yang meminta gerak dikurangi tidak dipaksa melihat animasi. */
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }

        /* Hanya terlihat pembaca layar, tetap terfokus lewat papan tombol. */
        .sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
        .lewati:focus {
            position: static; width: auto; height: auto; clip: auto;
            display: block; padding: 10px; background: #fff; color: var(--biru);
        }
    </style>
    @stack('gaya')
</head>
<body>
    <a href="#utama" class="sr lewati">Lewati ke isi utama</a>

    @include('petugas.partials.kepala')

    <main id="utama" class="bingkai">
        @if (session('pesan'))
            <div class="pesan p-hijau" role="status">{{ session('pesan') }}</div>
        @endif

        @if (session('galat'))
            <div class="pesan p-merah" role="alert">{{ session('galat') }}</div>
        @endif

        @yield('isi')
    </main>

    @yield('palang')

    <script>
        // Token CSRF dipakai semua permintaan asinkron di halaman turunan.
        window.CSRF = document.querySelector('meta[name=csrf-token]').content;
    </script>
    @stack('skrip')
</body>
</html>

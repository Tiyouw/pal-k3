<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f3c68">
    <meta name="description" content="Sistem informasi inspeksi sarana proteksi kebakaran dan pertolongan pertama Divisi K3LH PT PAL Indonesia.">
    {{-- Halaman publik tidak boleh terindeks: isinya keterangan internal
         perusahaan, meski tanpa angka aset. --}}
    <meta name="robots" content="noindex, nofollow">
    <title>Sistem Inspeksi K3 &mdash; PT PAL Indonesia</title>

    <style>
        :root {
            --biru: #0f3c68; --biru-tua: #0a2a49; --biru-muda: #e8f1f9;
            --abu: #5c636a; --abu-terang: #f6f7f9; --garis: #d7dbe0;
            --hijau: #157347; --hijau-md: #e6f4ec;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; font-size: 16px; line-height: 1.6; color: #1b1f23;
            background: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .bingkai { max-width: 1040px; margin: 0 auto; padding: 0 20px; }

        header {
            background: linear-gradient(160deg, var(--biru-tua), var(--biru) 60%, #16558f);
            color: #fff;
        }
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 0; gap: 16px;
        }
        .merek { display: flex; align-items: center; gap: 10px; font-weight: 700; letter-spacing: .3px; }
        .merek span.kotak {
            width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,.16);
            display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem;
        }
        .tbl {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 48px; padding: 0 22px; border-radius: 10px; font-weight: 600;
            text-decoration: none; border: 0; cursor: pointer; font-size: 1rem;
        }
        .tbl-putih { background: #fff; color: var(--biru); }
        .tbl-garis { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.55); }
        .tbl-biru  { background: var(--biru); color: #fff; }

        .jumbo { padding: 40px 0 64px; }
        .jumbo h1 { font-size: clamp(1.7rem, 5vw, 2.6rem); line-height: 1.2; margin: 0 0 14px; max-width: 22ch; }
        .jumbo p { font-size: clamp(1rem, 2.4vw, 1.12rem); opacity: .92; max-width: 58ch; margin: 0 0 26px; }
        .aksi { display: flex; flex-wrap: wrap; gap: 12px; }

        .pilar { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 44px; }
        .pilar div {
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
            border-radius: 14px; padding: 16px;
        }
        .pilar strong { display: block; font-size: .95rem; margin-bottom: 4px; }
        .pilar p { margin: 0; font-size: .86rem; opacity: .85; }

        section { padding: 56px 0; }
        section.abu { background: var(--abu-terang); }
        h2.judul { font-size: clamp(1.3rem, 3.4vw, 1.75rem); margin: 0 0 8px; color: var(--biru-tua); }
        p.sub { color: var(--abu); margin: 0 0 28px; max-width: 62ch; }

        .modul { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; }
        .kartu {
            background: #fff; border: 1px solid var(--garis); border-radius: 16px;
            padding: 22px; display: flex; flex-direction: column;
        }
        .kartu .ikon {
            width: 46px; height: 46px; border-radius: 12px; background: var(--biru-muda);
            color: var(--biru); display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 14px;
        }
        .kartu h3 { margin: 0 0 6px; font-size: 1.05rem; color: var(--biru-tua); }
        .kartu p  { margin: 0 0 14px; font-size: .9rem; color: var(--abu); flex: 1; }

        .lencana {
            display: inline-flex; align-items: center; gap: 6px; align-self: flex-start;
            padding: 4px 10px; border-radius: 999px; font-size: .76rem; font-weight: 600;
        }
        .l-hijau { background: var(--hijau-md); color: var(--hijau); }
        .l-abu   { background: #e9ecef; color: var(--abu); }
        .l-biru  { background: var(--biru-muda); color: var(--biru); }

        .langkah { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; counter-reset: n; }
        .langkah article { position: relative; padding-top: 44px; }
        .langkah article::before {
            counter-increment: n; content: counter(n);
            position: absolute; top: 0; left: 0; width: 34px; height: 34px;
            border-radius: 50%; background: var(--biru); color: #fff; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .langkah h3 { margin: 0 0 4px; font-size: 1rem; color: var(--biru-tua); }
        .langkah p  { margin: 0; font-size: .88rem; color: var(--abu); }

        .dasar {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px; margin-top: 8px;
        }
        .dasar li {
            list-style: none; background: #fff; border: 1px solid var(--garis);
            border-radius: 12px; padding: 14px; font-size: .88rem;
        }
        .dasar li strong { display: block; color: var(--biru-tua); margin-bottom: 2px; }
        ul.dasar { padding: 0; margin: 0; }

        footer { background: var(--biru-tua); color: rgba(255,255,255,.82); padding: 28px 0; font-size: .86rem; }
        footer a { color: #fff; }

        @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
    </style>
</head>
<body>
    <header>
        <div class="bingkai">
            <nav class="nav" aria-label="Utama">
                <span class="merek">
                    <span class="kotak" aria-hidden="true">&#128680;</span>
                    <span>Inspeksi K3 <span style="font-weight:400; opacity:.8">&middot; PT PAL Indonesia</span></span>
                </span>
                <a href="{{ $tujuan }}" class="tbl tbl-putih">
                    {{ $masuk ? 'Buka aplikasi' : 'Masuk petugas' }}
                </a>
            </nav>

            <div class="jumbo">
                <h1>Inspeksi sarana K3 yang terbukti dilakukan di tempatnya</h1>
                <p>
                    Aplikasi ini mencatat pemeriksaan berkala sarana proteksi kebakaran dan
                    pertolongan pertama di lingkungan perusahaan. Setiap pemeriksaan disertai
                    pemindaian stiker QR pada objek, koordinat lokasi, dan foto bukti, lalu
                    dirangkum menjadi laporan bulanan bagi Divisi K3LH.
                </p>

                <div class="aksi">
                    <a href="{{ $tujuan }}" class="tbl tbl-putih">
                        {{ $masuk ? 'Lanjutkan pekerjaan' : 'Masuk sebagai petugas' }}
                    </a>
                    <a href="#modul" class="tbl tbl-garis">Lihat cakupan modul</a>
                </div>

                <div class="pilar">
                    <div>
                        <strong>Pemindaian di lokasi</strong>
                        <p>Pengisian hanya terbuka setelah stiker QR pada objek dipindai.</p>
                    </div>
                    <div>
                        <strong>Koordinat dan foto</strong>
                        <p>Posisi dan foto tersimpan sebagai lampiran bukti pemeriksaan.</p>
                    </div>
                    <div>
                        <strong>Temuan otomatis</strong>
                        <p>Butir yang dinilai tidak baik langsung menjadi daftar tindak lanjut.</p>
                    </div>
                    <div>
                        <strong>Laporan siap tanda tangan</strong>
                        <p>Rekapitulasi bulanan mengikuti format laporan resmi perusahaan.</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="modul" class="abu">
        <div class="bingkai">
            <h2 class="judul">Objek yang diinspeksi</h2>
            <p class="sub">
                Daftar modul dibaca langsung dari data induk aplikasi, sehingga penambahan
                objek pemeriksaan berikutnya tidak memerlukan perubahan halaman ini.
            </p>

            <div class="modul">
                @forelse ($modul as $m)
                    <article class="kartu">
                        <span class="ikon" aria-hidden="true">{{ $m['ikon'] }}</span>
                        <h3>{{ $m['nama'] }}</h3>
                        <p>{{ $m['ringkas'] }}</p>
                        <span class="lencana {{ $m['siap'] ? 'l-hijau' : 'l-abu' }}">
                            {{ $m['siap'] ? 'Sudah beroperasi' : 'Tahap pengembangan' }}
                            &middot; {{ $m['periode'] }}
                        </span>
                    </article>
                @empty
                    <p class="sub">Data induk objek inspeksi belum terisi.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section>
        <div class="bingkai">
            <h2 class="judul">Alur pemeriksaan di lapangan</h2>
            <p class="sub">Satu putaran pemeriksaan satu objek memerlukan waktu sekitar dua sampai tiga menit.</p>

            <div class="langkah">
                <article>
                    <h3>Masuk dengan NIP</h3>
                    <p>Petugas membuka aplikasi dari peramban ponsel, tanpa pemasangan aplikasi.</p>
                </article>
                <article>
                    <h3>Pindai stiker QR</h3>
                    <p>Stiker pada dinding di atas dudukan objek menentukan aset mana yang diperiksa.</p>
                </article>
                <article>
                    <h3>Isi daftar periksa</h3>
                    <p>Butir pemeriksaan ditampilkan satu kelompok per layar dan tersimpan otomatis.</p>
                </article>
                <article>
                    <h3>Unggah foto dan kirim</h3>
                    <p>Hasil beserta temuan dan rekomendasi masuk ke rekapitulasi bulanan.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="abu">
        <div class="bingkai">
            <h2 class="judul">Dasar hukum yang dipakai</h2>
            <p class="sub">Butir daftar periksa disusun mengikuti ketentuan berikut.</p>

            <ul class="dasar">
                <li>
                    <strong>Permenaker No. 4 Tahun 1980</strong>
                    Syarat pemasangan dan pemeliharaan alat pemadam api ringan, termasuk
                    ketinggian pemasangan dan jarak jangkauan antar alat.
                </li>
                <li>
                    <strong>Permenakertrans No. 15 Tahun 2008</strong>
                    Pertolongan pertama pada kecelakaan di tempat kerja beserta jumlah baku
                    isi kotak pertolongan pertama.
                </li>
                <li>
                    <strong>NFPA 10</strong>
                    Rujukan periode pemeriksaan bulanan dan tata cara pencatatan pada kartu kontrol.
                </li>
            </ul>
        </div>
    </section>

    <footer>
        <div class="bingkai" style="display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between">
            <span>Divisi K3LH &mdash; PT PAL Indonesia (Persero), Surabaya.</span>
            <span>Akses terbatas bagi petugas berwenang. <a href="{{ $tujuan }}">Masuk</a></span>
        </div>
    </footer>
</body>
</html>

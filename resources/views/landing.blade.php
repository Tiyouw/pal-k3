@extends('layouts.situs')

{{--
    Halaman depan publik. Terbuka tanpa masuk, jadi TIDAK memuat data aset:
    tanpa daftar tabung, koordinat, maupun token QR. Kartu modul berasal dari
    asset_types lewat LandingController.

    Foto dan video milik PT PAL Indonesia (Persero), di-host sendiri di
    public/media/pal (lihat SUMBER.md di sana).
--}}

@section('judul', 'Sistem Inspeksi K3')
@section('deskripsi', 'Sistem informasi inspeksi sarana proteksi kebakaran dan pertolongan pertama Divisi K3LH PT PAL Indonesia.')

@php
    $tautan = [
        '#pengaman'   => 'Pengaman',
        '#cara-kerja' => 'Cara kerja',
        '#peran'      => 'Peran',
        '#modul'      => 'Modul',
        '#tentang'    => 'Tentang PT PAL',
    ];
@endphp

@push('kepala')
    <link rel="preload" as="image" type="image/webp" fetchpriority="high" href="{{ asset('media/pal/hero-poster.webp') }}">
@endpush

@section('navigasi')
    <header
        data-nav
        data-masuk="pudar"
        class="group absolute inset-x-0 top-0 z-50 border-b border-transparent text-paper transition-[background-color,color,border-color] duration-300 ease-halus js:fixed data-tergulir:border-bone data-tergulir:bg-paper data-tergulir:text-charcoal data-menu-terbuka:border-bone data-menu-terbuka:bg-paper data-menu-terbuka:text-charcoal"
    >
        <div class="bingkai flex h-16 items-center justify-between gap-4 lg:h-18">
            <a href="{{ route('landing') }}" class="text-heading leading-none font-light tracking-[-0.021em]" aria-label="Inspeksi K3 APAR, halaman depan">
                inspeksi k3
            </a>

            <nav aria-label="Utama" class="hidden lg:block">
                <ul class="flex items-center gap-11">
                    @foreach ($tautan as $alamat => $label)
                        <li><a href="{{ $alamat }}" class="tautan text-label font-medium uppercase">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-2">
                <a
                    href="{{ $tujuan }}"
                    @class([
                        'tombol tombol-terang px-4 py-3.5 group-data-tergulir:tombol-gelap group-data-menu-terbuka:tombol-gelap',
                        'max-sm:hidden' => $masuk,
                    ])
                >{{ $masuk ? 'Buka aplikasi' : 'Masuk' }}</a>

                <button
                    type="button"
                    data-menu-tombol
                    aria-expanded="false"
                    aria-controls="menu-ponsel"
                    aria-label="Buka menu"
                    class="relative -mr-3 hidden size-11 items-center justify-center js:max-lg:flex"
                >
                    <span class="absolute h-px w-[18px] -translate-y-[3px] rounded-sm bg-current transition-transform duration-200 ease-halus group-data-menu-terbuka:translate-y-0 group-data-menu-terbuka:rotate-45"></span>
                    <span class="absolute h-px w-[18px] translate-y-[3px] rounded-sm bg-current transition-transform duration-200 ease-halus group-data-menu-terbuka:translate-y-0 group-data-menu-terbuka:-rotate-45"></span>
                </button>
            </div>
        </div>

        {{-- Dibatasi setinggi layar di bawah bilah 4rem dan bisa digulir sendiri,
             supaya tombol masuk di dasarnya terjangkau di ponsel landscape.
             overflow-y:auto ikut memotong outline fokus (2px + offset 3px = 5px)
             yang keluar dari kotak panel. pt-2 memberi ruang di atas tautan
             pertama; scroll-py-2 membuat gulir-otomatis saat fokus berhenti 8px
             sebelum tepi, bukan tepat di tepi elemen. Diukur oleh
             tests/qa-browser/verifikasi_landing.py. --}}
        <div id="menu-ponsel" data-menu-panel hidden class="max-h-[calc(100svh-4rem)] scroll-py-2 overflow-y-auto overscroll-contain border-t border-bone bg-paper text-charcoal lg:hidden">
            <nav aria-label="Menu ponsel" class="bingkai pt-2 pb-6">
                <ul>
                    @foreach ($tautan as $alamat => $label)
                        <li class="border-b border-bone">
                            <a href="{{ $alamat }}" class="flex min-h-14 items-center text-subheading font-light">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ $tujuan }}" class="tombol tombol-gelap mt-6 flex w-full px-6 py-[1.125rem]">
                    {{ $masuk ? 'Buka aplikasi' : 'Masuk petugas' }}
                </a>
            </nav>
        </div>
    </header>
@endsection

@section('isi')
    {{-- Hero: video latar penuh dengan judul di kiri bawah. --}}
    <section data-hero aria-labelledby="judul-hero" class="relative isolate flex min-h-svh items-end overflow-hidden bg-deep-current text-paper">
        {{-- Penanda gulir untuk pasangNav (resources/js/app.js): begitu 8px ini
             keluar layar, nav berubah solid supaya teks hero tidak bergulir di
             bawah nav yang transparan. --}}
        <div data-nav-penanda aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-2"></div>
        <div class="absolute inset-0 -z-10" data-masuk-media>
            <video
                data-video-latar
                autoplay
                muted
                loop
                playsinline
                preload="none"
                disablepictureinpicture
                disableremoteplayback
                aria-hidden="true"
                poster="{{ asset('media/pal/hero-poster.webp') }}"
                data-src-lebar="{{ asset('media/pal/hero-lebar.mp4') }}"
                data-src-tegak="{{ asset('media/pal/hero-tegak.mp4') }}"
                class="size-full object-cover"
            ></video>
            <div class="selubung-gelap absolute inset-0"></div>
        </div>

        <div class="bingkai pt-36 pb-10 md:pb-14">
            <p data-masuk style="--urutan: 1" class="label-meta text-paper">Divisi K3LH &middot; PT PAL Indonesia</p>
            <h1 id="judul-hero" data-masuk style="--urutan: 2" class="mt-5 max-w-[15ch] text-display font-light">
                Inspeksi APAR yang terbukti dilakukan di tempatnya.
            </h1>
            <p data-masuk style="--urutan: 3" class="mt-6 max-w-[36rem] text-body-lg text-paper">
                Stiker QR, titik lokasi, dan foto bukti menggantikan kartu periksa kertas yang gampang hilang.
            </p>
            <div data-masuk style="--urutan: 4" class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $tujuan }}" class="tombol tombol-terang px-6 py-[1.125rem]">
                    {{ $masuk ? 'Lanjutkan pekerjaan' : 'Masuk petugas' }}
                </a>
                <a href="#cara-kerja" class="tombol tombol-terang px-6 py-[1.125rem]">Cara kerja</a>
            </div>
        </div>

        {{-- Kendali video (WCAG 2.2.2), tombol ghost bergaya label. Tersembunyi
             sampai video benar-benar berputar, jadi tidak tampil tanpa JavaScript,
             saat gerak dikurangi, atau saat menghemat data. Kotaknya sama dengan
             tombol hero (teks sebaris). Di bawah 640px berada di pojok kanan atas
             supaya tidak menutupi tombol utama yang selebar layar. --}}
        <div class="pointer-events-none absolute inset-x-0 top-18 sm:top-auto sm:bottom-0">
            <div class="bingkai flex justify-end sm:pb-10 md:pb-14">
                <button type="button" data-video-kendali hidden class="tautan pointer-events-auto -mr-3 inline-flex items-center rounded-md border border-transparent px-3 py-[1.125rem] text-label font-medium whitespace-nowrap uppercase">Jeda video</button>
            </div>
        </div>
    </section>

    {{-- Pernyataan singkat di tengah, tanpa hiasan. --}}
    <div class="bg-paper py-pita">
        <div class="bingkai">
            <p data-muncul class="mx-auto max-w-[45rem] text-center text-heading-lg font-light">
                Setiap tabung tercatat. Setiap pemeriksaan bisa dibuktikan.
            </p>
        </div>
    </div>

    {{-- Tiga pengaman keaslian. --}}
    <section id="pengaman" aria-labelledby="judul-pengaman" class="bg-deep-current py-pita text-paper">
        <div class="bingkai">
            <div class="grid gap-6 md:grid-cols-12 md:items-end md:gap-x-5">
                <div class="md:col-span-7">
                    <p data-muncul class="label-meta text-paper/70">Tiga pengaman</p>
                    <h2 id="judul-pengaman" data-muncul class="mt-4 max-w-[20ch] text-heading-lg font-light">
                        Bukti bahwa petugas benar-benar datang ke tabung.
                    </h2>
                </div>
                <p data-muncul class="text-body text-paper/70 md:col-span-5">
                    Kartu periksa kertas yang digantung di tabung gampang hilang, tulisannya sulit
                    dibaca, dan tidak bisa memastikan petugas datang ke lokasi. Tiga pengaman ini
                    menggantikannya.
                </p>
            </div>

            <ul class="mt-12 grid gap-x-5 gap-y-10 md:mt-16 md:grid-cols-3">
                <li data-muncul style="--urutan: 0">
                    <div class="bingkai-foto aspect-[4/3] bg-paper/5">
                        <x-situs.foto
                            berkas="bengkel-pemeriksaan"
                            sizes="(min-width: 80rem) 387px, (min-width: 48rem) 31vw, 100vw"
                            alt="Dua pekerja berhelm keselamatan bekerja di bengkel PT PAL Indonesia."
                        />
                    </div>
                    <div class="p-5">
                        <h3 class="text-subheading">Stiker QR per tabung</h3>
                        <p class="mt-3 text-body text-paper/70">
                            Tiap tabung punya kode acak sendiri. Formulir pemeriksaan baru terbuka
                            setelah stiker di tabung itu dipindai.
                        </p>
                    </div>
                </li>
                <li data-muncul style="--urutan: 1">
                    <div class="bingkai-foto aspect-[4/3] bg-paper/5">
                        <x-situs.foto
                            berkas="galangan-udara"
                            sizes="(min-width: 80rem) 387px, (min-width: 48rem) 31vw, 100vw"
                            alt="Foto udara dermaga galangan PT PAL Indonesia dengan tongkang pembangkit listrik dan kapal tunda."
                        />
                    </div>
                    <div class="p-5">
                        <h3 class="text-subheading">Pencocokan lokasi</h3>
                        <p class="mt-3 text-body text-paper/70">
                            Ponsel mengirim titik GPS saat pengisian. Bila terlalu jauh dari tabung,
                            hasilnya ditandai perlu ditinjau, bukan ditolak.
                        </p>
                    </div>
                </li>
                <li data-muncul style="--urutan: 2">
                    <div class="bingkai-foto aspect-[4/3] bg-paper/5">
                        <x-situs.foto
                            berkas="bengkel-pengelasan"
                            sizes="(min-width: 80rem) 387px, (min-width: 48rem) 31vw, 100vw"
                            alt="Juru las berhelm keselamatan mengelas konstruksi baja di bengkel PT PAL Indonesia."
                        />
                    </div>
                    <div class="p-5">
                        <h3 class="text-subheading">Foto bukti</h3>
                        <p class="mt-3 text-body text-paper/70">
                            Kondisi tabung dipotret langsung dari formulir dan tersimpan bersama
                            hasil pemeriksaan.
                        </p>
                    </div>
                </li>
            </ul>

            <p data-muncul class="mt-10 border-t border-paper/15 pt-6 text-body text-paper/70 md:mt-14">
                Butir yang gagal otomatis menjadi temuan dan masuk daftar tindak lanjut sampai
                dinyatakan selesai.
            </p>
        </div>
    </section>

    {{-- Alur harian empat langkah. --}}
    <section id="cara-kerja" aria-labelledby="judul-cara-kerja" class="bg-paper py-pita">
        <div class="bingkai">
            <p data-muncul class="label-meta">Alur harian</p>
            <h2 id="judul-cara-kerja" data-muncul class="mt-4 max-w-[20ch] text-heading-lg font-light">
                Dari stiker sampai rekap bulanan.
            </h2>

            <ol class="mt-12 grid border-t border-bone md:mt-16 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Pasang stiker', 'Administrator memasukkan data tabung, mencetak stiker QR dari menu Lembar Stiker, lalu menempelkannya di tiap tabung.'],
                    ['Periksa di lokasi', 'Petugas memindai stiker dari ponsel, mengisi sepuluh butir pemeriksaan, memotret kondisi tabung, lalu mengirim.'],
                    ['Temuan ditindaklanjuti', 'Butir yang bermasalah otomatis menjadi temuan dan masuk daftar tindak lanjut sampai selesai.'],
                    ['Rekap bulanan', 'Akhir bulan, administrator membuka menu laporan dan mengunduh rekap PDF.'],
                ] as [$judul, $uraian])
                    <li data-muncul style="--urutan: {{ $loop->index }}" class="border-b border-bone py-8 md:odd:pr-5 md:even:border-l md:even:pl-5 lg:border-b-0 lg:border-l lg:px-5 lg:first:border-l-0 lg:first:pl-0 lg:last:pr-0">
                        <span aria-hidden="true" class="block text-heading font-light tabular-nums">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3 class="mt-8 text-subheading">{{ $judul }}</h3>
                        <p class="mt-3 text-body text-charcoal/70">{{ $uraian }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Tiga peran pengguna. --}}
    <section id="peran" aria-labelledby="judul-peran" class="bg-bone py-pita">
        <div class="bingkai grid gap-12 md:grid-cols-12 md:gap-x-5">
            <div data-muncul class="order-last md:order-first md:col-span-5">
                <div class="bingkai-foto aspect-[4/5] bg-paper">
                    <x-situs.foto
                        berkas="bengkel-pelat-baja"
                        :lebar="[600, 1000]"
                        sizes="(min-width: 80rem) 484px, (min-width: 48rem) 40vw, 100vw"
                        alt="Pekerja berhelm memandu pelat baja yang diangkat derek di bengkel fabrikasi PT PAL Indonesia."
                    />
                </div>
            </div>

            <div class="md:col-span-6 md:col-start-7 md:self-center">
                <p data-muncul class="label-meta">Tiga peran</p>
                <h2 id="judul-peran" data-muncul class="mt-4 text-heading-lg font-light">Satu sistem, tiga cara memakainya.</h2>

                <ul class="mt-10 grid gap-px">
                    @foreach ([
                        ['Inspektur', 'Ponsel', 'Memindai stiker QR, mengisi daftar periksa, dan melihat riwayat pemeriksaannya sendiri.'],
                        ['Administrator', 'Komputer', 'Mengelola data tabung dan petugas, mencetak stiker, menerbitkan laporan, dan meninjau hasil.'],
                        ['Pemantau', 'Komputer', 'Hanya melihat papan pantau dan laporan, tanpa bisa mengubah data.'],
                    ] as [$nama, $alat, $uraian])
                        <li data-muncul style="--urutan: {{ $loop->index }}" class="grid gap-3 bg-paper p-5 sm:grid-cols-[10rem_1fr] sm:gap-5">
                            <div>
                                <h3 class="text-subheading">{{ $nama }}</h3>
                                <p class="mt-2 label-meta text-charcoal/60">{{ $alat }}</p>
                            </div>
                            <p class="text-body text-charcoal/70">{{ $uraian }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Dasar hukum daftar periksa. --}}
    <section id="dasar-hukum" aria-labelledby="judul-dasar-hukum" class="bg-paper py-pita">
        <div class="bingkai grid gap-10 md:grid-cols-12 md:gap-x-5">
            <div class="md:col-span-5">
                <p data-muncul class="label-meta">Dasar hukum</p>
                <h2 id="judul-dasar-hukum" data-muncul class="mt-4 text-heading-lg font-light">Setiap butir menyimpan rujukannya.</h2>
                <p data-muncul class="mt-6 text-body text-charcoal/70">
                    Butir pemeriksaan tidak disusun sendiri, tetapi mengikuti ketentuan yang berlaku.
                    Rujukan dasar hukum tersimpan di tiap butir, sehingga laporan bisa
                    dipertanggungjawabkan saat audit.
                </p>
            </div>

            <ul class="border-t border-bone md:col-span-6 md:col-start-7 md:self-end">
                <li data-muncul class="border-b border-bone py-6">
                    <h3 class="text-subheading">Permenaker No. 04/MEN/1980</h3>
                    <p class="mt-2 text-body text-charcoal/70">Syarat pemasangan dan pemeliharaan alat pemadam api ringan.</p>
                </li>
                <li data-muncul style="--urutan: 1" class="border-b border-bone py-6">
                    <h3 class="text-subheading">NFPA 10</h3>
                    <p class="mt-2 text-body text-charcoal/70">Pemeriksaan berkala setiap 30 hari.</p>
                </li>
            </ul>
        </div>
    </section>

    {{-- Modul: dibaca dari data induk asset_types. --}}
    <section id="modul" aria-labelledby="judul-modul" class="bg-bone py-pita">
        <div class="bingkai">
            <div class="grid gap-6 md:grid-cols-12 md:items-end md:gap-x-5">
                <div class="md:col-span-7">
                    <p data-muncul class="label-meta">Modul</p>
                    <h2 id="judul-modul" data-muncul class="mt-4 text-heading-lg font-light">Objek yang diperiksa.</h2>
                </div>
                <p data-muncul class="text-body text-charcoal/70 md:col-span-5">
                    Daftar modul dibaca langsung dari data induk aplikasi, sehingga objek
                    pemeriksaan berikutnya cukup ditambahkan lewat data tanpa mengubah halaman ini.
                </p>
            </div>

            <ul class="mt-12 grid gap-px sm:grid-cols-2 md:mt-16 lg:grid-cols-4">
                @forelse ($modul as $m)
                    <li data-muncul style="--urutan: {{ $loop->index }}" class="flex flex-col bg-paper p-5">
                        <p @class(['label-meta', 'text-charcoal' => $m['siap'], 'text-charcoal/60' => ! $m['siap']])>
                            {{ $m['siap'] ? 'Sudah beroperasi' : 'Tahap berikutnya' }}
                        </p>
                        <h3 class="mt-10 text-subheading">{{ $m['nama'] }}</h3>
                        <p class="mt-3 flex-1 text-body-sm text-charcoal/70">{{ $m['ringkas'] }}</p>
                        <p class="mt-8 border-t border-bone pt-4 label-meta text-charcoal/60">Periode {{ $m['periode'] }}</p>
                    </li>
                @empty
                    <li class="bg-paper p-5 text-body text-charcoal/70 sm:col-span-2 lg:col-span-4">
                        Data induk objek inspeksi belum terisi.
                    </li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- Tentang PT PAL Indonesia: ringkasan dari pal.co.id. --}}
    <section id="tentang" aria-labelledby="judul-tentang" class="bg-slate-depth text-paper">
        <div class="relative isolate flex min-h-[70svh] items-end overflow-hidden">
            <div class="absolute inset-0 -z-10">
                <x-situs.foto
                    berkas="kri-semarang-udara"
                    sizes="100vw"
                    data-zoom-pelan
                    alt="KRI Semarang (594), kapal produksi PT PAL Indonesia, melaju di laut lepas dilihat dari udara."
                />
                <div class="selubung-gelap absolute inset-0"></div>
            </div>

            <div class="bingkai pt-40 pb-10 md:pb-14">
                <p data-muncul class="label-meta text-paper/80">Tentang PT PAL Indonesia</p>
                <h2 id="judul-tentang" data-muncul class="mt-4 max-w-[16ch] text-heading-lg font-light">
                    Galangan kapal terbesar di Indonesia.
                </h2>
            </div>
        </div>

        <div class="bingkai py-pita">
            <div class="grid gap-10 md:grid-cols-12 md:gap-x-5">
                <div class="space-y-5 text-body text-paper/70 md:col-span-6">
                    <p data-muncul>
                        Berkantor di Ujung, Surabaya, PT PAL Indonesia (Persero) membangun kapal perang,
                        kapal selam, dan kapal niaga; mengerjakan pemeliharaan, perbaikan, dan overhaul
                        kapal; serta membuat produk rekayasa umum untuk energi dan kelistrikan.
                    </p>
                    <p data-muncul>
                        Cikal bakalnya adalah Marine Establishment yang diresmikan pemerintah Belanda.
                        Setelah kemerdekaan, galangan ini dinasionalisasi menjadi Penataran Angkatan Laut
                        (PAL), dan sejak 15 April 1980 berstatus perseroan terbatas.
                    </p>
                </div>
                <div class="md:col-span-5 md:col-start-8">
                    <p data-muncul class="text-body text-paper/70">
                        Kesehatan dan keselamatan kerja selalu ditekankan dalam sistem manajemen
                        perusahaan.
                    </p>
                    <a data-muncul href="https://www.pal.co.id/" class="tombol tombol-terang mt-8 px-6 py-[1.125rem]">
                        Situs resmi PT PAL
                    </a>
                </div>
            </div>

            <dl class="mt-16 grid grid-cols-2 gap-x-5 gap-y-10 md:mt-24 lg:grid-cols-4">
                @foreach ([
                    ['329', 'Kapal diproduksi', '1985–2024'],
                    ['92', 'Di antaranya kapal perang', null],
                    ['47', 'Kapal diekspor', null],
                    ['120', 'Hektare area galangan', null],
                ] as [$angka, $keterangan, $rentang])
                    <div data-muncul style="--urutan: {{ $loop->index }}" class="flex flex-col-reverse border-t border-paper/15 pt-5">
                        <dt class="mt-3 text-body-sm text-paper/70">
                            {{ $keterangan }}@if ($rentang), <span class="whitespace-nowrap">{{ $rentang }}</span>@endif
                        </dt>
                        <dd class="text-display font-light proportional-nums">{{ $angka }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
@endsection

@section('kaki')
    <footer class="bg-paper">
        <div class="bingkai py-12 md:py-16">
            <div class="flex flex-col gap-10 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-heading leading-none font-light tracking-[-0.021em]">inspeksi k3</p>
                    <p class="mt-4 max-w-[28rem] text-body-sm text-charcoal/70">
                        Divisi K3LH &mdash; PT PAL Indonesia (Persero), Surabaya.
                        Akses terbatas bagi petugas berwenang.
                    </p>
                </div>

                <nav aria-label="Kaki halaman">
                    <ul class="flex flex-wrap gap-x-5 gap-y-3">
                        <li><a href="{{ $tujuan }}" class="tautan text-label font-medium uppercase">{{ $masuk ? 'Buka aplikasi' : 'Masuk petugas' }}</a></li>
                        <li><a href="#cara-kerja" class="tautan text-label font-medium uppercase">Cara kerja</a></li>
                        <li><a href="#modul" class="tautan text-label font-medium uppercase">Modul</a></li>
                        <li><a href="https://www.pal.co.id/" class="tautan text-label font-medium uppercase">pal.co.id</a></li>
                    </ul>
                </nav>
            </div>

            <p class="mt-12 border-t border-bone pt-6 text-body-sm text-charcoal/60">
                Foto dan video: dokumentasi PT PAL Indonesia (Persero).
            </p>
        </div>
    </footer>
@endsection

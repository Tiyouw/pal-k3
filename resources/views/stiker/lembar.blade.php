<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lembar Stiker QR &mdash; Inspeksi K3</title>

    <style>
        /* Ukuran label memakai milimeter, bukan piksel: hasil cetak harus pas
           di kertas label yang sudah dipotong pabrik. */
        :root {
            --lebar:  {{ $tata['lebar'] }}mm;
            --tinggi: {{ $tata['tinggi'] }}mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0; background: #e9ecef; color: #000;
            font-family: -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        /* ---------- panel penyaring, tidak dicetak ---------- */
        .panel {
            background: #fff; border-bottom: 1px solid #ccc; padding: 16px 20px;
            position: sticky; top: 0; z-index: 10;
        }
        .panel form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; max-width: 1100px; }
        .panel label { display: block; font-size: .78rem; font-weight: 600; margin-bottom: 4px; color: #495057; }
        .panel input, .panel select {
            min-height: 42px; padding: 8px 10px; border: 1px solid #ced4da;
            border-radius: 8px; font-size: .9rem; min-width: 150px;
        }
        .panel button {
            min-height: 42px; padding: 0 18px; border: 0; border-radius: 8px;
            background: #0f3c68; color: #fff; font-weight: 600; cursor: pointer; font-size: .9rem;
        }
        .panel .cetak { background: #157347; }
        .panel .ket { font-size: .82rem; color: #6c757d; margin: 10px 0 0; max-width: 70ch; }

        /* ---------- kepala navigasi, tidak dicetak ----------
           Halaman ini sebelumnya tidak punya satu pun tautan keluar, jadi begitu
           dibuka satu-satunya jalan kembali adalah tombol mundur peramban. */
        .kepala {
            background: #0f3c68; color: #fff; padding: 14px 20px;
            display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
            justify-content: space-between;
        }
        .kepala h1 { font-size: 1.05rem; margin: 0; font-weight: 600; }
        .kepala nav { display: flex; flex-wrap: wrap; gap: 16px; }
        .kepala a { color: #fff; font-size: .88rem; text-decoration: underline; }

        /* ---------- lembar ---------- */
        .lembar {
            width: 210mm; min-height: 297mm; background: #fff; margin: 16px auto;
            padding: 8mm 5mm; display: grid;
            grid-template-columns: repeat({{ $tata['kolom'] }}, var(--lebar));
            grid-auto-rows: var(--tinggi);
            justify-content: center; align-content: start;
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }

        .label {
            width: var(--lebar); height: var(--tinggi);
            display: flex; align-items: center; gap: 2mm;
            padding: 2mm; overflow: hidden;
            /* Garis putus hanya panduan potong di layar, dihilangkan saat cetak. */
            outline: 1px dashed #ced4da; outline-offset: -1px;
        }

        .label .qr { flex: 0 0 auto; line-height: 0; }
        .label .qr svg { display: block; width: {{ $tata['tinggi'] > 40 ? 26 : 20 }}mm; height: auto; }

        .label .teks { flex: 1; min-width: 0; }
        .label .kode {
            font-size: {{ $tata['tinggi'] > 40 ? 4.6 : 3.8 }}mm; font-weight: 800;
            letter-spacing: .2px; line-height: 1.1;
        }
        .label .jenis { font-size: 2.4mm; font-weight: 700; color: #0f3c68; text-transform: uppercase; letter-spacing: .3px; }
        .label .lokasi { font-size: 2.5mm; line-height: 1.25; margin-top: .8mm; }
        .label .perintah { font-size: 2.3mm; margin-top: 1mm; font-weight: 600; }

        /* Kode cadangan dicetak kecil di bawah QR. Tanpa ini stiker yang tergores
           atau berdebu jadi tidak berguna sama sekali, dan petugas harus menunggu
           stiker pengganti sebelum bisa menginspeksi tabung. */
        .label .token {
            font-family: ui-monospace, 'Courier New', monospace;
            font-size: 1.9mm; color: #495057; word-break: break-all; line-height: 1.2; margin-top: .6mm;
        }

        @media print {
            body { background: #fff; }
            /* Kepala navigasi ikut disembunyikan bersama panel penyaring: keduanya
               perkakas layar. Kalau tertinggal, lembar pertama kehilangan satu baris
               label karena terdorong turun. */
            .panel, .kepala { display: none; }
            .lembar {
                margin: 0; box-shadow: none; padding: 8mm 5mm;
                page-break-after: always; break-after: page;
            }
            .lembar:last-child { page-break-after: auto; break-after: auto; }
            .label { outline: none; }

            @page { size: A4 portrait; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="kepala">
        <h1>Lembar Stiker QR</h1>
        <nav aria-label="Pindah halaman pengelolaan">
            <a href="/admin">Panel admin</a>
            <a href="{{ route('laporan.index') }}">Laporan bulanan</a>
        </nav>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('stiker.index') }}">
            <div>
                <label for="tata">Tata letak label</label>
                <select name="tata" id="tata">
                    @foreach ($pilihan as $kode => $t)
                        <option value="{{ $kode }}" @selected($kodeTata === $kode)>{{ $t['nama'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tipe">Jenis objek</label>
                <select name="tipe" id="tipe">
                    <option value="">Semua</option>
                    @foreach ($tipe as $t)
                        <option value="{{ $t->slug }}" @selected(($filter['tipe'] ?? '') === $t->slug)>{{ $t->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="gedung">Gedung</label>
                <select name="gedung" id="gedung">
                    <option value="">Semua</option>
                    @foreach ($gedung as $g)
                        <option value="{{ $g }}" @selected(($filter['gedung'] ?? '') === $g)>{{ $g }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="lantai">Lantai</label>
                <input type="text" name="lantai" id="lantai" value="{{ $filter['lantai'] ?? '' }}" placeholder="mis. 2">
            </div>

            <div style="flex:1; min-width:200px">
                <label for="kode">Kode aset tertentu</label>
                <input type="text" name="kode" id="kode" value="{{ $filter['kode'] ?? '' }}"
                       placeholder="APAR-001, APAR-014" style="width:100%">
            </div>

            <button type="submit">Terapkan</button>
            <button type="button" class="cetak" onclick="window.print()">Cetak</button>
        </form>

        <p class="ket">
            {{ $aset->count() }} stiker pada {{ $halaman->count() }} lembar.
            Tempelkan stiker di dinding tepat di atas dudukan objek, bukan pada badan
            tabung: tabung dapat dipindahkan atau ditukar saat pengisian ulang, sedangkan
            titik pemasangan tetap. Pada dialog cetak, matikan penskalaan
            (pilih Ukuran asli atau 100%) agar ukuran label tidak bergeser.
        </p>
    </div>

    @forelse ($halaman as $isi)
        <div class="lembar">
            @foreach ($isi as $a)
                <div class="label">
                    <div class="qr">
                        {{--
                            errorCorrection M menoleransi sekitar 15 persen kerusakan
                            modul. Stiker di area bengkel terkena debu gerinda dan
                            percikan cat, jadi tingkat L yang lebih rapat tidak dipakai.
                        --}}
                        {!! QrCode::size(200)->margin(0)->errorCorrection('M')->generate(route('petugas.inspeksi.mulai', $a->qr_token)) !!}
                    </div>
                    <div class="teks">
                        <div class="jenis">{{ $a->assetType?->nama ?? 'Objek K3' }}</div>
                        <div class="kode">{{ $a->kode }}</div>
                        <div class="lokasi">
                            {{-- labelLokasi() dipakai karena entitas HTML di dalam {{ }}
                                 tercetak mentah sebagai "&middot;" di stiker fisik, dan
                                 kolom lantai sudah berawalan sendiri. --}}
                            {{ $a->labelLokasi() }}
                        </div>
                        <div class="perintah">Pindai sebelum memeriksa</div>
                        <div class="token">{{ $a->qr_token }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="lembar" style="display:block; padding:20mm">
            <p style="font-size:1rem">
                Tidak ada aset yang cocok dengan penyaring. Longgarkan penyaring lalu terapkan ulang.
            </p>
        </div>
    @endforelse
</body>
</html>

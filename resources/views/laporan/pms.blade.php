{{--
    Lembar PMS bulanan. Dirender oleh dompdf, bukan peramban modern:
    tata letak memakai tabel dan lebar persen, bukan flexbox atau grid,
    karena dompdf tidak mendukung keduanya.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Lembar PMS {{ $tipe->nama }} &mdash; {{ $namaBulan[$periode->month] }} {{ $periode->year }}</title>
    <style>
        @page { margin: 12mm 10mm 14mm; }

        body {
            font-family: DejaVu Sans, sans-serif;  /* wajib: simbol Ø butuh font ber-Unicode */
            font-size: 7.5pt; color: #000; margin: 0;
        }

        .kop { width: 100%; border-bottom: 1.5pt solid #000; padding-bottom: 4pt; margin-bottom: 8pt; }
        .kop td { vertical-align: middle; }
        .kop .nama { font-size: 11pt; font-weight: bold; }
        .kop .sub  { font-size: 8pt; }
        .kop .lampiran { text-align: right; font-size: 7.5pt; }

        h1 { font-size: 10pt; text-align: center; margin: 0 0 2pt; text-transform: uppercase; }
        .periode { text-align: center; font-size: 8pt; margin: 0 0 8pt; }

        table.matriks { width: 100%; border-collapse: collapse; }
        table.matriks th, table.matriks td { border: .6pt solid #444; padding: 2.5pt 3pt; }
        table.matriks th { background: #e8e8e8; font-size: 6.8pt; text-align: center; }
        table.matriks td { font-size: 7pt; }
        table.matriks td.simbol { text-align: center; font-size: 9pt; font-weight: bold; }
        table.matriks td.kode { font-weight: bold; white-space: nowrap; }
        .kecil { font-size: 6.2pt; color: #333; }

        /* Baris aset yang tidak diperiksa diberi latar abu supaya baris kosong
           terbaca sebagai kelalaian, bukan sebagai kolom yang terlewat dicetak. */
        tr.kosong td { background: #f2f2f2; font-style: italic; }

        /*
            Kolom butir periksa diberi NOMOR, bukan judul miring.

            dompdf tidak mendukung writing-mode maupun transform: rotate, jadi
            judul vertikal mustahil di sini. Penomoran juga yang dipakai lembar
            PMS cetak yang sudah berjalan, dengan legenda di bawah tabel.
        */
        th.nomor { width: 3.2%; font-size: 7.5pt; }

        .legenda { margin-top: 8pt; font-size: 6.8pt; }
        .legenda table { width: 100%; border-collapse: collapse; }
        .legenda td { padding: 1.2pt 6pt 1.2pt 0; vertical-align: top; width: 25%; }
        .legenda .no { font-weight: bold; }

        .ket { margin-top: 8pt; font-size: 7pt; }
        .ket table { border-collapse: collapse; }
        .ket td { padding: 1pt 8pt 1pt 0; }

        .ringkas { width: 100%; border-collapse: collapse; margin-top: 8pt; font-size: 7.5pt; }
        .ringkas td, .ringkas th { border: .6pt solid #444; padding: 3pt 5pt; }
        .ringkas th { background: #e8e8e8; text-align: left; width: 26%; }

        .ttd { width: 100%; margin-top: 16pt; font-size: 7.5pt; text-align: center; }
        .ttd td { width: 33.33%; padding-top: 2pt; vertical-align: top; }
        .ttd .garis { border-top: .6pt solid #000; margin: 38pt 12pt 2pt; }

        /* dompdf menempatkan elemen fixed relatif terhadap kotak halaman.
           Nilai bottom dijaga positif agar teks tidak jatuh ke luar area cetak
           dan terpotong oleh pemotong kertas. */
        .kaki { position: fixed; bottom: 2mm; left: 0; right: 0; font-size: 6pt; color: #555; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width:66%">
                <div class="nama">PT PAL INDONESIA (PERSERO)</div>
                <div class="sub">Divisi Keselamatan, Kesehatan Kerja dan Lindungan Lingkungan</div>
            </td>
            <td class="lampiran">
                <div><strong>PREVENTIVE MAINTENANCE SCHEDULE</strong></div>
                <div>Lampiran 03 dari 04</div>
                <div>Dicetak {{ now()->translatedFormat('d M Y H:i') }} WIB</div>
            </td>
        </tr>
    </table>

    <h1>Laporan Pemeriksaan Berkala {{ $tipe->nama }}</h1>
    <p class="periode">
        Periode {{ $namaBulan[$periode->month] }} {{ $periode->year }}
        @if ($filter['gedung']) &middot; Gedung {{ $filter['gedung'] }} @endif
        &middot; {{ $aset->count() }} unit terdaftar
    </p>

    <table class="matriks">
        <thead>
            <tr>
                <th rowspan="2" style="width:9%">Kode</th>
                <th rowspan="2" style="width:17%">Lokasi</th>
                <th rowspan="2" style="width:6%">Tanggal</th>
                @foreach ($grup as $namaGrup => $isiGrup)
                    <th colspan="{{ $isiGrup->count() }}">{{ $namaGrup }}</th>
                @endforeach
                <th rowspan="2" style="width:8%">Kesimpulan</th>
                <th rowspan="2" style="width:9%">Petugas</th>
            </tr>
            <tr>
                @foreach ($butir as $i => $b)
                    <th class="nomor">{{ $i + 1 }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($aset as $a)
                @php $ins = $inspeksi[$a->id] ?? null; @endphp
                <tr class="{{ $ins ? '' : 'kosong' }}">
                    <td class="kode">{{ $a->kode }}</td>
                    <td>
                        {{ $a->lokasi_teks ?: $a->gedung }}
                        @if ($a->labelLantai())<span class="kecil"> ({{ $a->labelLantai() }})</span>@endif
                    </td>
                    <td style="text-align:center">
                        {{ $ins ? $ins->inspected_at->format('d/m') : '-' }}
                    </td>

                    @foreach ($butir as $b)
                        @php $jwb = $ins?->answers->firstWhere('checklist_item_id', $b->id); @endphp
                        <td class="simbol">{{ $jwb ? $jwb->simbol() : '-' }}</td>
                    @endforeach

                    <td style="text-align:center">
                        {{ $ins ? (\App\Models\Inspection::KESIMPULAN[$ins->kesimpulan] ?? '-') : 'Belum diperiksa' }}
                    </td>
                    <td class="kecil">{{ $ins?->user?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legenda">
        <strong>Nomor butir pemeriksaan.</strong>
        <table>
            @foreach ($butir->chunk(4) as $baris)
                <tr>
                    @foreach ($baris as $i => $b)
                        <td>
                            {{-- chunk() mempertahankan kunci asli, dan $butir sudah
                                 di-values() di controller, jadi $i adalah nomor urut
                                 yang sama dengan kepala kolom. --}}
                            <span class="no">{{ $i + 1 }}.</span>
                            {{ $b->label }}
                            @if ($b->dasar_hukum)
                                <span class="kecil">({{ $b->dasar_hukum }})</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>

    <div class="ket">
        <strong>Keterangan simbol.</strong> Dua dimensi dinilai terpisah:
        <table>
            <tr>
                <td><strong>&#10003;</strong> berfungsi</td>
                <td><strong>X</strong> tidak berfungsi</td>
                <td><strong>O</strong> kondisi baik</td>
                <td><strong>&#216;</strong> kondisi tidak baik</td>
                <td><strong>-</strong> tidak diperiksa</td>
            </tr>
        </table>
        Dasar hukum: Permenaker No. 4 Tahun 1980 tentang syarat pemasangan dan
        pemeliharaan alat pemadam api ringan, serta NFPA 10 untuk periode
        pemeriksaan bulanan.
    </div>

    <table class="ringkas">
        <tr>
            <th>Unit terdaftar</th>
            <td>{{ $ringkas['total'] }} unit</td>
            <th>Tingkat kepatuhan</th>
            <td>
                {{ number_format($ringkas['kepatuhan'], 1, ',', '.') }}%
                ({{ $ringkas['diperiksa'] }} diperiksa, {{ $ringkas['belum'] }} belum)
            </td>
        </tr>
        <tr>
            <th>Hasil pemeriksaan</th>
            <td>
                Layak {{ $ringkas['layak'] }} &middot;
                Layak dengan catatan {{ $ringkas['layakCatatan'] }} &middot;
                Tidak layak {{ $ringkas['tidakLayak'] }}
            </td>
            <th>Temuan berat terbuka</th>
            <td>{{ $ringkas['beratTerbuka'] }} butir</td>
        </tr>
        @if ($ringkas['perluTinjauan'] > 0)
            <tr>
                <th>Perlu tinjauan</th>
                <td colspan="3">
                    {{ $ringkas['perluTinjauan'] }} pemeriksaan memiliki catatan posisi
                    yang perlu ditinjau Admin K3 sebelum laporan disahkan.
                </td>
            </tr>
        @endif
    </table>

    <table class="ttd">
        <tr>
            <td>
                Diperiksa oleh<br>Petugas K3
                <div class="garis"></div>
                Nama dan tanggal
            </td>
            <td>
                Diverifikasi oleh<br>Supervisor K3LH
                <div class="garis"></div>
                Nama dan tanggal
            </td>
            <td>
                Disetujui oleh<br>Kepala Divisi K3LH
                <div class="garis"></div>
                Nama dan tanggal
            </td>
        </tr>
    </table>

    <div class="kaki">
        Dokumen dihasilkan Sistem Inspeksi K3 PT PAL Indonesia.
        Baris berlatar abu menandakan unit yang belum diperiksa pada periode ini.
    </div>
</body>
</html>

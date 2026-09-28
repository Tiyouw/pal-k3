{{--
    Rekapitulasi temuan terbuka. Potret, satu tabel, diurutkan dari yang terberat
    oleh controller. Tujuan lembar ini bukan mencatat semua yang pernah rusak,
    melainkan menjawab satu pertanyaan pengawas: mana yang harus dikerjakan
    minggu ini dan mana yang sudah lewat tenggat.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Temuan &mdash; {{ $namaBulan[$periode->month] }} {{ $periode->year }}</title>
    <style>
        @page { margin: 14mm 12mm 16mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #000; margin: 0; }

        .kop { width: 100%; border-bottom: 1.5pt solid #000; padding-bottom: 5pt; margin-bottom: 10pt; }
        .kop .nama { font-size: 11.5pt; font-weight: bold; }
        .kop .sub { font-size: 8pt; }
        .kop .kanan { text-align: right; font-size: 7.5pt; }

        h1 { font-size: 10.5pt; text-align: center; margin: 0 0 2pt; text-transform: uppercase; }
        .periode { text-align: center; font-size: 8.5pt; margin: 0 0 10pt; }

        table.daftar { width: 100%; border-collapse: collapse; }
        table.daftar th, table.daftar td { border: .6pt solid #444; padding: 3.5pt 4pt; vertical-align: top; }
        table.daftar th { background: #e8e8e8; font-size: 7.5pt; text-align: left; }
        table.daftar td { font-size: 8pt; }
        .tengah { text-align: center; }
        .kecil { font-size: 7pt; color: #333; }

        /* Tingkat keparahan dibedakan dengan tebal-tipis huruf dan latar, bukan
           warna saja: lembar ini hampir selalu dicetak hitam putih. */
        .berat  { background: #f0f0f0; font-weight: bold; }
        .lewat  { font-weight: bold; }

        .ringkas { width: 100%; border-collapse: collapse; margin-bottom: 10pt; font-size: 8pt; }
        .ringkas th, .ringkas td { border: .6pt solid #444; padding: 3.5pt 5pt; }
        .ringkas th { background: #e8e8e8; text-align: left; width: 28%; }

        .ttd { width: 100%; margin-top: 18pt; font-size: 8pt; text-align: center; }
        .ttd td { width: 33.33%; vertical-align: top; }
        .ttd .garis { border-top: .6pt solid #000; margin: 40pt 14pt 2pt; }

        .kaki { position: fixed; bottom: 2mm; left: 0; right: 0; font-size: 6.5pt; color: #555; }
        .kosong { text-align: center; padding: 16pt; font-style: italic; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width:64%">
                <div class="nama">PT PAL INDONESIA (PERSERO)</div>
                <div class="sub">Divisi Keselamatan, Kesehatan Kerja dan Lindungan Lingkungan</div>
            </td>
            <td class="kanan">
                <div><strong>REKAPITULASI TEMUAN</strong></div>
                <div>Lampiran pendukung laporan PMS</div>
                <div>Dicetak {{ now()->translatedFormat('d M Y H:i') }} WIB</div>
            </td>
        </tr>
    </table>

    <h1>Rekapitulasi Temuan {{ $tipe->nama }}</h1>
    <p class="periode">
        Keadaan per {{ now()->translatedFormat('d F Y') }}
        &middot; periode laporan {{ $namaBulan[$periode->month] }} {{ $periode->year }}
        @if ($filter['gedung']) &middot; Gedung {{ $filter['gedung'] }} @endif
    </p>

    <table class="ringkas">
        <tr>
            <th>Temuan terbuka</th>
            <td>{{ $temuan->count() }} butir</td>
            <th>Tingkat berat</th>
            <td>{{ $temuan->where('severity', 'berat')->count() }} butir</td>
        </tr>
        <tr>
            <th>Lewat tenggat</th>
            <td>
                {{ $temuan->filter(fn ($t) => $t->target_selesai && $t->target_selesai->isPast())->count() }} butir
            </td>
            <th>Unit terdampak</th>
            <td>{{ $temuan->pluck('asset_id')->unique()->count() }} dari {{ $ringkas['total'] }} unit</td>
        </tr>
    </table>

    <table class="daftar">
        <thead>
            <tr>
                <th class="tengah" style="width:4%">No</th>
                <th style="width:11%">Kode unit</th>
                <th style="width:17%">Lokasi</th>
                <th style="width:24%">Temuan</th>
                <th class="tengah" style="width:9%">Tingkat</th>
                <th style="width:19%">Tindakan yang diminta</th>
                <th class="tengah" style="width:9%">Tenggat</th>
                <th class="tengah" style="width:7%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($temuan as $i => $t)
                @php
                    $lewat = $t->target_selesai && $t->target_selesai->isPast();
                @endphp
                <tr class="{{ $t->severity === 'berat' ? 'berat' : '' }}">
                    <td class="tengah">{{ $i + 1 }}</td>
                    <td><strong>{{ $t->asset?->kode ?? '-' }}</strong></td>
                    <td>
                        {{ $t->asset?->lokasi_teks ?: $t->asset?->gedung }}
                        @if ($t->asset?->labelLantai())
                            <span class="kecil">({{ $t->asset->labelLantai() }})</span>
                        @endif
                    </td>
                    <td>
                        {{ $t->item }}
                        <div class="kecil">
                            Ditemukan {{ $t->created_at->translatedFormat('d M Y') }}
                            &middot; {{ $t->umurHari() }} hari berjalan
                        </div>
                    </td>
                    <td class="tengah">{{ ucfirst($t->severity) }}</td>
                    <td>{{ $t->tindak_lanjut ?: (\App\Services\IssueGenerator::TINDAKAN[$t->severity] ?? '-') }}</td>
                    <td class="tengah {{ $lewat ? 'lewat' : '' }}">
                        {{ $t->target_selesai ? $t->target_selesai->format('d/m/y') : '-' }}
                        @if ($lewat)<div class="kecil">lewat</div>@endif
                    </td>
                    <td class="tengah">{{ ucfirst($t->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="kosong">
                        Tidak ada temuan terbuka pada lingkup ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($ringkas['belum'] > 0)
        <p style="font-size:8pt; margin-top:10pt">
            <strong>Catatan kepatuhan.</strong>
            {{ $ringkas['belum'] }} dari {{ $ringkas['total'] }} unit belum diperiksa pada periode ini,
            sehingga daftar di atas belum menggambarkan keadaan seluruh unit.
        </p>
    @endif

    <table class="ttd">
        <tr>
            <td>
                Disusun oleh<br>Petugas K3
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
        Baris berlatar abu menandakan temuan tingkat berat: unit diturunkan dari layanan sampai diganti.
    </div>
</body>
</html>

{{--
    Kartu kontrol satu unit, satu tahun. Lembar ini digantung pada tabung dan
    menjadi rujukan pertama pengawas saat berkeliling, jadi isinya ringkas:
    tanggal, petugas, kesimpulan. Rincian butir periksa ada di lembar PMS.

    Diberi dua belas baris bulan yang selalu tercetak, termasuk bulan yang
    belum lewat. Baris kosong pada bulan yang sudah lewat adalah temuan
    kepatuhan, dan kartu yang hanya memuat bulan terisi selalu tampak penuh.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Kontrol {{ $asset->kode }} &mdash; {{ $tahun }}</title>
    <style>
        @page { margin: 12mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #000; margin: 0; }

        .rangka { border: 1.5pt solid #000; padding: 8pt 10pt 10pt; }

        .kop { width: 100%; border-bottom: 1pt solid #000; padding-bottom: 5pt; margin-bottom: 8pt; }
        .kop .nama { font-size: 11pt; font-weight: bold; }
        .kop .sub { font-size: 7.5pt; }
        .kop .kanan { text-align: right; font-size: 7.5pt; }

        h1 { font-size: 12pt; text-align: center; margin: 0 0 1pt; text-transform: uppercase; letter-spacing: .4pt; }
        .tahun { text-align: center; font-size: 10pt; font-weight: bold; margin: 0 0 9pt; }

        table.identitas { width: 100%; border-collapse: collapse; margin-bottom: 9pt; font-size: 8.5pt; }
        table.identitas th, table.identitas td { border: .6pt solid #444; padding: 3.5pt 5pt; }
        table.identitas th { background: #e8e8e8; text-align: left; width: 18%; }

        table.bulan { width: 100%; border-collapse: collapse; }
        table.bulan th, table.bulan td { border: .6pt solid #444; padding: 4pt 5pt; }
        table.bulan th { background: #e8e8e8; font-size: 7.5pt; text-align: center; }
        table.bulan td { font-size: 8pt; height: 15pt; }
        .tengah { text-align: center; }
        .kecil { font-size: 7pt; color: #333; }

        /* Bulan yang sudah lewat tanpa pemeriksaan diberi latar abu. Bulan yang
           belum datang dibiarkan putih supaya tidak terbaca sebagai kelalaian. */
        tr.terlewat td { background: #f0f0f0; }
        tr.mendatang td { color: #888; }

        .ttd { width: 100%; margin-top: 12pt; font-size: 8pt; text-align: center; }
        .ttd td { width: 50%; vertical-align: top; }
        .ttd .garis { border-top: .6pt solid #000; margin: 34pt 16pt 2pt; }

        .catatan { font-size: 7pt; margin-top: 8pt; color: #333; }
    </style>
</head>
<body>
<div class="rangka">
    <table class="kop">
        <tr>
            <td style="width:62%">
                <div class="nama">PT PAL INDONESIA (PERSERO)</div>
                <div class="sub">Divisi Keselamatan, Kesehatan Kerja dan Lindungan Lingkungan</div>
            </td>
            <td class="kanan">
                <div>Dicetak {{ now()->translatedFormat('d M Y') }}</div>
                <div>Simpan pada unit</div>
            </td>
        </tr>
    </table>

    <h1>Kartu Kontrol Pemeriksaan</h1>
    <p class="tahun">{{ $asset->assetType?->nama ?? 'Objek K3' }} &middot; Tahun {{ $tahun }}</p>

    <table class="identitas">
        <tr>
            <th>Kode unit</th>
            <td style="width:32%"><strong>{{ $asset->kode }}</strong></td>
            <th>Jenis media</th>
            <td>{{ $asset->labelMedia() ?: '-' }}</td>
        </tr>
        <tr>
            <th>Lokasi</th>
            <td>{{ $asset->lokasi_teks ?: '-' }}</td>
            <th>Gedung</th>
            <td>{{ $asset->gedung ?: '-' }}{{ $asset->labelLantai() ? ', ' . $asset->labelLantai() : '' }}</td>
        </tr>
        <tr>
            <th>Divisi</th>
            <td>{{ $asset->division?->nama ?? '-' }}</td>
            <th>Masa kedaluwarsa</th>
            <td>{{ $asset->tgl_expired ? $asset->tgl_expired->translatedFormat('d M Y') : '-' }}</td>
        </tr>
    </table>

    <table class="bulan">
        <thead>
            <tr>
                <th style="width:15%">Bulan</th>
                <th style="width:12%">Tanggal</th>
                <th style="width:24%">Petugas pemeriksa</th>
                <th style="width:19%">Kesimpulan</th>
                <th style="width:10%">Temuan</th>
                <th>Paraf</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($namaBulan as $no => $nama)
                @php
                    // Bulan yang sama bisa punya pemeriksaan ulang; yang terakhir dipakai.
                    $ins = ($perBulan[$no] ?? collect())->last();

                    $lewat = \Carbon\Carbon::create($tahun, $no, 1)->endOfMonth()->isPast();
                    $kelas = $ins ? '' : ($lewat ? 'terlewat' : 'mendatang');
                @endphp
                <tr class="{{ $kelas }}">
                    <td>{{ $nama }}</td>
                    <td class="tengah">{{ $ins ? $ins->inspected_at->format('d/m') : '' }}</td>
                    <td>
                        {{ $ins?->user?->name ?? '' }}
                        @if ($ins && $ins->perluTinjauan())
                            <div class="kecil">perlu tinjauan posisi</div>
                        @endif
                    </td>
                    <td>
                        @if ($ins)
                            {{ \App\Models\Inspection::KESIMPULAN[$ins->kesimpulan] ?? '-' }}
                        @elseif ($lewat)
                            <span class="kecil">tidak diperiksa</span>
                        @endif
                    </td>
                    <td class="tengah">
                        {{ $ins ? ($ins->issues()->count() ?: '0') : '' }}
                    </td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="catatan">
        Periode pemeriksaan satu bulan, mengikuti Permenaker No. 4 Tahun 1980 dan NFPA 10.
        Baris berlatar abu menandakan bulan yang sudah lewat tanpa pemeriksaan tercatat.
        Kolom paraf diisi tangan oleh pengawas saat memeriksa kartu di lokasi.
    </p>

    <table class="ttd">
        <tr>
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
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Laporan Bulanan &mdash; Inspeksi K3</title>

    <style>
        :root {
            --biru: #0f3c68; --biru-muda: #e8f1f9; --abu: #5c636a;
            --abu-terang: #f6f7f9; --garis: #d7dbe0;
            --hijau: #157347; --hijau-md: #e6f4ec;
            --kuning: #8a6100; --kuning-md: #fff4e0;
            --merah: #b02a37; --merah-md: #fdeaec;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--abu-terang); color: #1b1f23; font-size: 16px; line-height: 1.55;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .bingkai { max-width: 1100px; margin: 0 auto; padding: 0 20px 48px; }

        header { background: var(--biru); color: #fff; padding: 18px 0; margin-bottom: 24px; }
        header .bingkai { padding-bottom: 0; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.15rem; margin: 0; }
        header a { color: #fff; font-size: .88rem; }

        .kartu { background: #fff; border: 1px solid var(--garis); border-radius: 14px; padding: 20px; margin-bottom: 18px; }
        .kartu h2 { font-size: 1rem; margin: 0 0 14px; color: var(--biru); }

        form.saring { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        label { display: block; font-size: .78rem; font-weight: 600; color: var(--abu); margin-bottom: 4px; }
        select, input[type=text] {
            min-height: 44px; padding: 8px 10px; border: 1px solid var(--garis);
            border-radius: 9px; font-size: .92rem; min-width: 140px; background: #fff;
        }
        .tbl {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            min-height: 44px; padding: 0 18px; border: 0; border-radius: 9px;
            font-weight: 600; font-size: .92rem; text-decoration: none; cursor: pointer;
        }
        .tbl-biru { background: var(--biru); color: #fff; }
        .tbl-garis { background: #fff; color: var(--biru); border: 1.5px solid var(--biru); }

        .angka { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; }
        .angka div { background: var(--abu-terang); border-radius: 12px; padding: 14px; }
        .angka .nilai { font-size: 1.55rem; font-weight: 800; line-height: 1.1; }
        .angka .nama { font-size: .78rem; color: var(--abu); margin-top: 2px; }
        .angka .n-hijau { color: var(--hijau); }
        .angka .n-kuning { color: var(--kuning); }
        .angka .n-merah { color: var(--merah); }

        .pesan { border-radius: 10px; padding: 12px 14px; font-size: .88rem; margin-bottom: 14px; }
        .p-kuning { background: var(--kuning-md); color: var(--kuning); }
        .p-hijau { background: var(--hijau-md); color: var(--hijau); }

        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th, td { padding: 9px 10px; border-bottom: 1px solid var(--garis); text-align: left; vertical-align: top; }
        th { font-size: .76rem; text-transform: uppercase; letter-spacing: .3px; color: var(--abu); }
        .tengah { text-align: center; }
        .redup { color: var(--abu); font-size: .8rem; }

        .lencana { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: .74rem; font-weight: 600; white-space: nowrap; }
        .l-hijau { background: var(--hijau-md); color: var(--hijau); }
        .l-kuning { background: var(--kuning-md); color: var(--kuning); }
        .l-merah { background: var(--merah-md); color: var(--merah); }
        .l-abu { background: #e9ecef; color: var(--abu); }

        .gulung { overflow-x: auto; }
        .aksi-baris { display: flex; flex-wrap: wrap; gap: 10px; }
    </style>
</head>
<body>
    <header>
        <div class="bingkai">
            <h1>Laporan Bulanan Inspeksi K3</h1>
            <span>
                <a href="{{ route('stiker.index') }}">Lembar stiker QR</a>
                &nbsp;&middot;&nbsp;
                <a href="/admin">Panel admin</a>
            </span>
        </div>
    </header>

    <div class="bingkai">
        <div class="kartu">
            <h2>Pilih periode</h2>
            <form method="GET" action="{{ route('laporan.index') }}" class="saring">
                <div>
                    <label for="tipe">Jenis objek</label>
                    <select name="tipe" id="tipe">
                        @foreach ($pilihanTipe as $t)
                            <option value="{{ $t->slug }}" @selected($filter['tipe'] === $t->slug)>{{ $t->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="bulan">Bulan</label>
                    <select name="bulan" id="bulan">
                        @foreach ($namaBulan as $no => $nama)
                            <option value="{{ $no }}" @selected($filter['bulan'] === $no)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tahun">Tahun</label>
                    <select name="tahun" id="tahun">
                        @foreach ($pilihanTahun as $th)
                            <option value="{{ $th }}" @selected($filter['tahun'] === (int) $th)>{{ $th }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="gedung">Gedung</label>
                    <select name="gedung" id="gedung">
                        <option value="">Semua</option>
                        @foreach ($gedung as $g)
                            <option value="{{ $g }}" @selected($filter['gedung'] === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="tbl tbl-biru">Tampilkan</button>
            </form>
        </div>

        <div class="kartu">
            <h2>Ringkasan {{ $namaBulan[$periode->month] }} {{ $periode->year }}</h2>

            @if ($ringkas['belum'] > 0)
                <div class="pesan p-kuning">
                    {{ $ringkas['belum'] }} dari {{ $ringkas['total'] }} unit belum diperiksa pada periode ini.
                    Unit yang belum diperiksa tetap tercetak di lembar PMS sebagai baris kosong,
                    sehingga tingkat kepatuhan terbaca apa adanya.
                </div>
            @else
                <div class="pesan p-hijau">
                    Seluruh {{ $ringkas['total'] }} unit sudah diperiksa pada periode ini.
                </div>
            @endif

            <div class="angka">
                <div>
                    <div class="nilai {{ $ringkas['kepatuhan'] >= 90 ? 'n-hijau' : ($ringkas['kepatuhan'] >= 70 ? 'n-kuning' : 'n-merah') }}">
                        {{ number_format($ringkas['kepatuhan'], 1, ',', '.') }}%
                    </div>
                    <div class="nama">Tingkat kepatuhan</div>
                </div>
                <div>
                    <div class="nilai">{{ $ringkas['diperiksa'] }}<span style="font-size:.9rem; font-weight:400; color:var(--abu)">/{{ $ringkas['total'] }}</span></div>
                    <div class="nama">Unit diperiksa</div>
                </div>
                <div>
                    <div class="nilai n-hijau">{{ $ringkas['layak'] }}</div>
                    <div class="nama">Layak</div>
                </div>
                <div>
                    <div class="nilai n-kuning">{{ $ringkas['layakCatatan'] }}</div>
                    <div class="nama">Layak dengan catatan</div>
                </div>
                <div>
                    <div class="nilai n-merah">{{ $ringkas['tidakLayak'] }}</div>
                    <div class="nama">Tidak layak</div>
                </div>
                <div>
                    <div class="nilai n-merah">{{ $ringkas['beratTerbuka'] }}</div>
                    <div class="nama">Temuan berat terbuka</div>
                </div>
                <div>
                    <div class="nilai {{ $ringkas['perluTinjauan'] ? 'n-kuning' : '' }}">{{ $ringkas['perluTinjauan'] }}</div>
                    <div class="nama">Perlu tinjauan posisi</div>
                </div>
            </div>
        </div>

        <div class="kartu">
            <h2>Unduh berkas laporan</h2>
            <div class="aksi-baris">
                <a class="tbl tbl-biru" target="_blank" rel="noopener"
                   href="{{ route('laporan.pms', $filter) }}">Lembar PMS bulanan</a>
                <a class="tbl tbl-garis" target="_blank" rel="noopener"
                   href="{{ route('laporan.temuan', $filter) }}">Rekapitulasi temuan</a>
            </div>
            <p class="redup" style="margin:12px 0 0">
                Kartu kontrol dicetak per unit. Gunakan tautan pada kolom terakhir tabel di bawah.
            </p>
        </div>

        <div class="kartu">
            <h2>Rincian per unit</h2>
            <div class="gulung">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Lokasi</th>
                            <th>Tanggal periksa</th>
                            <th>Petugas</th>
                            <th>Kesimpulan</th>
                            <th class="tengah">Temuan</th>
                            <th>Bukti posisi</th>
                            <th>Kartu kontrol</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($aset as $a)
                            @php $ins = $inspeksi[$a->id] ?? null; @endphp
                            <tr>
                                <td><strong>{{ $a->kode }}</strong></td>
                                <td>
                                    {{ $a->lokasi_teks ?: $a->gedung }}
                                    @if ($a->labelLantai())<span class="redup"> &middot; {{ $a->labelLantai() }}</span>@endif
                                </td>
                                <td>
                                    {{ $ins ? $ins->inspected_at->translatedFormat('d M Y, H:i') : '-' }}
                                </td>
                                <td>{{ $ins?->user?->name ?? '-' }}</td>
                                <td>
                                    @if ($ins)
                                        @php
                                            $k = match ($ins->kesimpulan) {
                                                'layak' => 'l-hijau',
                                                'tidak_layak' => 'l-merah',
                                                default => 'l-kuning',
                                            };
                                        @endphp
                                        <span class="lencana {{ $k }}">
                                            {{ \App\Models\Inspection::KESIMPULAN[$ins->kesimpulan] ?? '-' }}
                                        </span>
                                    @else
                                        <span class="lencana l-abu">Belum diperiksa</span>
                                    @endif
                                </td>
                                <td class="tengah">
                                    {{ $ins ? $temuan->where('inspection_id', $ins->id)->count() : '-' }}
                                </td>
                                <td>
                                    @if ($ins)
                                        @php
                                            $kg = match ($ins->gate_status) {
                                                'sesuai' => 'l-hijau',
                                                'jauh' => 'l-merah',
                                                default => 'l-kuning',
                                            };
                                        @endphp
                                        <span class="lencana {{ $kg }}">{{ $ins->labelGerbang() }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('laporan.kartu', ['asset' => $a->id, 'tahun' => $periode->year]) }}"
                                       target="_blank" rel="noopener">Cetak {{ $periode->year }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

@extends('layouts.petugas')

@section('judul', 'Inspeksi Terkirim')
@section('judul-kepala', 'Terkirim')
@section('sub-kepala', $asset->kode)

@section('kembali')
    <a href="{{ route('petugas.beranda') }}" aria-label="Kembali ke beranda">&#8592;</a>
@endsection

@section('isi')
    @php
        $warna = match ($inspeksi->kesimpulan) {
            'layak'       => ['hijau', 'var(--hijau)', 'var(--hijau-md)'],
            'tidak_layak' => ['merah', 'var(--merah)', 'var(--merah-md)'],
            default       => ['kuning', 'var(--kuning)', 'var(--kuning-md)'],
        };
    @endphp

    <div class="kartu" style="text-align:center; padding:26px 20px; border-color:{{ $warna[1] }}">
        <div aria-hidden="true"
             style="width:64px; height:64px; margin:0 auto 14px; border-radius:50%;
                    background:{{ $warna[2] }}; color:{{ $warna[1] }}; font-size:1.9rem;
                    display:flex; align-items:center; justify-content:center">&#10003;</div>

        <h1 style="font-size:1.1rem; margin:0 0 4px">Inspeksi tersimpan</h1>
        <p class="redup" style="margin:0 0 14px">
            {{ $asset->kode }} &middot; {{ $inspeksi->inspected_at->translatedFormat('d M Y, H:i') }} WIB
        </p>

        <span class="lencana l-{{ $warna[0] }}" style="font-size:.92rem; padding:8px 16px">
            {{ \App\Models\Inspection::KESIMPULAN[$inspeksi->kesimpulan] ?? $inspeksi->kesimpulan }}
        </span>
    </div>

    {{-- Keadaan bukti kehadiran ditampilkan apa adanya, termasuk saat GPS lemah.
         Petugas perlu tahu inspeksinya akan masuk antrean tinjauan Admin K3
         sebelum dia meninggalkan lokasi, bukan seminggu kemudian. --}}
    <div class="kartu">
        <h2>Bukti kehadiran</h2>
        <div class="baris" style="padding:8px 0">
            <span>Pemindaian stiker QR</span>
            <span class="lencana l-hijau">&#10003; Terverifikasi</span>
        </div>
        <div class="baris" style="padding:8px 0; border-top:1px solid var(--garis)">
            <span>Posisi GPS</span>
            @php
                $kelasGerbang = match ($inspeksi->gate_status) {
                    'sesuai'  => 'l-hijau',
                    'jauh'    => 'l-merah',
                    default   => 'l-kuning',
                };
            @endphp
            <span class="lencana {{ $kelasGerbang }}">{{ $inspeksi->labelGerbang() }}</span>
        </div>
        @if ($inspeksi->jarak_m !== null)
            <p class="redup mono" style="margin:8px 0 0; font-size:.82rem">
                Jarak dari titik aset {{ number_format($inspeksi->jarak_m, 0, ',', '.') }} m
                @if ($inspeksi->gps_accuracy)
                    &middot; ketelitian &plusmn;{{ number_format($inspeksi->gps_accuracy, 0, ',', '.') }} m
                @endif
            </p>
        @endif
        <div class="baris" style="padding:8px 0; border-top:1px solid var(--garis)">
            <span>Foto bukti</span>
            <span class="lencana l-hijau">{{ $inspeksi->photos->count() }} foto</span>
        </div>
    </div>

    {{-- Temuan diurutkan dari yang terberat oleh controller. --}}
    <div class="kartu">
        <div class="baris" style="margin-bottom:6px">
            <h2 style="margin:0">Temuan</h2>
            <span class="lencana {{ $temuan->isEmpty() ? 'l-hijau' : 'l-kuning' }}">
                {{ $temuan->count() }} butir
            </span>
        </div>

        @forelse ($temuan as $t)
            @php
                $k = match ($t->severity) {
                    'berat'  => 'l-merah',
                    'sedang' => 'l-kuning',
                    default  => 'l-abu',
                };
            @endphp
            <div style="padding:12px 0; border-top:1px solid var(--garis)">
                <div class="baris" style="align-items:flex-start">
                    <span style="flex:1">{{ $t->item }}</span>
                    <span class="lencana {{ $k }}">{{ ucfirst($t->severity) }}</span>
                </div>
                <p class="redup" style="margin:6px 0 0; font-size:.82rem">
                    {{ \App\Services\IssueGenerator::TINDAKAN[$t->severity] ?? '' }}
                    @if ($t->target_selesai)
                        &middot; tenggat {{ $t->target_selesai->translatedFormat('d M Y') }}
                    @endif
                </p>
            </div>
        @empty
            <div class="pesan p-hijau" style="margin-top:8px">
                Seluruh butir pemeriksaan dinyatakan baik. Tidak ada temuan.
            </div>
        @endforelse

        @if ($inspeksi->rekomendasi)
            <div style="margin-top:14px; padding-top:14px; border-top:1px solid var(--garis)">
                <p style="margin:0 0 4px; font-weight:600; font-size:.88rem">Rekomendasi Anda</p>
                <p class="redup" style="margin:0">{{ $inspeksi->rekomendasi }}</p>
            </div>
        @endif
    </div>

    <p class="redup" style="text-align:center; margin:16px 0 0; font-size:.82rem">
        Hasil ini masuk ke laporan bulanan dan tidak dapat diubah lagi.
        Bila ada kekeliruan, hubungi Admin K3.
    </p>
@endsection

@section('palang')
    <div class="palang">
        <div class="bingkai-dalam">
            {{-- Tombol utama mengarah ke pemindai, bukan beranda: petugas biasanya
                 punya beberapa tabung dalam satu lantai dan langsung lanjut. --}}
            <a href="{{ route('petugas.pindai') }}" class="tbl tbl-utama">
                <span aria-hidden="true">&#9635;</span> Inspeksi aset berikutnya
            </a>
            <a href="{{ route('petugas.beranda') }}" class="tbl tbl-abu tbl-kecil"
               style="width:100%; margin-top:8px">Selesai, ke beranda</a>
        </div>
    </div>
@endsection

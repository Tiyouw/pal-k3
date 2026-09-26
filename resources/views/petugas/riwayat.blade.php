@extends('layouts.petugas')

@section('judul', 'Riwayat Inspeksi')
@section('judul-kepala', 'Riwayat Saya')
@section('sub-kepala', $inspeksi->total() . ' inspeksi terkirim')

@section('kembali')
    <a href="{{ route('petugas.beranda') }}" aria-label="Kembali ke beranda">&#8592;</a>
@endsection

@section('isi')
    @forelse ($inspeksi->groupBy(fn ($i) => $i->inspected_at->translatedFormat('F Y')) as $bulan => $daftar)
        <p style="margin:18px 2px 0; font-size:.8rem; font-weight:700; color:var(--abu);
                  text-transform:uppercase; letter-spacing:.4px">{{ $bulan }}</p>

        @foreach ($daftar as $i)
            @php
                $k = match ($i->kesimpulan) {
                    'layak'       => 'l-hijau',
                    'tidak_layak' => 'l-merah',
                    default       => 'l-kuning',
                };
                $berat = $i->issues->where('severity', 'berat')->count();
            @endphp

            <a href="{{ route('petugas.inspeksi.selesai', $i) }}" class="kartu baris"
               style="text-decoration:none; color:inherit; align-items:flex-start; min-height:64px">
                <span style="flex:1; min-width:0">
                    <strong>{{ $i->asset->kode }}</strong>
                    <span class="redup" style="display:block; font-size:.84rem">
                        {{ $i->asset->lokasi_teks ?: $i->asset->gedung }}
                    </span>
                    <span class="redup mono" style="display:block; font-size:.78rem; margin-top:2px">
                        {{ $i->inspected_at->translatedFormat('d M Y, H:i') }}
                        @if ($i->issues->isNotEmpty())
                            &middot; {{ $i->issues->count() }} temuan{{ $berat ? ", {$berat} berat" : '' }}
                        @endif
                    </span>
                </span>
                <span style="text-align:right">
                    <span class="lencana {{ $k }}" style="font-size:.72rem">
                        {{ \App\Models\Inspection::KESIMPULAN[$i->kesimpulan] ?? '-' }}
                    </span>
                    @if (in_array($i->gate_status, ['jauh', 'perlu_review', 'gps_lemah'], true))
                        <span class="lencana l-kuning" style="font-size:.68rem; margin-top:4px">
                            perlu tinjauan
                        </span>
                    @endif
                </span>
            </a>
        @endforeach
    @empty
        <div class="kartu" style="text-align:center; padding:32px 20px; margin-top:24px">
            <p style="margin:0 0 4px; font-weight:600">Belum ada riwayat</p>
            <p class="redup" style="margin:0">
                Inspeksi yang sudah Anda kirim akan tampil di sini.
            </p>
        </div>
    @endforelse

    @if ($inspeksi->hasPages())
        <div style="margin-top:20px">{{ $inspeksi->links() }}</div>
    @endif
@endsection

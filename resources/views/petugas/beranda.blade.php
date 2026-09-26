@extends('layouts.petugas')

@section('judul', 'Beranda Petugas')
@section('judul-kepala', $user->name)
@section('sub-kepala', 'NIP ' . ($user->nip ?? '-') . ' &middot; ' . ($user->jabatan ?: 'Inspektur'))

@section('kembali')
    <span aria-hidden="true" style="width:44px; height:44px; display:inline-flex;
          align-items:center; justify-content:center; background:rgba(255,255,255,.14);
          border-radius:10px">&#128100;</span>
@endsection

@section('isi')
    {{-- Ringkasan kepatuhan bulan berjalan. Angka besar supaya terbaca sekilas
         tanpa memfokuskan mata, karena sering dilihat sambil berjalan. --}}
    <div class="kartu">
        <div class="baris" style="margin-bottom:12px">
            <h2 style="margin:0">Periode {{ $bulanLabel }}</h2>
            <span class="lencana l-biru">APAR</span>
        </div>

        @php
            $persen = $totalAktif > 0 ? (int) round($sudah / $totalAktif * 100) : 0;
        @endphp

        <div style="display:flex; align-items:baseline; gap:6px; margin-bottom:10px">
            <span class="mono" style="font-size:2.4rem; font-weight:700; color:var(--biru)">{{ $sudah }}</span>
            <span class="redup" style="font-size:1.05rem">/ {{ $totalAktif }} tabung terperiksa</span>
        </div>

        {{-- role=progressbar agar pembaca layar mengumumkan angkanya, bukan
             hanya membacakan bilah kosong. --}}
        <div role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="Kemajuan inspeksi bulan ini"
             style="height:12px; background:var(--abu-md); border-radius:999px; overflow:hidden">
            <div style="width:{{ $persen }}%; height:100%;
                        background:{{ $persen >= 100 ? 'var(--hijau)' : 'var(--biru)' }}"></div>
        </div>

        <p class="redup" style="margin:10px 0 0">
            {{ $persen }}% selesai &middot; {{ $inspeksiSaya }} inspeksi oleh Anda bulan ini
        </p>
    </div>

    {{-- Draf yang tertinggal ditaruh di atas daftar tugas: pekerjaan separuh
         jalan lebih mendesak daripada memulai yang baru. --}}
    @if ($draf->isNotEmpty())
        <div class="kartu" style="border-color:#f0d79a; background:var(--kuning-md)">
            <h2 style="color:var(--kuning)">Belum dikirim ({{ $draf->count() }})</h2>
            @foreach ($draf as $d)
                <a href="{{ route('petugas.inspeksi.isi', $d) }}"
                   class="baris" style="text-decoration:none; color:inherit; padding:12px 0;
                          min-height:56px; border-top:1px solid #f0d79a">
                    <span>
                        <strong>{{ $d->asset->kode }}</strong><br>
                        <span class="redup">{{ $d->asset->lokasi_teks ?: $d->asset->gedung }}</span>
                    </span>
                    <span class="lencana l-kuning">Lanjutkan &rsaquo;</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Daftar sisa tugas dikelompokkan per gedung dan lantai supaya petugas
         bisa menyusun rute jalan, bukan berpindah lantai berulang kali. --}}
    <div class="kartu">
        <div class="baris" style="margin-bottom:4px">
            <h2 style="margin:0">Belum diperiksa ({{ $belum->count() }})</h2>
            @if ($belum->isNotEmpty())
                <span class="redup" style="font-size:.8rem">urut per lantai</span>
            @endif
        </div>

        @forelse ($belum->groupBy(fn ($a) => trim(($a->gedung ?: 'Lainnya') . ' ' . ($a->lantai ? 'Lt. ' . $a->lantai : ''))) as $grup => $daftar)
            <div style="margin-top:14px">
                <p style="margin:0 0 6px; font-size:.8rem; font-weight:700; color:var(--abu);
                          text-transform:uppercase; letter-spacing:.4px">
                    {{ $grup }} &middot; {{ $daftar->count() }}
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:8px">
                    @foreach ($daftar as $a)
                        {{-- Kode saja, tanpa tautan: aset TIDAK boleh dibuka dari
                             daftar. Satu-satunya jalan masuk adalah memindai QR di
                             lokasi (Bagian 5.2), supaya kehadiran tetap terbukti. --}}
                        <span class="lencana l-abu mono" style="padding:8px 12px; font-size:.85rem">
                            {{ $a->kode }}
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="pesan p-hijau" style="margin-top:8px">
                Seluruh APAR periode ini sudah diperiksa. Terima kasih.
            </div>
        @endforelse

        @if ($belum->isNotEmpty())
            <p class="redup" style="margin:16px 0 0; font-size:.82rem">
                Inspeksi hanya dapat dimulai dengan memindai stiker QR di lokasi tabung.
            </p>
        @endif
    </div>

    <a href="{{ route('petugas.riwayat') }}" class="tbl tbl-abu" style="margin-top:12px">
        Riwayat inspeksi saya
    </a>
@endsection

@section('palang')
    <div class="palang">
        <div class="bingkai-dalam">
            <a href="{{ route('petugas.pindai') }}" class="tbl tbl-utama" style="font-size:1.15rem">
                <span aria-hidden="true" style="font-size:1.3rem">&#9635;</span> Pindai QR
            </a>
        </div>
    </div>
@endsection

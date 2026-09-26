@extends('layouts.petugas')

@section('judul', $judul)
@section('judul-kepala', 'Tidak dapat dilanjutkan')
@section('sub-kepala', auth()->user()->name)

@section('kembali')
    <a href="{{ route('petugas.beranda') }}" aria-label="Kembali ke beranda">&#8592;</a>
@endsection

@section('isi')
    <div class="kartu" style="text-align:center; padding:28px 20px; margin-top:24px">
        <div aria-hidden="true"
             style="width:64px; height:64px; margin:0 auto 16px; border-radius:50%;
                    background:var(--merah-md); color:var(--merah); font-size:1.8rem;
                    display:flex; align-items:center; justify-content:center">&#9888;</div>

        <h1 style="font-size:1.15rem; margin:0 0 10px; color:var(--merah)">{{ $judul }}</h1>
        <p style="margin:0; color:var(--abu); text-align:left">{{ $pesan }}</p>

        @isset ($asset)
            <dl style="margin:18px 0 0; text-align:left; font-size:.88rem;
                       background:var(--abu-terang); border-radius:12px; padding:14px;
                       display:grid; grid-template-columns:auto 1fr; gap:6px 14px">
                <dt class="redup">Kode aset</dt>
                <dd style="margin:0"><strong>{{ $asset->kode }}</strong></dd>
                <dt class="redup">Lokasi</dt>
                <dd style="margin:0">{{ $asset->lokasi_teks ?: '-' }}</dd>
            </dl>
        @endisset
    </div>

    <a href="{{ route('petugas.pindai') }}" class="tbl tbl-utama" style="margin-top:12px">
        Pindai stiker lain
    </a>
    <a href="{{ route('petugas.beranda') }}" class="tbl tbl-abu" style="margin-top:10px">
        Kembali ke beranda
    </a>
@endsection

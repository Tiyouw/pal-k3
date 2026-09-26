@extends('layouts.petugas')

@section('judul', 'Masuk Petugas')

@section('isi')
    <div style="padding-top:32px">
        <div style="text-align:center; margin-bottom:24px">
            <div style="width:72px; height:72px; margin:0 auto 14px; border-radius:20px;
                        background:var(--biru); color:#fff; display:flex; align-items:center;
                        justify-content:center; font-size:2rem" aria-hidden="true">&#128680;</div>
            <h1 style="font-size:1.35rem; margin:0 0 4px; color:var(--biru-tua)">Inspeksi K3</h1>
            <p class="redup" style="margin:0">Divisi K3LH &mdash; PT PAL Indonesia</p>
        </div>

        <div class="kartu">
            <form method="POST" action="{{ route('petugas.masuk.kirim') }}" novalidate>
                @csrf

                <div style="margin-bottom:16px">
                    <label class="judul" for="nip">NIP</label>
                    {{--
                        inputmode numeric memunculkan papan tombol angka, tetapi tipe
                        tetap text: NIP berawalan nol tidak boleh dipangkas, dan
                        sebagian NIP memuat huruf.
                        autocomplete username agar pengelola sandi ponsel mengenalinya.
                    --}}
                    <input type="text" id="nip" name="nip" value="{{ old('nip') }}"
                           inputmode="numeric" autocomplete="username"
                           autocapitalize="off" autocorrect="off" spellcheck="false"
                           required autofocus
                           placeholder="Contoh: 198703152010011002"
                           aria-describedby="{{ $errors->has('nip') ? 'galat-nip' : '' }}"
                           @if($errors->has('nip')) aria-invalid="true" @endif>
                    @error('nip')
                        <p class="galat" id="galat-nip" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:16px">
                    <label class="judul" for="password">Kata sandi</label>
                    <div style="position:relative">
                        <input type="password" id="password" name="password"
                               autocomplete="current-password" required
                               style="padding-right:58px"
                               @if($errors->has('password')) aria-invalid="true" @endif>
                        {{-- Tombol lihat sandi: mengetik sandi di bawah sinar matahari
                             sambil memakai sarung tangan sering salah tekan. --}}
                        <button type="button" id="lihat"
                                aria-label="Tampilkan kata sandi" aria-pressed="false"
                                style="position:absolute; right:4px; top:4px; width:48px; height:44px;
                                       border:0; background:transparent; font-size:1.1rem; cursor:pointer">
                            &#128065;
                        </button>
                    </div>
                    @error('password')
                        <p class="galat" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <label style="display:flex; align-items:center; gap:10px; margin-bottom:20px; min-height:44px">
                    <input type="checkbox" name="ingat" value="1" style="width:22px; height:22px"
                           @checked(old('ingat'))>
                    <span>Ingat perangkat ini</span>
                </label>

                <button type="submit" class="tbl tbl-utama">Masuk</button>
            </form>
        </div>

        <p class="redup" style="text-align:center; margin-top:20px; font-size:.82rem">
            Lupa kata sandi? Hubungi Administrator K3 Divisi K3LH.
        </p>
    </div>
@endsection

@push('skrip')
<script>
    document.getElementById('lihat').addEventListener('click', function () {
        const isian  = document.getElementById('password');
        const tampil = isian.type === 'password';

        isian.type = tampil ? 'text' : 'password';
        this.setAttribute('aria-pressed', tampil ? 'true' : 'false');
        this.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
</script>
@endpush

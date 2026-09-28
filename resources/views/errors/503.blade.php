{{--
    Halaman galat 503. Menumpang errors/tata.blade.php supaya seluruh halaman
    galat berbunyi seragam dan selalu menawarkan jalan pulang; sebelum ini
    direktori errors/ belum ada sehingga yang tampil adalah halaman bawaan
    Laravel berbahasa Inggris tanpa satu pun tautan keluar.
--}}
@php
    $kode = '503';
    $judul = 'Sistem sedang dirawat';
    $pesan = 'Aplikasi sedang dalam perawatan singkat dan akan kembali normal.';
    $saran = [
        'Coba lagi beberapa menit kemudian.',
    ];
@endphp

@include('errors.tata')

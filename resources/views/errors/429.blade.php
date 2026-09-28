{{--
    Halaman galat 429. Menumpang errors/tata.blade.php supaya seluruh halaman
    galat berbunyi seragam dan selalu menawarkan jalan pulang; sebelum ini
    direktori errors/ belum ada sehingga yang tampil adalah halaman bawaan
    Laravel berbahasa Inggris tanpa satu pun tautan keluar.
--}}
@php
    $kode = '429';
    $judul = 'Terlalu banyak permintaan';
    $pesan = 'Permintaan dari perangkat ini terlalu sering dalam waktu singkat. Tunggu sebentar sebelum mencoba lagi.';
    $saran = [
        'Tunggu kira-kira satu menit, lalu ulangi.',
    ];
@endphp

@include('errors.tata')

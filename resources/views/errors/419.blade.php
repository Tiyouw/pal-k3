{{--
    Halaman galat 419. Menumpang errors/tata.blade.php supaya seluruh halaman
    galat berbunyi seragam dan selalu menawarkan jalan pulang; sebelum ini
    direktori errors/ belum ada sehingga yang tampil adalah halaman bawaan
    Laravel berbahasa Inggris tanpa satu pun tautan keluar.
--}}
@php
    $kode = '419';
    $judul = 'Sesi sudah kedaluwarsa';
    $pesan = 'Halaman dibiarkan terbuka terlalu lama sehingga token keamanannya habis. Isian yang belum terkirim tidak tersimpan.';
    $saran = [
        'Masuk kembali, lalu ulangi langkah terakhir.',
        'Draf inspeksi yang sudah tersimpan tidak hilang; lanjutkan dari daftar tugas.',
    ];
@endphp

@include('errors.tata')

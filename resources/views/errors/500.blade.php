{{--
    Halaman galat 500. Menumpang errors/tata.blade.php supaya seluruh halaman
    galat berbunyi seragam dan selalu menawarkan jalan pulang; sebelum ini
    direktori errors/ belum ada sehingga yang tampil adalah halaman bawaan
    Laravel berbahasa Inggris tanpa satu pun tautan keluar.
--}}
@php
    $kode = '500';
    $judul = 'Sistem sedang bermasalah';
    $pesan = 'Ada kesalahan di sisi server, bukan kesalahan Anda. Kejadian ini sudah tercatat di log sistem.';
    $saran = [
        'Coba ulangi beberapa saat lagi.',
        'Kalau sedang mengisi inspeksi, jawaban yang sudah tersimpan tetap aman.',
        'Kalau berulang, laporkan ke admin K3 sebutkan jam kejadian.',
    ];
@endphp

@include('errors.tata')

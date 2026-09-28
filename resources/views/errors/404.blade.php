{{--
    Halaman galat 404. Menumpang errors/tata.blade.php supaya seluruh halaman
    galat berbunyi seragam dan selalu menawarkan jalan pulang; sebelum ini
    direktori errors/ belum ada sehingga yang tampil adalah halaman bawaan
    Laravel berbahasa Inggris tanpa satu pun tautan keluar.
--}}
@php
    $kode = '404';
    $judul = 'Halaman tidak ditemukan';
    $pesan = 'Alamat yang dibuka tidak ada di sistem ini. Biasanya karena tautan lama, salah ketik, atau data yang sudah dihapus.';
    $saran = [
        'Kalau tadi memindai stiker QR, ulangi pemindaian dari tombol di bawah.',
        'Kalau menyalin tautan dari pesan, cek ada bagian yang terpotong.',
    ];
@endphp

@include('errors.tata')

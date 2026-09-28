{{--
    Halaman galat 403. Menumpang errors/tata.blade.php supaya seluruh halaman
    galat berbunyi seragam dan selalu menawarkan jalan pulang; sebelum ini
    direktori errors/ belum ada sehingga yang tampil adalah halaman bawaan
    Laravel berbahasa Inggris tanpa satu pun tautan keluar.
--}}
@php
    $kode = '403';
    $judul = 'Tidak punya wewenang';
    $pesan = 'Akun Anda tidak berwenang membuka halaman ini. Pembatasan ini disengaja, bukan gangguan sistem.';
    $saran = [
        'Lembar stiker QR dan laporan hanya untuk admin dan pemantau.',
        'Pengisian inspeksi hanya untuk inspektur lewat pindaian QR di depan objek.',
        'Kalau memang perlu akses, minta admin K3 menyesuaikan peran akun Anda.',
    ];
@endphp

@include('errors.tata')

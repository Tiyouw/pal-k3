@extends('layouts.petugas')

@section('judul', 'Pindai Stiker QR')
@section('judul-kepala', 'Pindai Stiker')
@section('sub-kepala', 'Arahkan ke stiker di dinding')

@section('kembali')
    <a href="{{ route('petugas.beranda') }}" aria-label="Kembali ke beranda">&#8592;</a>
@endsection

@push('gaya')
<style>
    #kotak-pindai {
        width: 100%; aspect-ratio: 1; background: #000;
        border-radius: var(--radius); overflow: hidden; position: relative;
    }
    #kotak-pindai video { width: 100% !important; height: 100% !important; object-fit: cover; }
    /* Pustaka menyisipkan garis bantu sendiri; disembunyikan agar tidak bertumpuk
       dengan bingkai bidik milik kita. */
    #kotak-pindai img[alt="Info icon"], #kotak-pindai div#qr-shaded-region { display: none !important; }

    .bidik {
        position: absolute; inset: 18%; border: 3px solid rgba(255,255,255,.9);
        border-radius: 12px; pointer-events: none;
    }
    .bidik::before, .bidik::after {
        content: ''; position: absolute; width: 28px; height: 28px;
        border: 4px solid var(--biru-muda);
    }
    .bidik::before { top: -4px; left: -4px; border-right: 0; border-bottom: 0; border-radius: 8px 0 0 0; }
    .bidik::after  { bottom: -4px; right: -4px; border-left: 0; border-top: 0; border-radius: 0 0 8px 0; }
</style>
@endpush

@section('isi')
    {{-- Peringatan konteks tidak aman ditaruh paling atas dan dicetak di peladen:
         tanpa HTTPS, getUserMedia tidak pernah dipanggil sehingga pesan galat
         pustaka tidak akan pernah muncul dan petugas hanya melihat kotak hitam. --}}
    <noscript>
        <div class="pesan p-merah">
            Pemindai memerlukan JavaScript. Aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>

    <div id="peringatan-aman" class="pesan p-merah" style="display:none" role="alert">
        Halaman ini dibuka tanpa HTTPS sehingga kamera tidak dapat diakses.
        Buka alamat yang berawalan <strong>https://</strong>.
    </div>

    <div class="kartu" style="padding:12px">
        <div id="kotak-pindai" role="region" aria-label="Pratinjau kamera">
            <div class="bidik" aria-hidden="true"></div>
        </div>

        <p id="status-pindai" class="redup" style="margin:12px 2px 0; text-align:center" role="status" aria-live="polite">
            Menyiapkan kamera&hellip;
        </p>

        <div id="galat-pindai" class="pesan p-merah" style="display:none" role="alert"></div>

        <div id="kendali" style="display:none; margin-top:12px">
            <button type="button" id="ganti-kamera" class="tbl tbl-abu tbl-kecil" style="width:100%">
                Ganti kamera
            </button>
        </div>
    </div>

    <div class="kartu">
        <h2>Stiker tidak terbaca?</h2>
        <p class="redup" style="margin:0 0 12px">
            Kalau stiker kotor, pudar, atau kamera tidak mau fokus, masukkan kode
            cadangan yang tercetak di bawah gambar QR.
        </p>

        <form method="GET" id="form-manual" onsubmit="return kirimManual(event)">
            <label class="judul" for="kode">Kode cadangan</label>
            <input type="text" id="kode" name="kode" placeholder="PAL-K3-xxxxxxxx&hellip;"
                   autocapitalize="characters" autocorrect="off" spellcheck="false"
                   style="text-transform:uppercase; font-family:ui-monospace, monospace">
            <p class="galat" id="galat-manual" style="display:none"></p>
            <button type="submit" class="tbl tbl-garis" style="margin-top:12px">Buka inspeksi</button>
        </form>
    </div>
@endsection

@push('skrip')
<script src="{{ asset('vendor/html5-qrcode.min.js') }}"></script>
<script>
(function () {
    const status  = document.getElementById('status-pindai');
    const galat   = document.getElementById('galat-pindai');
    const kendali = document.getElementById('kendali');

    // Pola token stiker. Dipakai dua kali: saat memindai dan saat kode diketik
    // manual, supaya kamera yang menangkap QR milik barang lain (mis. tanda aset
    // inventaris) tidak mengirim petugas ke halaman galat.
    const POLA = /^PAL-K3-[0-9a-f]{32}$/i;

    if (!window.isSecureContext) {
        document.getElementById('peringatan-aman').style.display = 'block';
        status.textContent = 'Kamera tidak tersedia tanpa HTTPS.';
        return;
    }

    if (typeof Html5Qrcode === 'undefined') {
        galat.style.display = 'block';
        galat.textContent = 'Pustaka pemindai gagal dimuat. Gunakan kode cadangan di bawah.';
        status.textContent = '';
        return;
    }

    const pemindai = new Html5Qrcode('kotak-pindai', { verbose: false });
    let kameraAktif = null;
    let daftarKamera = [];
    let indeks = 0;
    let sudahPindah = false;   // kunci agar satu QR tidak memicu dua kali pengalihan

    function tokenDari(teks) {
        // Isi QR berupa URL penuh (https://host/i/TOKEN). Diambil segmen terakhir
        // agar stiker tetap berfungsi walau nama host berubah saat pindah peladen.
        const bersih = String(teks).trim().replace(/\/+$/, '');
        const bagian = bersih.split('/');
        const akhir  = bagian[bagian.length - 1];

        return POLA.test(akhir) ? akhir : (POLA.test(bersih) ? bersih : null);
    }

    function berhasil(teks) {
        if (sudahPindah) return;

        const token = tokenDari(teks);

        if (!token) {
            status.textContent = 'Kode ini bukan stiker inspeksi K3. Coba stiker yang benar.';
            return;
        }

        sudahPindah = true;
        status.textContent = 'Stiker terbaca, membuka inspeksi\u2026';

        // Getaran singkat sebagai umpan balik: di area bising petugas memakai
        // pelindung telinga, dan layar sering tidak terlihat jelas di bawah matahari.
        if (navigator.vibrate) navigator.vibrate(120);

        pemindai.stop().catch(function () {}).then(function () {
            window.location.href = '{{ url('/i') }}/' + token;
        });
    }

    function mulai(idKamera) {
        return pemindai.start(
            idKamera,
            {
                fps: 10,
                // Kotak bidik mengikuti lebar layar: stiker dipindai dari jarak
                // satu lengan, bukan menempel, karena tabung sering terpasang tinggi.
                qrbox: function (lebar, tinggi) {
                    const sisi = Math.floor(Math.min(lebar, tinggi) * 0.68);
                    return { width: sisi, height: sisi };
                },
                aspectRatio: 1,
            },
            berhasil,
            function () { /* bingkai tanpa QR, tidak perlu ditanggapi */ },
        ).then(function () {
            kameraAktif = idKamera;
            status.textContent = 'Arahkan kamera ke stiker QR pada dinding.';
            if (daftarKamera.length > 1) kendali.style.display = 'block';
        });
    }

    Html5Qrcode.getCameras().then(function (kamera) {
        if (!kamera || kamera.length === 0) throw new Error('Tidak ada kamera terdeteksi.');

        daftarKamera = kamera;

        // Kamera belakang dipilih lebih dulu. Tanpa ini sebagian ponsel membuka
        // kamera depan dan petugas memindai wajahnya sendiri.
        const belakang = kamera.findIndex(function (k) {
            return /back|rear|belakang|environment/i.test(k.label || '');
        });

        indeks = belakang >= 0 ? belakang : kamera.length - 1;

        return mulai(kamera[indeks].id);
    }).catch(function (e) {
        galat.style.display = 'block';
        status.textContent = '';

        const nama = e && e.name ? e.name : '';

        galat.textContent =
            nama === 'NotAllowedError'
                ? 'Izin kamera ditolak. Buka pengaturan situs pada peramban, izinkan Kamera, lalu muat ulang halaman.'
                : nama === 'NotFoundError'
                    ? 'Kamera tidak ditemukan pada perangkat ini. Gunakan kode cadangan di bawah.'
                    : 'Kamera gagal dijalankan (' + (e && e.message ? e.message : 'sebab tidak diketahui') + '). Gunakan kode cadangan di bawah.';
    });

    document.getElementById('ganti-kamera').addEventListener('click', function () {
        if (daftarKamera.length < 2) return;

        indeks = (indeks + 1) % daftarKamera.length;
        status.textContent = 'Mengganti kamera\u2026';

        pemindai.stop().catch(function () {}).then(function () {
            return mulai(daftarKamera[indeks].id);
        }).catch(function () {
            galat.style.display = 'block';
            galat.textContent = 'Kamera pilihan tidak dapat dibuka.';
        });
    });

    // Kamera dilepas saat halaman ditinggalkan supaya lampu kamera tidak
    // menyala terus dan baterai tidak terkuras saat berjalan antarlantai.
    window.addEventListener('pagehide', function () {
        pemindai.stop().catch(function () {});
    });

    window.kirimManual = function (ev) {
        ev.preventDefault();

        const isian = document.getElementById('kode');
        const pesan = document.getElementById('galat-manual');
        const nilai = isian.value.trim().toUpperCase();

        if (!POLA.test(nilai)) {
            pesan.style.display = 'block';
            pesan.textContent = 'Kode harus berbentuk PAL-K3- diikuti 32 karakter.';
            isian.focus();
            return false;
        }

        window.location.href = '{{ url('/i') }}/' + nilai.toLowerCase().replace(/^pal-k3-/, 'PAL-K3-');
        return false;
    };
})();
</script>
@endpush

<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\Petugas\AuthController;
use App\Http\Controllers\Petugas\BerandaController;
use App\Http\Controllers\Petugas\InspeksiController;
use App\Http\Controllers\StikerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
| Bagian 3.1: halaman depan berisi profil sistem dan empat kartu modul.
| Tidak memuat data aset apa pun karena dapat dibuka tanpa masuk.
*/

Route::get('/', [LandingController::class, 'index'])->name('landing');

/*
|--------------------------------------------------------------------------
| Pintu masuk petugas
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'form'])->name('petugas.masuk');
    Route::post('/masuk', [AuthController::class, 'masuk'])->name('petugas.masuk.kirim');
});

Route::post('/keluar', [AuthController::class, 'keluar'])
    ->middleware('auth')
    ->name('petugas.keluar');

/*
|--------------------------------------------------------------------------
| Pintasan stiker QR
|--------------------------------------------------------------------------
| Isi QR pada stiker adalah URL lengkap ke /i/{token}. Konsekuensinya stiker
| tetap berguna walau dibuka dengan aplikasi kamera bawaan ponsel, tanpa perlu
| memasang aplikasi apa pun.
|
| Token acak 32 heksadesimal, BUKAN nomor APAR (Bagian 5.2). Nomor aset yang
| dapat diterka membuat inspeksi bisa diisi dari kantin tanpa mendekati tabung.
|
| Rute sengaja singkat: semakin pendek URL, semakin kecil dan rapat modul QR,
| sehingga stiker masih terbaca kamera walau tinta sudah pudar.
*/

Route::get('/i/{token}', [InspeksiController::class, 'mulai'])
    ->middleware('auth')
    ->name('petugas.inspeksi.mulai');

/*
|--------------------------------------------------------------------------
| Antarmuka lapangan
|--------------------------------------------------------------------------
| Seluruh rute di bawah ini dipakai dari ponsel. Middleware inspektur menjaga
| agar admin yang salah membuka alamat ini diarahkan ke panelnya, bukan melihat
| antarmuka yang bukan untuknya.
*/

Route::middleware(['auth', 'inspektur'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', [BerandaController::class, 'index'])->name('beranda');
    Route::get('/riwayat', [BerandaController::class, 'riwayat'])->name('riwayat');
    Route::get('/pindai', [InspeksiController::class, 'pemindai'])->name('pindai');

    Route::prefix('inspeksi/{inspeksi}')->name('inspeksi.')->group(function () {
        Route::get('/', [InspeksiController::class, 'isi'])->name('isi');
        Route::post('/lokasi', [InspeksiController::class, 'lokasi'])->name('lokasi');
        Route::post('/jawaban', [InspeksiController::class, 'simpanJawaban'])->name('jawaban');
        Route::post('/foto', [InspeksiController::class, 'unggahFoto'])->name('foto');
        Route::delete('/foto/{foto}', [InspeksiController::class, 'hapusFoto'])->name('foto.hapus');
        Route::post('/kirim', [InspeksiController::class, 'kirim'])->name('kirim');
        Route::delete('/', [InspeksiController::class, 'batal'])->name('batal');
        Route::get('/selesai', [InspeksiController::class, 'selesai'])->name('selesai');
    });
});

/*
|--------------------------------------------------------------------------
| Halaman pengelolaan di luar panel
|--------------------------------------------------------------------------
| Lembar stiker QR dicetak dari peramban, bukan diunduh sebagai PDF, supaya
| pengelola dapat memeriksa pratinjau dan memilih rentang halaman sendiri.
|
| Hanya peran panel yang diizinkan. Bila inspektur dapat membuka halaman ini,
| dia dapat memindai kode dari mana saja dan pemindaian berhenti menjadi bukti
| kehadiran di depan objek.
*/

Route::middleware(['auth', 'pengelola'])->group(function () {
    Route::get('/stiker', [StikerController::class, 'index'])->name('stiker.index');

    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/pms', [LaporanController::class, 'pms'])->name('pms');
        Route::get('/temuan', [LaporanController::class, 'temuan'])->name('temuan');
        Route::get('/kartu/{asset}', [LaporanController::class, 'kartu'])->name('kartu');
    });
});

/*
|--------------------------------------------------------------------------
| Penadah alamat tak dikenal
|--------------------------------------------------------------------------
| Alamat yang tidak cocok rute mana pun tidak pernah melewati middleware web,
| sehingga sesi belum dibaca dan auth()->user() bernilai null di halaman 404.
| Akibatnya petugas yang sedang masuk tetap disuguhi tombol "Masuk dengan NIP"
| alih-alih jalan pulang ke daftar tugasnya.
|
| Verifikasi di situs hidup yang menemukan ini; test feature memberi hijau palsu
| karena actingAs() menyuntik pengguna langsung ke container dan melewati sesi.
|
| Rute ini WAJIB berada di baris terakhir: pencocokan Laravel berurutan, jadi
| menaruhnya lebih awal akan menelan seluruh rute di bawahnya.
*/
Route::fallback(function () {
    abort(404);
})->middleware('web');

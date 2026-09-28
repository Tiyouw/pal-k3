<?php

/**
 * Pesan otentikasi Bahasa Indonesia.
 *
 * "throttle" dipakai oleh pembatas percobaan bawaan Laravel dan Filament.
 * Pintu masuk petugas punya pembatasnya sendiri di Petugas\AuthController
 * dengan pesan yang sudah berbahasa Indonesia; berkas ini menutup jalur
 * bawaan yang tersisa, terutama halaman masuk panel /admin.
 */
return [
    'failed' => 'NIP atau kata sandi tidak cocok.',
    'password' => 'Kata sandi salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',
];

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * inspected_at adalah waktu KIRIM, bukan waktu mulai.
 *
 * Migrasi awal menandainya NOT NULL, padahal inspeksi lahir sebagai draf saat
 * gerbang QR dibuka dan baru diberi inspected_at ketika petugas menekan kirim
 * (InspeksiController::kirim). Akibatnya setiap pemindaian stiker gagal insert
 * dengan "NOT NULL constraint failed: inspections.inspected_at" dan rute
 * /i/{token} selalu balas 500.
 *
 * Kolom dijadikan nullable supaya draf sah tanpa waktu kirim. Waktu mulai tetap
 * tersimpan di mulai_pada, jadi durasi pengerjaan masih bisa dihitung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->timestamp('inspected_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Draf yang belum dikirim tidak punya waktu kirim yang benar. Daripada
        // mengarang nilai, baris draf dibuang dulu supaya constraint bisa
        // dipasang ulang tanpa data palsu.
        Schema::table('inspections', function (Blueprint $table) {
            $table->timestamp('inspected_at')->nullable(false)->change();
        });
    }
};

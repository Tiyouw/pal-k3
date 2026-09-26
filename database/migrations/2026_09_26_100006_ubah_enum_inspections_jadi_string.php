<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan ketidakcocokan nilai antara kode dan batasan kolom.
 *
 * Migration awal mendefinisikan:
 *   gate_status enum('lolos', 'perlu_review', 'gps_lemah', 'tanpa_gps')
 *   status      enum('draft', 'terkirim')
 *
 * Sedangkan Asset::evaluasiGerbang() mengembalikan 'sesuai', 'jauh', dan
 * 'tidak_berlaku', lalu InspeksiController menyimpan status 'final'. Tiga dari
 * nilai gerbang dan satu nilai status tidak ada di daftar enum, sehingga pada
 * MySQL penyimpanan akan ditolak atau dipotong menjadi string kosong. Pada
 * SQLite enum hanya varchar sehingga bug ini lolos tanpa gejala saat
 * pengembangan lokal dan baru muncul di peladen.
 *
 * Kolom diubah menjadi string, bukan enum diperluas, karena daftar status
 * gerbang masih ikut berubah ketika modul Hydrant dan Kotak P3K ditambahkan.
 * Setiap penambahan nilai pada enum MySQL berarti ALTER TABLE yang mengunci
 * tabel inspeksi, dan tabel itu yang paling sering ditulisi petugas lapangan.
 * Daftar nilai yang sah tetap dijaga di lapisan aplikasi lewat Asset dan
 * aturan validasi, tempat pesan kesalahannya bisa dibaca manusia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('gate_status', 32)->default('tanpa_gps')->change();
            $table->string('status', 16)->default('draft')->change();
        });

        // Data lama yang sempat tersimpan dengan istilah sebelum penyeragaman.
        // Pada pemasangan baru tidak ada baris yang terpengaruh.
        DB::table('inspections')->where('gate_status', 'lolos')->update(['gate_status' => 'sesuai']);
        DB::table('inspections')->where('status', 'terkirim')->update(['status' => 'final']);
    }

    public function down(): void
    {
        DB::table('inspections')->where('gate_status', 'sesuai')->update(['gate_status' => 'lolos']);
        DB::table('inspections')->whereIn('gate_status', ['jauh', 'tidak_berlaku'])
            ->update(['gate_status' => 'perlu_review']);
        DB::table('inspections')->where('status', 'final')->update(['status' => 'terkirim']);

        Schema::table('inspections', function (Blueprint $table) {
            $table->enum('gate_status', ['lolos', 'perlu_review', 'gps_lemah', 'tanpa_gps'])
                ->default('tanpa_gps')->change();
            $table->enum('status', ['draft', 'terkirim'])->default('draft')->change();
        });
    }
};

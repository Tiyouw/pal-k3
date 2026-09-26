<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian 6.1 + 5.7 rancangan.
 *
 * kesimpulan   : Layak pakai / Layak dengan catatan / Tidak layak pakai.
 * mulai_pada   : dicatat saat gerbang QR dibuka, bukan saat kirim.
 *                Selisih dengan inspected_at = durasi pengerjaan.
 * durasi_detik : dihitung di peladen. Dipakai menandai "35 aset dalam 2 menit"
 *                (Bagian 5.7 baris "selang waktu antarpemindaian dicatat").
 * device_time  : waktu ponsel, HANYA untuk pembanding. Waktu resmi tetap dari peladen.
 * ua           : jejak peramban, dipakai admin saat meninjau anomali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('kesimpulan')->nullable()->after('status');
            $table->timestamp('mulai_pada')->nullable()->after('inspected_at');
            $table->integer('durasi_detik')->nullable()->after('mulai_pada');
            $table->timestamp('device_time')->nullable()->after('durasi_detik');
            $table->string('ua', 255)->nullable()->after('device_time');
            $table->timestamp('ditinjau_pada')->nullable()->after('kesimpulan');
            $table->foreignId('ditinjau_oleh')->nullable()->after('ditinjau_pada')
                ->constrained('users')->nullOnDelete();
            $table->text('catatan_review')->nullable()->after('ditinjau_oleh');

            // Indeks ['asset_id','inspected_at'] TIDAK didaftarkan di sini.
            // Migration create_inspections_table sudah membuatnya, dan mendaftar
            // ulang membuat migrate:fresh gagal dengan "index ... already exists".
            $table->index(['gate_status', 'inspected_at']);
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropIndex(['gate_status', 'inspected_at']);
            $table->dropConstrainedForeignId('ditinjau_oleh');
            $table->dropColumn([
                'kesimpulan', 'mulai_pada', 'durasi_detik', 'device_time',
                'ua', 'ditinjau_pada', 'catatan_review',
            ]);
        });
    }
};

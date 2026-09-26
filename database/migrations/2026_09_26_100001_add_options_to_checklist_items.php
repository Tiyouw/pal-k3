<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian 4.4 rancangan: tipe jawaban "select" butuh daftar pilihan per item.
 * Disimpan JSON supaya penambahan pilihan tidak perlu migrasi baru.
 *
 * severity_map: pilihan mana yang memicu temuan, beserta tingkat keparahannya.
 * Contoh Volume: {"Tidak penuh":"berat"} -> pilihan lain tidak memicu temuan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->json('options')->nullable()->after('answer_type');
            $table->json('severity_map')->nullable()->after('severity_default');
            $table->text('keterangan')->nullable()->after('dasar_hukum');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn(['options', 'severity_map', 'keterangan']);
        });
    }
};

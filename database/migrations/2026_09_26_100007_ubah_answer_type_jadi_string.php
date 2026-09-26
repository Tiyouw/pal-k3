<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan ketidakcocokan nilai pada checklist_items.answer_type.
 *
 * Migration awal: enum('fungsi', 'kondisi', 'number', 'text').
 * ChecklistItem::TIPE mendaftarkan tujuh nilai: fungsi, kondisi, boolean,
 * select, number, date, text. Tiga di antaranya tidak ada di enum, dan
 * MasterSeeder memakai 'boolean' tujuh kali serta 'select' tiga kali, jadi
 * penyemaian berhenti di butir pertama dengan CHECK constraint failed.
 *
 * Daftar tipe jawaban adalah urusan aplikasi, bukan urusan penyimpanan.
 * Butir periksa Hydrant, Kotak P3K, dan Mobil Pemadam masih akan menambah
 * tipe baru, dan tiap penambahan seharusnya cukup menyentuh satu konstanta
 * PHP, bukan menulis migration pengubah skema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->string('answer_type', 16)->default('kondisi')->change();
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->enum('answer_type', ['fungsi', 'kondisi', 'number', 'text'])
                ->default('kondisi')->change();
        });
    }
};

<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian 3.3 rancangan menyebut tiga peran: Inspektur, Admin, Pemantau.
 *
 * Migrasi awal menulis enum ['admin','supervisor','inspektur'] sehingga nilai
 * 'pemantau' ditolak di tingkat basis data. Kolom diubah menjadi string biasa
 * dengan daftar nilai dijaga di lapisan aplikasi (User::LABEL_ROLE + validasi
 * formulir), bukan di skema.
 *
 * Alasan tidak memakai enum baru: setiap penambahan peran pada enum menuntut
 * migrasi yang membangun ulang tabel, dan pada SQLite pembangunan ulang tabel
 * yang sudah punya kunci asing ke inspections berisiko. String + validasi
 * aplikasi memberi keleluasaan yang sama tanpa risiko itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pindahkan dulu data lama: supervisor adalah nama lain dari pemantau.
        DB::table('users')->where('role', 'supervisor')->update(['role' => User::ROLE_PEMANTAU]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default(User::ROLE_INSPEKTUR)->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', User::ROLE_PEMANTAU)->update(['role' => 'supervisor']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'supervisor', 'inspektur'])
                ->default('inspektur')
                ->change();
        });
    }
};

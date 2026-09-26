<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Inspektur masuk pakai NIP, bukan surel.
            $table->string('nip', 32)->nullable()->unique()->after('name');
            // admin      = kelola master data + cetak laporan
            // supervisor = verifikasi hasil inspeksi
            // inspektur  = hanya isi checklist di lapangan, TIDAK boleh masuk panel admin
            $table->enum('role', ['admin', 'supervisor', 'inspektur'])
                  ->default('inspektur')
                  ->after('email');
            $table->string('jabatan')->nullable()->after('role');
            $table->boolean('aktif')->default(true)->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip']);
            $table->dropColumn(['nip', 'role', 'jabatan', 'aktif']);
        });
    }
};

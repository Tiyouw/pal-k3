<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan division_id pada users.
 *
 * UserSeeder sudah memetakan tiap peran ke satu divisi (admin ke K3LH,
 * inspektur ke PIP, pemantau ke TI), tetapi kolomnya tidak pernah dibuat.
 * Penjaganya memakai $user->isFillable('division_id') yang hanya memeriksa
 * daftar atribut model, bukan skema tabel, sehingga pemeriksaan itu lolos dan
 * penyemaian gagal dengan "no such column: division_id".
 *
 * Kolom dibuat, bukan blok seeder dihapus, karena laporan bulanan PMS
 * ditandatangani per divisi dan nama penanggung jawab diambil dari petugas.
 * Tanpa kolom ini, keterkaitan petugas dengan divisi tidak punya tempat
 * tinggal selain ditebak dari aset yang pernah dia periksa.
 *
 * nullable: petugas yang lintas divisi atau belum ditempatkan tetap sah.
 * nullOnDelete: divisi yang dibubarkan tidak boleh menghapus riwayat petugas,
 * karena inspeksi yang pernah dia kirim harus tetap dapat dilacak ke orangnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('jabatan')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian 4.2 rancangan: tabel schedules (penjadwalan berkala).
 *
 * Satu baris = satu kewajiban inspeksi pada satu periode untuk satu aset.
 * jatuh_tempo dihitung dari asset_types.periode_hari (APAR = 30 hari).
 *
 * Dipakai papan pantau kepatuhan: terlaksana / telat / belum,
 * supaya angka kepatuhan tidak ditebak dari tanggal terakhir saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('periode');      // tanggal 1 bulan bersangkutan
            $table->date('jatuh_tempo');
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('belum'); // belum | selesai | telat
            $table->timestamps();

            $table->unique(['asset_id', 'periode']);
            $table->index(['periode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

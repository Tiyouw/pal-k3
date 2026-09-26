<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kode');                    // nomor aset, mis. 01D
            $table->string('gedung')->nullable();
            $table->string('lantai')->nullable();
            $table->string('lokasi_teks');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('radius_m')->nullable(); // ambang bukti GPS
            $table->string('qr_token', 64)->unique();  // acak, bukan nomor aset
            $table->json('attributes')->nullable();    // {tipe, kapasitas_kg, ...}
            $table->date('tgl_expired')->nullable();
            $table->date('terakhir_dicek')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['asset_type_id', 'kode']);
            $table->index(['asset_type_id', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

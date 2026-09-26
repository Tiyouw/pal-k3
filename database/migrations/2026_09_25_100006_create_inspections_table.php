<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // waktu peladen, bukan waktu perangkat -> cegah putar balik jam ponsel
            $table->timestamp('inspected_at');
            $table->decimal('gps_lat', 10, 7)->nullable();
            $table->decimal('gps_lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('gps_accuracy')->nullable(); // meter
            $table->unsignedInteger('jarak_m')->nullable();           // ke titik aset
            $table->boolean('qr_verified')->default(false);
            // lolos | perlu_review | gps_lemah | tanpa_gps
            $table->enum('gate_status', ['lolos', 'perlu_review', 'gps_lemah', 'tanpa_gps'])
                  ->default('perlu_review');
            $table->text('catatan')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->enum('status', ['draft', 'terkirim'])->default('draft');
            $table->timestamps();

            $table->index(['asset_id', 'inspected_at']);
            $table->index(['inspected_at', 'gate_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};

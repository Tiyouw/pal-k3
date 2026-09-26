<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_group_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            // fungsi  = dua nilai centang/silang  (dioperasikan)
            // kondisi = dua nilai O / slashed-O    (dilihat saja)
            // number  = angka, dibandingkan vs jumlah_baku (kotak P3K)
            // text    = catatan bebas
            $table->enum('answer_type', ['fungsi', 'kondisi', 'number', 'text'])->default('kondisi');
            $table->unsignedSmallInteger('jumlah_baku')->nullable(); // acuan Permenakertrans 15/2008
            $table->string('satuan', 24)->nullable();
            $table->string('dasar_hukum')->nullable();  // mis. Permenaker 4/1980 Pasal 12(1)a
            $table->enum('severity_default', ['ringan', 'sedang', 'berat'])->default('sedang');
            $table->boolean('wajib')->default(true);
            $table->unsignedSmallInteger('urut')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['checklist_group_id', 'urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};

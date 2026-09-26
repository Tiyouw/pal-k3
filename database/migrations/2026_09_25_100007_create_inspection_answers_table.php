<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained()->restrictOnDelete();
            // fungsi:  ok | tidak      -> cetak centang / silang
            // kondisi: ok | tidak      -> cetak O / slashed-O
            // number:  simpan di nilai_angka
            $table->string('nilai', 32)->nullable();
            $table->integer('nilai_angka')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'checklist_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_answers');
    }
};

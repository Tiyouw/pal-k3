<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')->constrained()->cascadeOnDelete();
            $table->string('kode', 8)->nullable();     // A, B, C, D
            $table->string('nama');
            $table->unsignedSmallInteger('urut')->default(0);
            $table->timestamps();

            $table->index(['asset_type_id', 'urut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_groups');
    }
};

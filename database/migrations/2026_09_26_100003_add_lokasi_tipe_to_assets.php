<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian 5.5 rancangan: radius mengikuti JENIS LOKASI, bukan satu angka global.
 *
 *   dalam_gedung  -> 75 m  (beton + rangka baja; lantai sudah ditentukan QR)
 *   bengkel       -> 60 m  (bangunan luas)
 *   area_terbuka  -> 30 m  (langit terbuka, ketepatan lebih baik)
 *   kendaraan     -> null  (mobil damkar memang berpindah; gerbang tidak berlaku)
 *
 * radius_override = kolom radius_m yang sudah ada. Kalau diisi, dia menang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('lokasi_tipe')->default('dalam_gedung')->after('lokasi_teks');
            $table->index('lokasi_tipe');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['lokasi_tipe']);
            $table->dropColumn('lokasi_tipe');
        });
    }
};

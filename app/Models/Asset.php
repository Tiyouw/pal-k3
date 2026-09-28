<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory;

    /** GPS di dalam beton buruk; ambang bawaan dipakai kalau lokasi_tipe tak dikenal. */
    public const RADIUS_BAWAAN_M = 75;

    /**
     * Bagian 5.6 rancangan. Di atas ambang ini GPS dianggap tidak bisa dipercaya,
     * BUKAN pelanggaran. Tanpa pembedaan ini, hari berawan menghasilkan puluhan
     * penandaan palsu dan fitur penandaan kehilangan gunanya dalam satu bulan.
     */
    public const AKURASI_LEMAH_M = 100;

    /** Bagian 5.5: radius mengikuti jenis lokasi, bukan satu angka global. */
    public const RADIUS_PER_LOKASI = [
        'dalam_gedung' => 75,
        'bengkel'      => 60,
        'area_terbuka' => 30,
        'kendaraan'    => null, // objek bergerak; gerbang GPS tidak diberlakukan
    ];

    public const LABEL_LOKASI = [
        'dalam_gedung' => 'Dalam gedung bertingkat',
        'bengkel'      => 'Bengkel atau gudang',
        'area_terbuka' => 'Area terbuka',
        'kendaraan'    => 'Kendaraan (bergerak)',
    ];

    protected $fillable = [
        'asset_type_id', 'division_id', 'kode', 'gedung', 'lantai', 'lokasi_teks',
        'lokasi_tipe', 'lat', 'lng', 'radius_m', 'qr_token', 'attributes',
        'tgl_expired', 'terakhir_dicek', 'aktif',
    ];

    protected $casts = [
        'attributes'     => 'array',
        'lat'            => 'float',
        'lng'            => 'float',
        'radius_m'       => 'integer',
        'tgl_expired'    => 'date',
        'terakhir_dicek' => 'date',
        'aktif'          => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $asset) {
            $asset->qr_token ??= self::buatToken();
        });
    }

    /** Token acak, bukan nomor aset. Nomor aset bisa terbaca orang; token tidak bisa ditebak. */
    public static function buatToken(): string
    {
        return 'PAL-K3-' . bin2hex(random_bytes(16));
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class)->latest('inspected_at');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Bagian 5.6: radius = radius_override ?? radius_menurut_jenis_lokasi.
     * null berarti gerbang GPS tidak diberlakukan (kendaraan).
     */
    public function radiusEfektif(): ?int
    {
        if ($this->radius_m) {
            return (int) $this->radius_m;
        }

        $tipe = $this->lokasi_tipe ?: 'dalam_gedung';

        if (! array_key_exists($tipe, self::RADIUS_PER_LOKASI)) {
            return self::RADIUS_BAWAAN_M;
        }

        return self::RADIUS_PER_LOKASI[$tipe];
    }

    /**
     * Isi kolom JSON attributes.
     *
     * WAJIB dipakai alih-alih $asset->attributes. Eloquent sudah memakai nama
     * attributes untuk properti internalnya yang berisi seluruh nilai kolom,
     * sehingga penulisan langsung mengembalikan larik kolom mentah, bukan isi
     * kolom JSON, dan pada konteks di luar kelas memicu galat akses properti
     * terlindung. Nama kolom di basis data tetap attributes agar tidak perlu
     * migrasi ulang.
     */
    public function atribut(?string $kunci = null, mixed $bawaan = null): mixed
    {
        $data = $this->getAttributeValue('attributes') ?? [];

        if (! is_array($data)) {
            $data = [];
        }

        return $kunci === null ? $data : ($data[$kunci] ?? $bawaan);
    }

    /** Keterangan jenis media dan kapasitas untuk ditampilkan di layar petugas. */
    public function labelMedia(): ?string
    {
        $jenis     = $this->atribut('jenis');
        $kapasitas = $this->atribut('kapasitas_kg');

        if (! $jenis && ! $kapasitas) {
            return null;
        }

        return trim(($jenis ?: 'APAR') . ($kapasitas ? ' / ' . $kapasitas . ' kg' : ''));
    }

    public function labelLokasiTipe(): string
    {
        return self::LABEL_LOKASI[$this->lokasi_tipe ?? ''] ?? 'Dalam gedung bertingkat';
    }

    /** Jarak haversine dalam meter. null kalau salah satu titik tidak punya koordinat. */
    public function jarakDari(?float $lat, ?float $lng): ?int
    {
        if ($lat === null || $lng === null || $this->lat === null || $this->lng === null) {
            return null;
        }

        $R = 6371000.0;
        $dLat = deg2rad($lat - $this->lat);
        $dLng = deg2rad($lng - $this->lng);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($this->lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round($R * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /**
     * Aturan Bagian 5.6 rancangan, apa adanya:
     *
     *   radius  = radius_override ?? radius_menurut_jenis_lokasi
     *   akurasi = gps_accuracy dari peramban, dalam meter
     *
     *   jika akurasi > 100        -> gps_lemah
     *   lainnya jika jarak <= r   -> sesuai
     *   lainnya jika jarak <= 2r  -> perlu_review
     *   lainnya                   -> jauh
     *
     * PENTING: pada SEMUA keadaan di atas pengiriman tetap diterima.
     * Yang membedakan hanya penandaan dan apakah masuk antrean tinjauan admin.
     * Satu penolakan salah membuat inspektur kembali ke kertas.
     */
    public function evaluasiGerbang(?float $lat, ?float $lng, ?int $accuracy): array
    {
        $radius = $this->radiusEfektif();
        $jarak  = $this->jarakDari($lat, $lng);

        // Objek bergerak (mobil damkar): gerbang GPS memang tidak diberlakukan.
        if ($radius === null) {
            return ['status' => 'tidak_berlaku', 'jarak_m' => $jarak, 'radius_m' => null];
        }

        if ($jarak === null) {
            return ['status' => 'tanpa_gps', 'jarak_m' => null, 'radius_m' => $radius];
        }

        if ($accuracy !== null && $accuracy > self::AKURASI_LEMAH_M) {
            return ['status' => 'gps_lemah', 'jarak_m' => $jarak, 'radius_m' => $radius];
        }

        /*
         * Akurasi yang dilaporkan peramban adalah jari-jari ketidakpastian, jadi
         * ia ditambahkan ke ambang, bukan diabaikan. Bacaan 40 m dengan akurasi
         * 20 m berarti posisi sebenarnya ada di antara 20 m dan 60 m: menyebutnya
         * menyimpang berarti menghukum inspektur atas keterbatasan alat.
         *
         * Akurasi di atas AKURASI_LEMAH_M sudah dipagari di atas, sehingga
         * pelebaran ini terbatas dan tidak bisa dipakai memuluskan posisi yang
         * benar-benar jauh.
         */
        $toleransi = $radius + (int) ($accuracy ?? 0);

        $status = match (true) {
            $jarak <= $toleransi     => 'sesuai',
            $jarak <= $toleransi * 2 => 'perlu_review',
            default                  => 'jauh',
        };

        return [
            'status'    => $status,
            'jarak_m'   => $jarak,
            'radius_m'  => $radius,
            'toleransi' => $toleransi,
        ];
    }

    /** Status yang masuk antrean tinjauan admin (Bagian 8.2 /admin/penyimpangan). */
    public static function statusPerluTinjauan(): array
    {
        return ['perlu_review', 'jauh'];
    }

    public function jatuhTempo(): bool
    {
        if (! $this->terakhir_dicek) {
            return true;
        }

        $periode = $this->assetType?->periode_hari ?? 30;

        return $this->terakhir_dicek->addDays($periode)->isPast();
    }

    public function namaTampil(): string
    {
        return trim($this->kode . ' - ' . Str::limit($this->lokasi_teks, 40));
    }

    /**
     * Label lantai yang siap dicetak, atau null kalau lantainya tak bermakna.
     *
     * Kolom lantai di CSV sumber sudah berisi awalan sendiri ("Lt. 1"), jadi
     * view yang menambahkan "Lt. " lagi menghasilkan "LT. LT. 1" di stiker dan
     * dasbor. Aset di area terbuka diisi "-", yang kalau diawali jadi "Lt. -".
     * Keduanya diselesaikan di satu tempat supaya tiap view tak perlu tahu
     * bentuk data mentahnya.
     */
    public function labelLantai(): ?string
    {
        $lantai = trim((string) $this->lantai);

        // Tanda hubung dan strip panjang dipakai di data sebagai "tanpa lantai".
        if ($lantai === '' || in_array($lantai, ['-', '–', '—'], true)) {
            return null;
        }

        // Sudah berawalan Lt./Lantai dalam bentuk apa pun: pakai apa adanya.
        if (preg_match('/^(lt\.?|lantai)\s/i', $lantai)) {
            return $lantai;
        }

        return 'Lt. ' . $lantai;
    }

    /**
     * Satu baris lokasi: gedung ditambah lantai kalau ada, dipisah titik tengah.
     * Dipakai stiker, dasbor petugas, dan laporan supaya bunyinya seragam.
     */
    public function labelLokasi(string $pemisah = ' · '): string
    {
        $bagian = array_filter([
            $this->lokasi_teks ?: $this->gedung,
            $this->labelLantai(),
        ]);

        return implode($pemisah, $bagian) ?: '-';
    }
}

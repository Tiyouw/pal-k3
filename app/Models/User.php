<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'nip', 'email', 'role', 'jabatan', 'division_id', 'aktif', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Tiga peran Bagian 3.3 rancangan. */
    public const ROLE_ADMIN     = 'admin';     // komputer: data induk, laporan, tinjauan
    public const ROLE_PEMANTAU  = 'pemantau';  // komputer: hanya papan pantau dan laporan
    public const ROLE_INSPEKTUR = 'inspektur'; // ponsel: pindai QR, isi checklist

    /**
     * Peran yang boleh membuka panel admin.
     *
     * Inspektur sengaja TIDAK masuk daftar: alat kerjanya antarmuka lapangan di
     * /petugas, bukan panel. Pemantau masuk, tetapi kewenangannya dibatasi per
     * resource lewat Policy (hanya lihat, tidak boleh ubah data induk).
     */
    public const ROLE_PANEL = [self::ROLE_ADMIN, self::ROLE_PEMANTAU];

    public const LABEL_ROLE = [
        self::ROLE_ADMIN     => 'Administrator K3',
        self::ROLE_PEMANTAU  => 'Pemantau',
        self::ROLE_INSPEKTUR => 'Inspektur',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'aktif'             => 'boolean',
        ];
    }

    /**
     * Tanpa metode ini Filament abort 403 di semua env selain local
     * (vendor/filament/filament/src/Http/Middleware/Authenticate.php).
     * Pengecekan peran wajib: kalau hanya mengembalikan true, inspektur lapangan
     * ikut bisa masuk panel admin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->aktif && in_array($this->role, self::ROLE_PANEL, true);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Divisi penempatan petugas. Dipakai pada blok tanda tangan laporan
     * bulanan PMS, yang ditandatangani per divisi.
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isInspektur(): bool
    {
        return $this->role === self::ROLE_INSPEKTUR;
    }

    /**
     * Pemantau hanya membaca papan pantau dan laporan. Dipakai untuk menutup
     * tombol tulis di panel: sebelum ini pemantau bisa mengubah dan menghapus
     * data induk karena Resource tanpa aturan wewenang dianggap terbuka oleh
     * Filament.
     */
    public function isPemantau(): bool
    {
        return $this->role === self::ROLE_PEMANTAU;
    }

    /**
     * Boleh membuka halaman pengelolaan di luar panel Filament: lembar stiker QR
     * dan laporan bulanan. Sengaja dipisah dari canAccessPanel() supaya syarat
     * Filament dan syarat halaman biasa dapat berbeda tanpa saling menarik.
     */
    public function bolehPanel(): bool
    {
        return $this->aktif && in_array($this->role, self::ROLE_PANEL, true);
    }
}

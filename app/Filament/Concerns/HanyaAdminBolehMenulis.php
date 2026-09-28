<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Menutup jalur tulis di panel untuk peran selain admin.
 *
 * Alasan trait ini ada: Filament menganggap Resource tanpa aturan wewenang
 * sebagai terbuka untuk siapa pun yang bisa masuk panel. Karena pemantau juga
 * boleh masuk panel (User::ROLE_PANEL), ia jadi bisa mengubah dan menghapus
 * data induk. Aturannya dikumpulkan di satu tempat supaya tak disalin ke tiap
 * Resource dan tak ada yang tertinggal saat Resource baru ditambahkan.
 *
 * Membaca tetap terbuka: tugas pemantau memang memantau.
 */
trait HanyaAdminBolehMenulis
{
    protected static function penggunaAdalahAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canRestore(Model $record): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canRestoreAny(): bool
    {
        return static::penggunaAdalahAdmin();
    }

    public static function canReplicate(Model $record): bool
    {
        return static::penggunaAdalahAdmin();
    }
}

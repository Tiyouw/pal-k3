<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login;
use Illuminate\Validation\ValidationException;

class Masuk extends Login
{
    protected static string $view = 'filament.auth.masuk';

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('nip')->label('NIP')->required()->maxLength(32)->autocomplete('username')->placeholder('Masukkan NIP Anda');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return ['nip' => $data['nip'], 'password' => $data['password'], 'aktif' => true];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages(['data.nip' => 'NIP atau kata sandi tidak cocok, atau akun tidak memiliki akses panel.']);
    }
}

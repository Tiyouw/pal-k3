<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class DemoUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $password = $user->getAuthPassword();
        $provided = $credentials['password'] ?? null;

        if (! is_string($password) || ! is_string($provided) || $provided === '') {
            return false;
        }

        if (! Hash::isHashed($password)) {
            return $user instanceof User && $user->isDemoAccount()
                && hash_equals($password, $provided);
        }

        return parent::validateCredentials($user, $credentials);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        if ($user instanceof User && $user->isDemoAccount() && ! Hash::isHashed($user->getAuthPassword())) {
            return;
        }

        parent::rehashPasswordIfRequired($user, $credentials, $force);
    }
}

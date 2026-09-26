<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Default sengaja inspektur: peran paling kecil haknya.
     * Tes yang butuh panel admin harus menyatakannya lewat state admin() atau supervisor(),
     * supaya hak akses tidak pernah kebetulan lolos.
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'nip'               => fake()->unique()->numerify('##########'),
            'email'             => fake()->unique()->safeEmail(),
            'role'              => User::ROLE_INSPEKTUR,
            'jabatan'           => 'Petugas K3',
            'aktif'             => true,
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'    => User::ROLE_ADMIN,
            'jabatan' => 'Kepala Divisi K3LH',
        ]);
    }

    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'    => User::ROLE_SUPERVISOR,
            'jabatan' => 'Supervisor K3LH',
        ]);
    }

    public function inspektur(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'    => User::ROLE_INSPEKTUR,
            'jabatan' => 'Petugas K3',
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => ['aktif' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

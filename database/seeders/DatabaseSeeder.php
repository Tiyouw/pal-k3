<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan wajib: divisi dulu (MasterSeeder), karena UserSeeder dan
        // AparSeeder keduanya menunjuk ke divisi.
        $this->call([
            MasterSeeder::class,
            UserSeeder::class,
            AparSeeder::class,
        ]);
    }
}

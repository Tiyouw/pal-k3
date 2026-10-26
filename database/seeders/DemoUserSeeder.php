<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.enabled')) {
            $this->command?->info('Mode demo nonaktif; akun demonstrasi tidak diubah.');

            return;
        }

        DB::transaction(function (): void {
            foreach (config('demo.accounts') as $username => $account) {
                User::updateOrCreate(['nip' => $username], [
                    'name' => $account['name'],
                    'email' => $username.'@demo.pal.test',
                    'role' => $account['role'],
                    'jabatan' => 'Akun demonstrasi stakeholder',
                    'division_id' => Division::where('kode', $account['division'])->value('id'),
                    'aktif' => true,
                    'password' => $account['password'],
                ]);
            }
        });

        $this->command?->info('Tiga akun demo siap: admin, pemantau, inspektur.');
    }
}

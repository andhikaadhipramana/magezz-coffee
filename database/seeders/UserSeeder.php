<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pindahkan akun manager lama ke email baru agar data terkait
        // (transaksi, shift, absensi) tidak ikut terhapus, tanpa melewati unique email.
        $legacyManager = User::query()->where('email', 'manager@magezz.test')->first();
        $newManager = User::query()->where('email', 'andhika@magezz.test')->first();

        if ($legacyManager && ! $newManager) {
            $legacyManager->update(['email' => 'andhika@magezz.test']);
        }

        $users = [
            [
                'name' => 'Andhika',
                'email' => 'andhika@magezz.test',
                'role' => User::ROLE_MANAGER,
                'phone' => '081200000002',
            ],
            [
                'name' => 'Alexander',
                'email' => 'alexander@magezz.test',
                'role' => User::ROLE_KASIR,
                'phone' => '081200000004',
            ],
            [
                'name' => 'Veronica',
                'email' => 'veronica@magezz.test',
                'role' => User::ROLE_KASIR,
                'phone' => '081200000005',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                array_merge($user, [
                    'password' => 'password',
                    'is_active' => true,
                ])
            );
        }
    }
}

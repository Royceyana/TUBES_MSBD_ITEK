<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun contoh per role. Aman dijalankan berulang: akun yang emailnya sudah ada
     * (mis. dari kafe_kaleng.sql) tidak diubah. Kata sandi awal: kaleng123.
     * Data lain (menu, kategori, bahan, resep) berasal dari kafe_kaleng.sql.
     */
    public function run(): void
    {
        $accounts = [
            ['Admin Kafe', 'admin@kafekaleng.test', 'admin'],
            ['Kasir 1', 'kasir@kafekaleng.test', 'kasir'],
            ['Chef 1', 'dapur@kafekaleng.test', 'dapur'],
            ['Waitress 1', 'waitress@kafekaleng.test', 'waitress'],
            ['Owner Kaleng', 'owner@kafekaleng.test', 'owner'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'role' => $role, 'status' => 'active', 'password' => Hash::make('kaleng123')],
            );
        }
    }
}

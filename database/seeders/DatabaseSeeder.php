<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Hash bcrypt yang sama dengan akun di kafe_kaleng.sql, jadi kata sandi akun dari dump
     * tetap berlaku. Dimasukkan lewat query builder karena cast `hashed` pada model akan
     * meng-hash ulang nilai yang cost-nya berbeda dari BCRYPT_ROUNDS.
     *
     * Awalan $2b$ (bcrypt Node.js) lolos verifikasi selama config/hashing.php belum ada atau
     * `verify` bernilai false. Jika nanti `verify` diaktifkan, ganti awalan menjadi $2y$.
     */
    private const PASSWORD_HASH = '$2b$10$qaZQcAedW8otkJgd9pcaCulmJn3Tfqfjv99xwGAIH9aAqJ3zrrQDG';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            ['Admin Kafe', 'admin@kafekaleng.test', User::ROLE_ADMIN],
            ['Kasir 1', 'kasir@kafekaleng.test', User::ROLE_KASIR],
            ['Chef 1', 'dapur@kafekaleng.test', User::ROLE_DAPUR],
            ['Owner Kaleng', 'owner@kafekaleng.test', User::ROLE_OWNER],
            ['Waitress 1', 'waitress@kafekaleng.test', User::ROLE_WAITRESS],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => self::PASSWORD_HASH,
                    'role' => $role,
                    'status' => User::STATUS_ACTIVE,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}

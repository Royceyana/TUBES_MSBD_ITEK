<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi database Kafe Kaleng. Aman dijalankan berulang (tidak menimpa atau menggandakan data),
     * baik di database kosong maupun yang sudah di-restore dari kafe_kaleng.sql.
     *
     *   php artisan migrate:fresh --seed     # database kosong, semua terisi
     *   php artisan db:seed                  # lengkapi data yang belum ada
     *   php artisan db:seed --class=SampleTransactionSeeder
     */
    public function run(): void
    {
        // Data wajib: akun, menu, resep, dan stok awal agar menu bisa dipesan.
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            IngredientSeeder::class,
            MenuSeeder::class,
            RecipeSeeder::class,
            InitialStockSeeder::class,
        ]);

        // Data contoh untuk demo dan pengujian dashboard/laporan; dilewati di production.
        if (! app()->isProduction()) {
            $this->call([
                SampleTransactionSeeder::class,
                StockAlertSeeder::class,
            ]);
        }
    }
}

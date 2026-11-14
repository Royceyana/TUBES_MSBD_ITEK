<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipeSeeder extends Seeder
{
    /**
     * Resep per 1 porsi: menu_id => [ingredient_id => qty_needed]. Satuan mengikuti ingredients.unit.
     * Menu yang sudah punya resep (mis. diubah lewat halaman admin) dilewati, jadi tidak tertimpa.
     */
    public function run(): void
    {
        $recipes = [
            1 => [7 => 5, 20 => 30, 26 => 50, 27 => 10, 34 => 150], // Bakso
            2 => [1 => 75, 2 => 150, 4 => 30, 6 => 20, 7 => 10, 10 => 20, 16 => 5, 17 => 10, 18 => 5, 19 => 3], // Nasi Ayam Andaliman
            3 => [1 => 75, 2 => 80, 3 => 0.5, 4 => 15, 6 => 15, 7 => 10, 14 => 20, 16 => 5, 17 => 10, 18 => 5, 21 => 20, 27 => 15], // Nasi Soto Ayam
            4 => [1 => 100, 3 => 1, 4 => 30, 6 => 15, 7 => 5, 9 => 10, 15 => 20, 20 => 30, 31 => 40, 32 => 40, 42 => 15], // Nasi Goreng Seafood
            5 => [1 => 100, 2 => 40, 3 => 1, 4 => 30, 6 => 15, 7 => 5, 8 => 15, 9 => 10, 15 => 20, 20 => 30, 33 => 10, 42 => 15], // Nasi Goreng Kampung
            6 => [1 => 100, 3 => 1, 4 => 30, 6 => 15, 7 => 5, 9 => 10, 15 => 20, 42 => 15], // Nasi Goreng Biasa
            7 => [1 => 75, 3 => 2, 4 => 20, 6 => 10, 8 => 10, 12 => 20, 15 => 20], // Nasi Telor (Ceplok/Dadar)
            8 => [1 => 75, 2 => 150, 4 => 60, 6 => 15, 7 => 5, 11 => 50, 12 => 20], // Nasi Ayam Sambal Ijo
            9 => [1 => 75, 2 => 150, 4 => 40, 6 => 15, 7 => 5, 8 => 20, 12 => 30], // Nasi Ayam Pecak
            10 => [1 => 75, 2 => 150, 6 => 10, 8 => 15, 12 => 20, 42 => 15], // Nasi Ayam Bakar
            11 => [1 => 75, 2 => 150, 4 => 40, 6 => 10, 8 => 15, 9 => 10, 12 => 30], // Nasi Ayam Penyet
            12 => [1 => 75, 2 => 150, 4 => 40, 7 => 5, 8 => 25, 22 => 24, 24 => 8], // Nasi Ayam Geprek
            13 => [3 => 1, 4 => 30, 6 => 15, 7 => 5, 8 => 10, 9 => 10, 20 => 30, 26 => 100, 31 => 40, 32 => 40, 35 => 50, 42 => 15], // Mie Balap Seafood
            14 => [3 => 1, 8 => 10, 20 => 40, 29 => 1], // Indomie (Goreng/Kuah)
            15 => [2 => 30, 3 => 0.5, 7 => 5, 13 => 20, 21 => 40, 26 => 100], // Mie Sop Kampung
            16 => [3 => 1, 4 => 30, 6 => 15, 7 => 5, 20 => 40, 28 => 150, 34 => 50, 42 => 15], // Mie Tiaw Goreng
            17 => [3 => 1, 4 => 20, 6 => 15, 7 => 5, 20 => 40, 30 => 1, 42 => 15], // Ifu Mie (Goreng/Kuah)
            18 => [2 => 20, 3 => 1, 4 => 30, 6 => 15, 7 => 5, 13 => 20, 20 => 20, 27 => 60, 42 => 15], // Mie Hun Goreng
            19 => [2 => 90, 4 => 40, 13 => 15, 23 => 30, 39 => 6, 40 => 10, 41 => 15], // Dimsum Goreng
            20 => [2 => 60, 13 => 10, 23 => 20, 39 => 4, 40 => 30, 41 => 5], // Dimsum Mentai
            21 => [2 => 60, 13 => 10, 23 => 20, 39 => 4, 41 => 10], // Dimsum Ayam
            22 => [2 => 60, 3 => 0.5, 4 => 40, 7 => 5, 23 => 30, 42 => 15], // Sempol Black Pepper
            23 => [2 => 60, 3 => 0.5, 4 => 40, 23 => 30, 42 => 15], // Sempol Teriyaki
            24 => [2 => 60, 3 => 0.5, 4 => 40, 23 => 30], // Sempol Barbeque
            25 => [2 => 60, 3 => 0.5, 4 => 40, 23 => 30], // Sempol Spicy
            26 => [2 => 30, 3 => 1, 4 => 40, 13 => 10, 14 => 10, 22 => 30, 25 => 20, 40 => 10], // Risol Ayam
            27 => [4 => 50, 5 => 5, 22 => 24, 38 => 3, 43 => 15], // Pisang Pasir
            28 => [4 => 50, 14 => 100, 34 => 50, 36 => 60, 37 => 40, 40 => 10, 41 => 15], // Mix Platter
            29 => [4 => 30, 37 => 80, 40 => 10, 41 => 15], // Sosis
            30 => [4 => 40, 36 => 100, 40 => 10, 41 => 15], // Nugget
            31 => [4 => 50, 14 => 200, 40 => 10, 41 => 15], // French Fries
            32 => [43 => 30, 51 => 20], // Thai Tea
            33 => [5 => 15, 44 => 30, 52 => 20], // Thai Greentea
            34 => [5 => 15, 44 => 50, 50 => 5], // Teh Hijau Susu
            35 => [5 => 20, 50 => 5], // Teh Hijau
            36 => [43 => 25, 49 => 5], // Teh Susu
            37 => [5 => 20, 16 => 15, 49 => 5], // Teh Jahe
            38 => [5 => 20, 49 => 5, 57 => 0.5], // Teh Lemon
            39 => [5 => 20, 49 => 5], // Teh Manis
            40 => [5 => 20, 56 => 4], // Jeruk Nipis
            41 => [5 => 20, 55 => 3], // Jeruk Peras
            42 => [5 => 20, 16 => 10, 53 => 5], // Rosella Jahe
            43 => [5 => 20, 53 => 5], // Rosella
            44 => [5 => 20, 16 => 5, 17 => 20], // Wedang Serai
            45 => [5 => 20, 16 => 20, 17 => 10], // Wedang Jahe
            46 => [5 => 15, 44 => 100, 47 => 15, 59 => 40], // Cappucino Cincau
            47 => [5 => 15, 44 => 100, 47 => 15], // Cappucino
            48 => [44 => 120, 48 => 15, 60 => 20], // Kopi Susu Gula Aren
            49 => [16 => 15, 43 => 30, 47 => 15], // Kopi Jahe Susu
            50 => [5 => 20, 16 => 15, 47 => 15], // Kopi Jahe
            51 => [48 => 15], // V60
            52 => [43 => 25, 47 => 15], // Sanger
            53 => [5 => 20, 47 => 15], // Kopi Tubruk
            54 => [5 => 15, 47 => 15, 57 => 0.5, 62 => 100], // Kopi Soda Lemon
            55 => [5 => 15, 58 => 150], // Jus Kuini
            56 => [5 => 20, 15 => 150], // Timun Serut
            57 => [5 => 15, 12 => 100, 13 => 100], // Jus Tomat Wortel
            58 => [5 => 15, 13 => 80, 55 => 3], // Jus Jeruk Wortel
            59 => [5 => 15, 12 => 150], // Jus Tomat
            60 => [5 => 15, 55 => 4], // Jus Jeruk
            61 => [5 => 15, 15 => 150, 56 => 1], // Jus Timun
            62 => [5 => 15, 43 => 30, 54 => 100], // Jus Alpukat
            63 => [44 => 120, 46 => 25], // Red Velvet
            64 => [43 => 25, 61 => 30], // Badak Susu
            65 => [61 => 30, 62 => 150], // Badak Soda
            66 => [44 => 120, 45 => 25], // Coklat Susu
            67 => [43 => 25, 63 => 200], // Fanta Susu
        ];

        foreach ($recipes as $menuId => $items) {
            if (DB::table('menu_ingredients')->where('menu_id', $menuId)->exists()) {
                continue;
            }

            foreach ($items as $ingredientId => $qty) {
                DB::table('menu_ingredients')->insert([
                    'menu_id' => $menuId, 'ingredient_id' => $ingredientId, 'qty_needed' => $qty,
                ]);
            }
        }

        // menu_ingredients.id memakai sequence; majukan bila data pernah dimuat dengan id eksplisit.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('menu_ingredients', 'id'), COALESCE((SELECT MAX(id) FROM menu_ingredients), 1), (SELECT COUNT(*) > 0 FROM menu_ingredients))");
        }
    }
}

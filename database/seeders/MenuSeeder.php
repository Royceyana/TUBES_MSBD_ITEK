<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SyncsSequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    use SyncsSequence;

    /** Daftar menu: [id, category_id, nama, harga (Rp)]. category_id mengikuti CategorySeeder. */
    public function run(): void
    {
        $rows = [
            [1, 1, 'Bakso', 18000],
            [2, 1, 'Nasi Ayam Andaliman', 23000],
            [3, 1, 'Nasi Soto Ayam', 23000],
            [4, 1, 'Nasi Goreng Seafood', 23000],
            [5, 1, 'Nasi Goreng Kampung', 17000],
            [6, 1, 'Nasi Goreng Biasa', 17000],
            [7, 1, 'Nasi Telor (Ceplok/Dadar)', 13000],
            [8, 1, 'Nasi Ayam Sambal Ijo', 20000],
            [9, 1, 'Nasi Ayam Pecak', 20000],
            [10, 1, 'Nasi Ayam Bakar', 20000],
            [11, 1, 'Nasi Ayam Penyet', 20000],
            [12, 1, 'Nasi Ayam Geprek', 20000],
            [13, 2, 'Mie Balap Seafood', 20000],
            [14, 2, 'Indomie (Goreng/Kuah)', 15000],
            [15, 2, 'Mie Sop Kampung', 15000],
            [16, 2, 'Mie Tiaw Goreng', 15000],
            [17, 2, 'Ifu Mie (Goreng/Kuah)', 15000],
            [18, 2, 'Mie Hun Goreng', 15000],
            [19, 3, 'Dimsum Goreng', 21000],
            [20, 3, 'Dimsum Mentai', 24000],
            [21, 3, 'Dimsum Ayam', 16000],
            [22, 3, 'Sempol Black Pepper', 13000],
            [23, 3, 'Sempol Teriyaki', 13000],
            [24, 3, 'Sempol Barbeque', 13000],
            [25, 3, 'Sempol Spicy', 13000],
            [26, 3, 'Risol Ayam', 16000],
            [27, 3, 'Pisang Pasir', 13000],
            [28, 3, 'Mix Platter', 23000],
            [29, 3, 'Sosis', 13000],
            [30, 3, 'Nugget', 13000],
            [31, 3, 'French Fries', 13000],
            [32, 4, 'Thai Tea', 16000],
            [33, 4, 'Thai Greentea', 15000],
            [34, 4, 'Teh Hijau Susu', 15000],
            [35, 4, 'Teh Hijau', 13000],
            [36, 4, 'Teh Susu', 13000],
            [37, 4, 'Teh Jahe', 13000],
            [38, 4, 'Teh Lemon', 14000],
            [39, 4, 'Teh Manis', 9000],
            [40, 5, 'Jeruk Nipis', 13000],
            [41, 5, 'Jeruk Peras', 14000],
            [42, 5, 'Rosella Jahe', 15000],
            [43, 5, 'Rosella', 13000],
            [44, 5, 'Wedang Serai', 13000],
            [45, 5, 'Wedang Jahe', 13000],
            [46, 5, 'Cappucino Cincau', 16000],
            [47, 5, 'Cappucino', 14000],
            [48, 5, 'Kopi Susu Gula Aren', 19000],
            [49, 5, 'Kopi Jahe Susu', 19000],
            [50, 5, 'Kopi Jahe', 18000],
            [51, 5, 'V60', 18000],
            [52, 5, 'Sanger', 16000],
            [53, 5, 'Kopi Tubruk', 15000],
            [54, 5, 'Kopi Soda Lemon', 15000],
            [55, 6, 'Jus Kuini', 14000],
            [56, 6, 'Timun Serut', 14000],
            [57, 6, 'Jus Tomat Wortel', 16000],
            [58, 6, 'Jus Jeruk Wortel', 16000],
            [59, 6, 'Jus Tomat', 14000],
            [60, 6, 'Jus Jeruk', 14000],
            [61, 6, 'Jus Timun', 14000],
            [62, 6, 'Jus Alpukat', 14000],
            [63, 7, 'Red Velvet', 15000],
            [64, 7, 'Badak Susu', 15000],
            [65, 7, 'Badak Soda', 13000],
            [66, 7, 'Coklat Susu', 15000],
            [67, 7, 'Fanta Susu', 13000],
        ];

        foreach ($rows as [$id, $categoryId, $name, $price]) {
            DB::table('menus')->insertOrIgnore([
                'id' => $id, 'category_id' => $categoryId, 'name' => $name, 'price' => $price, 'status' => 'active',
            ]);
        }

        $this->syncSequence('menus');
    }
}

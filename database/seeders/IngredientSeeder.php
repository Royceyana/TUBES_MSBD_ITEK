<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SyncsSequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    use SyncsSequence;

    /**
     * Bahan baku: [id, nama, satuan, stok minimum, harga per satuan (Rp)].
     * stock_qty sengaja tidak diisi di sini; stok hanya berubah lewat stock_movements
     * (lihat InitialStockSeeder), karena trigger database yang memperbarui ingredients.stock_qty.
     */
    public function run(): void
    {
        $rows = [
            [1, 'Beras', 'g', 5000, 15.75],
            [2, 'Ayam Karkas', 'g', 4000, 46.9],
            [3, 'Telur Ayam', 'pcs', 30, 1700],
            [4, 'Minyak Goreng', 'ml', 3000, 22.9],
            [5, 'Gula Pasir', 'g', 3000, 18.75],
            [6, 'Bawang Merah', 'g', 1000, 29],
            [7, 'Bawang Putih', 'g', 500, 30.8],
            [8, 'Cabai Rawit Merah', 'g', 500, 53.15],
            [9, 'Cabai Merah Keriting', 'g', 500, 55.7],
            [10, 'Cabai Merah Besar', 'g', 300, 61.6],
            [11, 'Cabai Hijau', 'g', 300, 40],
            [12, 'Tomat', 'g', 1000, 15.9],
            [13, 'Wortel', 'g', 1000, 14.8],
            [14, 'Kentang', 'g', 2000, 20.1],
            [15, 'Timun', 'g', 1000, 10],
            [16, 'Jahe', 'g', 500, 40],
            [17, 'Serai', 'g', 300, 15],
            [18, 'Kunyit', 'g', 200, 20],
            [19, 'Andaliman', 'g', 100, 150],
            [20, 'Sawi/Kangkung', 'g', 1000, 12],
            [21, 'Kol/Tauge', 'g', 500, 10],
            [22, 'Tepung Terigu', 'g', 1000, 13.6],
            [23, 'Tapioka', 'g', 1000, 14],
            [24, 'Maizena', 'g', 500, 22],
            [25, 'Tepung Panir', 'g', 500, 25],
            [26, 'Mie Kuning Basah', 'g', 1000, 16],
            [27, 'Bihun/Soun', 'g', 500, 31.5],
            [28, 'Kwetiau Basah', 'g', 1000, 18],
            [29, 'Indomie', 'pcs', 10, 3500],
            [30, 'Ifu Mie', 'pcs', 10, 4000],
            [31, 'Udang', 'g', 500, 80],
            [32, 'Cumi', 'g', 500, 90],
            [33, 'Ikan Teri', 'g', 200, 100],
            [34, 'Bakso Sapi', 'g', 1000, 55],
            [35, 'Bakso Ikan', 'g', 500, 45],
            [36, 'Nugget Ayam', 'g', 1000, 60],
            [37, 'Sosis Ayam', 'g', 1000, 55],
            [38, 'Pisang Pasir', 'pcs', 15, 1200],
            [39, 'Kulit Pangsit', 'pcs', 50, 200],
            [40, 'Mayonaise', 'g', 500, 30],
            [41, 'Saus Sambal', 'ml', 500, 20],
            [42, 'Kecap Manis', 'ml', 500, 25],
            [43, 'Susu Kental Manis', 'g', 1500, 37.84],
            [44, 'Susu UHT', 'ml', 3000, 19],
            [45, 'Bubuk Cokelat', 'g', 500, 100],
            [46, 'Bubuk Red Velvet', 'g', 500, 120],
            [47, 'Kopi Bubuk', 'g', 500, 90],
            [48, 'Biji Kopi Arabika', 'g', 500, 150],
            [49, 'Teh Seduh', 'g', 200, 60],
            [50, 'Teh Hijau', 'g', 100, 100],
            [51, 'Bubuk Thai Tea', 'g', 300, 120],
            [52, 'Bubuk Thai Green Tea', 'g', 300, 120],
            [53, 'Rosella Kering', 'g', 100, 120],
            [54, 'Alpukat', 'g', 1000, 30],
            [55, 'Jeruk Peras', 'pcs', 30, 1300],
            [56, 'Jeruk Nipis', 'pcs', 30, 700],
            [57, 'Lemon', 'pcs', 10, 3000],
            [58, 'Kuini', 'g', 500, 25],
            [59, 'Cincau', 'g', 500, 15],
            [60, 'Gula Aren', 'g', 500, 30],
            [61, 'Sirup Badak', 'ml', 300, 65.22],
            [63, 'Fanta', 'ml', 1000, 20],
            [64, 'Gas LPG 3 kg', 'tabung', 1, 25000],
            [65, 'Galon Air', 'galon', 1, 16000],
            [66, 'Es Kristal', 'kg', 10, 0],
            [62, 'Air Soda', 'ml', 2000, 10.61],
        ];

        foreach ($rows as [$id, $name, $unit, $minStock, $cost]) {
            DB::table('ingredients')->insertOrIgnore([
                'id' => $id, 'name' => $name, 'unit' => $unit,
                'min_stock' => $minStock, 'cost_per_unit' => $cost, 'is_active' => true,
            ]);
        }

        $this->syncSequence('ingredients');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockAlertSeeder extends Seeder
{
    private const MARKER = 'Stok opname (seeder)';

    /**
     * Bahan contoh yang sengaja dibuat menipis/habis agar peringatan stok, status "menipis/habis",
     * dan menu "tidak tersedia" bisa didemokan. Nilai = pengali dari stok minimum
     * (0 = habis, di bawah 1 = menipis). Kosongkan array ini bila tidak ingin data demo tersebut.
     */
    private const ALERTS = [
        'Udang' => 0.4,
        'Cumi' => 0.5,
        'Andaliman' => 0.8,
        'Gula Pasir' => 0.7,
        'Fanta' => 0,
    ];

    /**
     * Dijalankan setelah SampleTransactionSeeder, lewat fn_stock_adjust_to (stok opname) sehingga
     * perubahan tercatat sebagai stock_movements "adjustment" dan tidak memutus trigger.
     */
    public function run(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $adminId = DB::table('users')->where('email', 'admin@kafekaleng.test')->value('id')
            ?? DB::table('users')->where('role', 'admin')->value('id');

        foreach (self::ALERTS as $name => $factor) {
            $ing = DB::table('ingredients')->where('name', $name)->first(['id', 'min_stock']);
            if (! $ing) {
                continue;
            }

            $done = DB::table('stock_movements')
                ->where('ingredient_id', $ing->id)->where('notes', self::MARKER)->exists();
            if ($done) {
                continue;
            }

            DB::select('select fn_stock_adjust_to(?, ?, ?, ?)', [
                $ing->id, round((float) $ing->min_stock * $factor, 2), $adminId, self::MARKER,
            ]);
        }
    }
}

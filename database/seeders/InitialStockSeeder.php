<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialStockSeeder extends Seeder
{
    private const MARKER = 'Stok awal (seeder)';

    /** Stok awal = stok minimum x pengali ini, supaya semua bahan berstatus "aman". */
    private const MULTIPLIER = 4;

    /**
     * Mengisi stok awal bahan lewat stock_movements (type "in"); trigger database yang menaikkan
     * ingredients.stock_qty. Tanpa ini semua stok 0 dan seluruh menu tampil "habis" di kasir/waitress.
     * Bahan yang sudah pernah diisi seeder ini dilewati, jadi aman dijalankan berulang.
     */
    public function run(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->command?->warn('InitialStockSeeder dilewati: trigger stok hanya ada di PostgreSQL.');

            return;
        }

        $adminId = DB::table('users')->where('email', 'admin@kafekaleng.test')->value('id')
            ?? DB::table('users')->where('role', 'admin')->value('id');

        // Dicatat sebelum data transaksi contoh supaya riwayat stok terurut wajar.
        $at = now()->subDays(SampleTransactionSeeder::DAYS + 1)->setTime(8, 0)->format('Y-m-d H:i:s');

        $filled = 0;

        foreach (DB::table('ingredients')->where('is_active', true)->orderBy('id')->get(['id', 'min_stock', 'stock_qty']) as $ing) {
            $already = DB::table('stock_movements')
                ->where('ingredient_id', $ing->id)->where('notes', self::MARKER)->exists();
            if ($already) {
                continue;
            }

            $delta = round((float) $ing->min_stock * self::MULTIPLIER - (float) $ing->stock_qty, 2);
            if ($delta <= 0) {
                continue;
            }

            DB::table('stock_movements')->insert([
                'ingredient_id' => $ing->id, 'user_id' => $adminId, 'type' => 'in',
                'qty' => $delta, 'notes' => self::MARKER, 'created_at' => $at,
            ]);
            $filled++;
        }

        $this->command?->info("Stok awal diisi untuk {$filled} bahan.");
    }
}

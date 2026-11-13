<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Stok bahan dikelola TRIGGER database (kafe_kaleng.sql), bukan PHP:
 *  - stock_movements INSERT        -> trg_stock_movement_apply memperbarui ingredients.stock_qty
 *  - transactions.order_status='ready' -> trg_consume_stock_on_ready mencatat pemakaian bahan
 * Service ini hanya membaca ketersediaan lewat view v_menu_availability dan
 * meneruskan id pengguna ke trigger lewat setting sesi `app.user_id`.
 */
class StockService
{
    /**
     * Ketersediaan tiap menu: [menu_id => ['available' => bool, 'portions' => int|null]].
     * portions = null berarti menu tanpa resep (tidak dibatasi stok).
     */
    public function availability(): array
    {
        return DB::table('v_menu_availability')
            ->get(['menu_id', 'is_available', 'porsi_tersedia', 'jumlah_bahan'])
            ->mapWithKeys(fn ($r) => [(int) $r->menu_id => [
                'available' => (bool) $r->is_available,
                'portions' => $r->jumlah_bahan > 0 ? (int) $r->porsi_tersedia : null,
            ]])
            ->all();
    }

    /**
     * Bahan yang stoknya KURANG untuk memasak pesanan (kebutuhan resep x qty > stok saat ini).
     * Dipakai dapur untuk memberi peringatan sebelum pesanan ditandai "siap" (saat itu trigger
     * memotong stok dan stok bisa menjadi minus).
     *
     * @param  array<int>  $transactionIds
     * @return array<int, list<object>>  [transaction_id => [ {name, unit, needed, stock, short}, ... ]]
     */
    public function shortages(array $transactionIds): array
    {
        if ($transactionIds === []) {
            return [];
        }

        return DB::table('transaction_details as td')
            ->join('menu_ingredients as mi', 'mi.menu_id', '=', 'td.menu_id')
            ->join('ingredients as i', 'i.id', '=', 'mi.ingredient_id')
            ->whereIn('td.transaction_id', $transactionIds)
            ->groupBy('td.transaction_id', 'i.id', 'i.name', 'i.unit', 'i.stock_qty')
            ->havingRaw('SUM(mi.qty_needed * td.qty) > COALESCE(i.stock_qty, 0)')
            ->orderBy('i.name')
            ->selectRaw('td.transaction_id, i.name, i.unit, SUM(mi.qty_needed * td.qty) as needed, COALESCE(i.stock_qty, 0) as stock')
            ->get()
            ->map(function ($r) {
                $r->short = (float) $r->needed - (float) $r->stock;

                return $r;
            })
            ->groupBy('transaction_id')
            ->map(fn ($rows) => $rows->values()->all())
            ->all();
    }

    /** Jalankan $fn dalam transaksi DB dengan app.user_id terisi (dipakai trigger). */
    public function asUser(?int $userId, callable $fn): mixed
    {
        return DB::transaction(function () use ($userId, $fn) {
            DB::select("select set_config('app.user_id', ?, true)", [(string) ($userId ?? '')]);

            return $fn();
        });
    }
}

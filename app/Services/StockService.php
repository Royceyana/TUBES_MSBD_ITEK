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

    /** Jalankan $fn dalam transaksi DB dengan app.user_id terisi (dipakai trigger). */
    public function asUser(?int $userId, callable $fn): mixed
    {
        return DB::transaction(function () use ($userId, $fn) {
            DB::select("select set_config('app.user_id', ?, true)", [(string) ($userId ?? '')]);

            return $fn();
        });
    }
}

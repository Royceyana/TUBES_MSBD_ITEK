<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Integrasi POS dengan stok bahan baku.
 *
 * ASUMSI NAMA TABEL/KOLOM (diambil dari README.md dan README-UI.md, BELUM dicocokkan
 * dengan migration anggota lain). Jika migration mereka berbeda, cukup ubah di sini:
 *
 *   menu_ingredients : menu_id, ingredient_id, qty_needed
 *   ingredients      : id, name, stock_qty
 *   stock_movements  : ingredient_id, user_id, type ('in' | 'out'), qty, notes, created_at, updated_at
 *
 * Selama ketiga tabel itu belum ada, isReady() = false dan POS berjalan tanpa stok
 * (hanya untuk masa pengembangan). Setelah tabel ada, integrasi aktif otomatis.
 */
class StockService
{
    /** Apakah tabel-tabel stok sudah tersedia di database? */
    public function isReady(): bool
    {
        return Schema::hasTable('menu_ingredients')
            && Schema::hasTable('ingredients')
            && Schema::hasTable('stock_movements');
    }

    /**
     * Menu mana yang boleh dijual sekarang?
     * Syarat: punya resep DAN stok setiap bahan cukup untuk 1 porsi.
     *
     * @param  Collection<int, int|string>  $menuIds
     * @return array<int, bool> [menu_id => boleh dijual]
     */
    public function availability(Collection $menuIds): array
    {
        $result = $menuIds->mapWithKeys(fn ($id) => [(int) $id => false])->all();

        if (! $this->isReady() || $menuIds->isEmpty()) {
            return $menuIds->mapWithKeys(fn ($id) => [(int) $id => true])->all();
        }

        $rows = DB::table('menu_ingredients as r')
            ->join('ingredients as i', 'i.id', '=', 'r.ingredient_id')
            ->whereIn('r.menu_id', $menuIds)
            ->get(['r.menu_id', 'r.qty_needed', 'i.stock_qty']);

        foreach ($rows->groupBy('menu_id') as $menuId => $recipe) {
            $result[(int) $menuId] = $recipe->every(fn ($row) => $row->stock_qty >= $row->qty_needed);
        }

        return $result;
    }

    /**
     * Kurangi stok untuk semua item transaksi dan catat di stock_movements.
     * HARUS dipanggil di dalam DB::transaction agar ikut dibatalkan jika gagal.
     *
     * @throws ValidationException jika ada menu tanpa resep atau stok tidak cukup
     */
    public function deduct(Transaction $transaction): void
    {
        if (! $this->isReady()) {
            return;
        }

        $needs = []; // ingredient_id => total kebutuhan

        foreach ($transaction->details as $detail) {
            $recipe = DB::table('menu_ingredients')->where('menu_id', $detail->menu_id)->get();

            if ($recipe->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => "Menu \"{$detail->menu_name}\" belum punya resep, tidak bisa dijual.",
                ]);
            }

            foreach ($recipe as $row) {
                $needs[$row->ingredient_id] = ($needs[$row->ingredient_id] ?? 0) + $row->qty_needed * $detail->qty;
            }
        }

        $now = now();

        foreach ($needs as $ingredientId => $qty) {
            // UPDATE bersyarat: stok hanya berkurang bila MASIH cukup.
            // Aman walau dua kasir menjual bersamaan (tidak mungkin jadi minus).
            $affected = DB::table('ingredients')
                ->where('id', $ingredientId)
                ->where('stock_qty', '>=', $qty)
                ->decrement('stock_qty', $qty);

            if ($affected === 0) {
                $name = DB::table('ingredients')->where('id', $ingredientId)->value('name') ?? "#{$ingredientId}";

                throw ValidationException::withMessages([
                    'items' => "Stok bahan \"{$name}\" tidak cukup untuk pesanan ini.",
                ]);
            }

            DB::table('stock_movements')->insert([
                'ingredient_id' => $ingredientId,
                'user_id' => $transaction->user_id,
                'type' => 'out',
                'qty' => $qty,
                'notes' => $this->saleNote($transaction),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Kembalikan stok sebuah transaksi yang dibatalkan.
     * Jumlah yang dikembalikan = jumlah yang dulu benar-benar dipotong
     * (dibaca dari stock_movements), bukan dihitung ulang dari resep terbaru.
     */
    public function restore(Transaction $transaction): void
    {
        if (! $this->isReady()) {
            return;
        }

        $outs = DB::table('stock_movements')
            ->where('type', 'out')
            ->where('notes', $this->saleNote($transaction))
            ->get();

        $now = now();

        foreach ($outs as $out) {
            DB::table('ingredients')->where('id', $out->ingredient_id)->increment('stock_qty', $out->qty);

            DB::table('stock_movements')->insert([
                'ingredient_id' => $out->ingredient_id,
                'user_id' => $transaction->user_id,
                'type' => 'in',
                'qty' => $out->qty,
                'notes' => "Pembatalan #{$transaction->invoice_number}",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function saleNote(Transaction $transaction): string
    {
        return "Penjualan #{$transaction->invoice_number}";
    }
}

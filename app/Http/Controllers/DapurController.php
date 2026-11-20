<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\OrderViews;
use App\Support\DbMessage;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Dapur: antrian pesanan yang sudah dibayar (Menunggu -> Dimasak -> Siap), stok bahan, dan resep.
 * Saat "siap", trigger DB memotong stok bahan; sebelum itu dapur diperingatkan bila stok kurang.
 */
class DapurController extends Controller
{
    public function __construct(private StockService $stock, private OrderViews $orders)
    {
    }

    public function board(): View
    {
        // Antrian dibaca dari view v_order_queue (pesanan lunas yang belum diantar) + v_order_lines (item).
        $open = $this->orders->openOrders();

        $queue = $this->orders->withLines($open->whereIn('order_status', ['waiting', 'preparing'])->values());
        $done = $this->orders->withLines(
            $open->where('order_status', 'ready')->filter(fn ($t) => Carbon::parse($t->tanggal)->isToday())
                ->sortByDesc('id')->take(8)->values()
        );

        // [transaction_id => bahan yang kurang]; kosong berarti stok cukup.
        $shortages = $this->stock->shortages($queue->pluck('id')->all());

        return view('dapur.board', compact('queue', 'done', 'shortages'));
    }

    public function advance(Request $request, Transaction $transaction): RedirectResponse
    {
        $next = ['waiting' => 'preparing', 'preparing' => 'ready'][$transaction->order_status] ?? null;

        if ($transaction->status !== Transaction::STATUS_PAID || ! $next) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini tidak bisa diproses.']);
        }

        // Peringatan stok kurang: pesanan "siap" memotong stok, jadi minta konfirmasi bila bahan tidak cukup.
        if ($next === 'ready' && ! $request->boolean('confirm_shortage')) {
            $short = $this->stock->shortages([$transaction->id])[$transaction->id] ?? [];

            if ($short !== []) {
                $names = collect($short)->pluck('name')->join(', ');

                throw ValidationException::withMessages([
                    'order' => "{$transaction->number}: stok kurang untuk {$names}. Cek stok lalu konfirmasi bila tetap ingin menandai siap.",
                ]);
            }
        }

        try {
            // Urutan status, pengecekan peran, dan penguncian baris ditangani prosedur sp_set_order_status (savepoint agar error tidak merusak transaksi luar).
            DB::transaction(fn () => DB::statement('CALL sp_set_order_status(?::integer, ?::text, ?::integer)', [$transaction->id, $next, auth()->id()]));
        } catch (QueryException $e) {
            throw ValidationException::withMessages(['order' => DbMessage::from($e, 'Status pesanan tidak bisa diubah.')]);
        }

        return back()->with('success', "{$transaction->number}: ".Transaction::ORDER_LABEL[$next].'.');
    }

    /** Stok bahan (hanya lihat) + pendataan stok fisik dapur lewat stok opname. */
    public function stock(): View
    {
        $ingredients = DB::table('v_ingredient_stock')->orderBy('name')->get();
        $lowStock = $ingredients->where('stock_status', '!=', 'aman')->values();

        return view('dapur.stock', compact('ingredients', 'lowStock'));
    }

    /** Mendata bahan di dapur: samakan stok sistem dengan stok fisik lewat prosedur sp_stock_opname. */
    public function opname(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'qty' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $delta = DB::selectOne('CALL sp_stock_opname(?::integer, ?::numeric, ?::integer, ?::text, NULL::numeric)', [
            $data['ingredient_id'], $data['qty'], auth()->id(), $data['notes'] ?: 'Pendataan stok dapur',
        ])->p_delta;

        return back()->with('success', (float) $delta === 0.0 ? 'Stok sudah sesuai, tidak ada selisih.' : 'Stok disesuaikan.');
    }

    /** Resep per menu (hanya lihat): bahan dan takaran untuk 1 porsi. */
    public function recipes(): View
    {
        // Resep dibaca dari view v_menu_recipe.
        $rows = DB::table('v_menu_recipe')->where('menu_status', 'active')
            ->orderBy('kategori')->orderBy('menu')->orderBy('bahan')->get();

        $menus = $rows->unique('menu_id')->map(fn ($r) => (object) ['id' => $r->menu_id, 'name' => $r->menu, 'category' => $r->kategori])->values();

        $lines = $rows->whereNotNull('ingredient_id')->map(fn ($r) => (object) [
            'menu_id' => $r->menu_id, 'name' => $r->bahan, 'unit' => $r->satuan, 'qty_needed' => $r->qty_needed,
        ])->groupBy('menu_id');

        return view('dapur.recipes', compact('menus', 'lines'));
    }
}

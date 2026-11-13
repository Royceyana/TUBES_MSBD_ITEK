<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Dapur: antrian pesanan yang sudah dibayar (Menunggu -> Dimasak -> Siap), stok bahan, dan resep.
 * Saat "siap", trigger DB memotong stok bahan; sebelum itu dapur diperingatkan bila stok kurang.
 */
class DapurController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function board(): View
    {
        $base = Transaction::with('details.menu')
            ->where('status', Transaction::STATUS_PAID);

        $queue = (clone $base)->whereIn('order_status', ['waiting', 'preparing'])->orderBy('id')->get();
        $done = (clone $base)->where('order_status', 'ready')->whereDate('transaction_date', today())
            ->latest('id')->take(8)->get();

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

        $this->stock->asUser(auth()->id(), fn () => Transaction::where('id', $transaction->id)
            ->where('order_status', $transaction->order_status)
            ->update(['order_status' => $next]));

        return back()->with('success', "{$transaction->number}: ".Transaction::ORDER_LABEL[$next].'.');
    }

    /** Stok bahan (hanya lihat) + pendataan stok fisik dapur lewat stok opname. */
    public function stock(): View
    {
        $ingredients = DB::table('v_ingredient_stock')->orderBy('name')->get();
        $lowStock = $ingredients->where('stock_status', '!=', 'aman')->values();

        return view('dapur.stock', compact('ingredients', 'lowStock'));
    }

    /** Mendata bahan di dapur: samakan stok sistem dengan stok fisik lewat fn_stock_adjust_to(). */
    public function opname(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'qty' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $delta = DB::selectOne('select public.fn_stock_adjust_to(?, ?, ?, ?) as delta', [
            $data['ingredient_id'], $data['qty'], auth()->id(), $data['notes'] ?: 'Pendataan stok dapur',
        ])->delta;

        return back()->with('success', (float) $delta === 0.0 ? 'Stok sudah sesuai, tidak ada selisih.' : 'Stok disesuaikan.');
    }

    /** Resep per menu (hanya lihat): bahan dan takaran untuk 1 porsi. */
    public function recipes(): View
    {
        $menus = DB::table('menus as m')
            ->leftJoin('categories as c', 'c.id', '=', 'm.category_id')
            ->where('m.status', 'active')
            ->orderBy('c.name')->orderBy('m.name')
            ->get(['m.id', 'm.name', 'c.name as category']);

        $lines = DB::table('menu_ingredients as mi')
            ->join('ingredients as i', 'i.id', '=', 'mi.ingredient_id')
            ->orderBy('i.name')
            ->get(['mi.menu_id', 'i.name', 'i.unit', 'mi.qty_needed'])
            ->groupBy('menu_id');

        return view('dapur.recipes', compact('menus', 'lines'));
    }
}

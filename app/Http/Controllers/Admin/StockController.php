<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    /** Riwayat pergerakan stok terbaru, dibaca dari view v_stock_history. */
    public static function recentMovements(int $limit = 100)
    {
        return DB::table('v_stock_history')
            ->orderByDesc('id')->limit($limit)
            ->get()
            ->map(function ($m) {
                $at = \Illuminate\Support\Carbon::parse($m->waktu);

                return (object) [
                    'id' => $m->id,
                    'ingredient' => $m->bahan,
                    'unit' => $m->satuan,
                    'type' => $m->jenis,
                    'qty' => $m->qty,
                    'notes' => $m->catatan,
                    'user' => $m->petugas,
                    'created_at' => $m->waktu,
                    'time' => $at->isToday() ? $at->format('H:i') : $at->translatedFormat('d M, H:i'),
                ];
            });
    }

    public function index(): View
    {
        $ingredients = DB::table('v_ingredient_stock')->orderBy('name')->get();
        $lowStock = $ingredients->where('stock_status', '!=', 'aman')->values();
        $movements = self::recentMovements();

        return view('admin.stock', compact('ingredients', 'lowStock', 'movements'));
    }

    /** Stok masuk lewat prosedur sp_restock; stock_qty bertambah lewat trigger trg_stock_movement_apply. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'qty' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::statement('CALL sp_restock(?::integer, ?::numeric, ?::integer, ?::text)', [
            $data['ingredient_id'], $data['qty'], Auth::id(), $data['notes'] ?? 'Restock',
        ]);

        return back()->with('success', 'Stok masuk tercatat.');
    }

    /** Stok opname: samakan stok sistem dengan stok fisik lewat prosedur sp_stock_opname (memanggil fn_stock_adjust_to). */
    public function opname(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'qty' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $delta = DB::selectOne('CALL sp_stock_opname(?::integer, ?::numeric, ?::integer, ?::text, NULL::numeric)', [
            $data['ingredient_id'], $data['qty'], Auth::id(), $data['notes'] ?: 'Stok opname',
        ])->p_delta;

        return back()->with('success', (float) $delta === 0.0 ? 'Stok sudah sesuai, tidak ada selisih.' : 'Stok disesuaikan.');
    }
}

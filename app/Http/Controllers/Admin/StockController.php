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
    public static function recentMovements(int $limit = 100)
    {
        return DB::table('stock_movements as s')
            ->join('ingredients as i', 'i.id', '=', 's.ingredient_id')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->orderByDesc('s.id')->limit($limit)
            ->get(['s.*', 'i.name as ingredient', 'i.unit', 'u.name as user'])
            ->map(function ($m) {
                $m->time = \Illuminate\Support\Carbon::parse($m->created_at)->isToday()
                    ? \Illuminate\Support\Carbon::parse($m->created_at)->format('H:i')
                    : \Illuminate\Support\Carbon::parse($m->created_at)->translatedFormat('d M, H:i');

                return $m;
            });
    }

    public function index(): View
    {
        $ingredients = DB::table('v_ingredient_stock')->orderBy('name')->get();
        $lowStock = $ingredients->where('stock_status', '!=', 'aman')->values();
        $movements = self::recentMovements();

        return view('admin.stock', compact('ingredients', 'lowStock', 'movements'));
    }

    /** Stok masuk. stock_qty bertambah lewat trigger trg_stock_movement_apply. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'qty' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        StockMovement::create($data + ['type' => 'in', 'user_id' => Auth::id()]);

        return back()->with('success', 'Stok masuk tercatat.');
    }

    /** Stok opname: samakan stok sistem dengan stok fisik lewat fn_stock_adjust_to(). */
    public function opname(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'qty' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $delta = DB::selectOne('select public.fn_stock_adjust_to(?, ?, ?, ?) as delta', [
            $data['ingredient_id'], $data['qty'], Auth::id(), $data['notes'] ?: 'Stok opname',
        ])->delta;

        return back()->with('success', (float) $delta === 0.0 ? 'Stok sudah sesuai, tidak ada selisih.' : 'Stok disesuaikan.');
    }
}

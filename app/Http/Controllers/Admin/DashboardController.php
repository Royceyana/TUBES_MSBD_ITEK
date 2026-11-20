<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Dashboard admin: angka dibaca dari view v_admin_summary, v_sales_daily, v_ingredient_stock, v_sales_by_menu, v_stock_history. */
class DashboardController extends Controller
{
    public function index(): View
    {
        $summary = DB::table('v_admin_summary')->first();

        $stats = [
            'menus' => (int) $summary->menu_aktif,
            'categories' => (int) $summary->kategori,
            'ingredients' => (int) $summary->bahan_aktif,
            'today' => (int) DB::table('v_sales_daily')->where('tanggal', today()->toDateString())->value('jumlah_transaksi'),
        ];

        $lowStock = DB::table('v_ingredient_stock')->where('stock_status', '!=', 'aman')->orderBy('stock_qty')->get();

        $topMenus = DB::table('v_sales_by_menu')->orderByDesc('qty_terjual')->limit(5)->get(['name', 'qty_terjual', 'pendapatan']);

        $movements = StockController::recentMovements(6);

        return view('admin.dashboard', compact('stats', 'lowStock', 'topMenus', 'movements'));
    }
}

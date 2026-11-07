<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'menus' => DB::table('menus')->where('status', 'active')->count(),
            'categories' => DB::table('categories')->count(),
            'ingredients' => DB::table('ingredients')->where('is_active', true)->count(),
            'today' => Transaction::where('status', Transaction::STATUS_PAID)->whereDate('transaction_date', today())->count(),
        ];

        $lowStock = DB::table('v_ingredient_stock')->where('stock_status', '!=', 'aman')->orderBy('stock_qty')->get();

        $movements = StockController::recentMovements(6);

        return view('admin.dashboard', compact('stats', 'lowStock', 'movements'));
    }
}

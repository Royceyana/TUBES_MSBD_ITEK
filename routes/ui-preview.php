<?php

use Illuminate\Support\Facades\Route;

/*
|  DATA CONTOH — bentuknya sengaja sama dengan model Eloquent (akses ->property),
|  jadi view tidak perlu diubah saat controller asli mengirim collection model.
*/
$o = fn (array $a) => (object) $a;
$many = fn (array $rows) => collect($rows)->map($o);

$categories = $many([
    ['id' => 1, 'name' => 'Kopi', 'menus_count' => 5],
    ['id' => 2, 'name' => 'Non-Kopi', 'menus_count' => 4],
    ['id' => 3, 'name' => 'Makanan Berat', 'menus_count' => 3],
    ['id' => 4, 'name' => 'Camilan', 'menus_count' => 3],
]);

$menus = $many([
    ['id' => 1, 'category_id' => 1, 'category' => 'Kopi', 'name' => 'Kopi Susu Kaleng', 'price' => 15000, 'image' => null, 'status' => 'active', 'recipe_count' => 3, 'available' => true],
    ['id' => 2, 'category_id' => 1, 'category' => 'Kopi', 'name' => 'Americano', 'price' => 12000, 'image' => null, 'status' => 'active', 'recipe_count' => 2, 'available' => true],
    ['id' => 3, 'category_id' => 1, 'category' => 'Kopi', 'name' => 'Latte Vintage', 'price' => 18000, 'image' => null, 'status' => 'active', 'recipe_count' => 3, 'available' => true],
    ['id' => 4, 'category_id' => 2, 'category' => 'Non-Kopi', 'name' => 'Es Teh Manis', 'price' => 6000, 'image' => null, 'status' => 'active', 'recipe_count' => 2, 'available' => true],
    ['id' => 5, 'category_id' => 2, 'category' => 'Non-Kopi', 'name' => 'Coklat Panas', 'price' => 14000, 'image' => null, 'status' => 'active', 'recipe_count' => 2, 'available' => false],
    ['id' => 6, 'category_id' => 3, 'category' => 'Makanan Berat', 'name' => 'Mie Goreng Rumahan', 'price' => 16000, 'image' => null, 'status' => 'active', 'recipe_count' => 4, 'available' => true],
    ['id' => 7, 'category_id' => 3, 'category' => 'Makanan Berat', 'name' => 'Nasi Goreng Kaleng', 'price' => 18000, 'image' => null, 'status' => 'active', 'recipe_count' => 5, 'available' => true],
    ['id' => 8, 'category_id' => 4, 'category' => 'Camilan', 'name' => 'Roti Bakar Coklat Keju', 'price' => 12000, 'image' => null, 'status' => 'active', 'recipe_count' => 0, 'available' => true],
    ['id' => 9, 'category_id' => 4, 'category' => 'Camilan', 'name' => 'Kentang Goreng', 'price' => 10000, 'image' => null, 'status' => 'inactive', 'recipe_count' => 2, 'available' => true],
]);

$ingredients = $many([
    ['id' => 1, 'name' => 'Biji kopi arabika', 'unit' => 'gram', 'stock_qty' => 4200, 'min_stock' => 1000],
    ['id' => 2, 'name' => 'Susu cair', 'unit' => 'ml', 'stock_qty' => 900, 'min_stock' => 2000],
    ['id' => 3, 'name' => 'Gula pasir', 'unit' => 'gram', 'stock_qty' => 3500, 'min_stock' => 1500],
    ['id' => 4, 'name' => 'Teh celup', 'unit' => 'pcs', 'stock_qty' => 120, 'min_stock' => 50],
    ['id' => 5, 'name' => 'Bubuk coklat', 'unit' => 'gram', 'stock_qty' => 0, 'min_stock' => 500],
    ['id' => 6, 'name' => 'Mie telur', 'unit' => 'gram', 'stock_qty' => 6000, 'min_stock' => 2000],
    ['id' => 7, 'name' => 'Telur ayam', 'unit' => 'pcs', 'stock_qty' => 14, 'min_stock' => 20],
    ['id' => 8, 'name' => 'Roti tawar', 'unit' => 'pcs', 'stock_qty' => 32, 'min_stock' => 10],
]);
$low = $ingredients->filter(fn ($i) => $i->stock_qty <= $i->min_stock)->values();

$movements = $many([
    ['ingredient' => 'Susu cair', 'unit' => 'ml', 'type' => 'out', 'qty' => 180, 'notes' => 'Penjualan #INV-0142', 'user' => 'Dimas Kasir', 'time' => '14:32'],
    ['ingredient' => 'Biji kopi arabika', 'unit' => 'gram', 'type' => 'in', 'qty' => 2000, 'notes' => 'Beli di supplier', 'user' => 'Rina Admin', 'time' => '10:05'],
    ['ingredient' => 'Telur ayam', 'unit' => 'pcs', 'type' => 'adjustment', 'qty' => 14, 'notes' => 'Opname — 2 pecah', 'user' => 'Rina Admin', 'time' => '09:40'],
    ['ingredient' => 'Bubuk coklat', 'unit' => 'gram', 'type' => 'out', 'qty' => 40, 'notes' => 'Penjualan #INV-0139', 'user' => 'Dimas Kasir', 'time' => 'Kemarin'],
    ['ingredient' => 'Gula pasir', 'unit' => 'gram', 'type' => 'in', 'qty' => 5000, 'notes' => 'Beli di Pasar Petisah', 'user' => 'Rina Admin', 'time' => 'Kemarin'],
]);

$trx = fn ($n, $t, $items, $m, $total, $s = 'paid') => $o(['number' => "INV-$n", 'time' => $t, 'items' => $items, 'method' => $m, 'total' => $total, 'status' => $s]);
$transactions = collect([
    $trx('0142', '14:32', '2× Kopi Susu Kaleng, 1× Roti Bakar', 'Tunai', 42000),
    $trx('0141', '14:05', '1× Mie Goreng Rumahan, 1× Es Teh', 'QRIS', 22000),
    $trx('0140', '13:48', '3× Americano', 'Tunai', 36000),
    $trx('0139', '13:10', '1× Nasi Goreng Kaleng', 'Debit', 18000),
    $trx('0138', '12:51', '2× Latte Vintage', 'QRIS', 36000, 'void'),
]);

// ---------- Landing & auth ----------
Route::view('/', 'welcome')->name('home');
Route::view('/login', 'auth.login')->name('login');

// ---------- Admin ----------
Route::prefix('admin')->group(function () use ($categories, $menus, $ingredients, $low, $movements, $o) {
    Route::get('dashboard', fn () => view('admin.dashboard', [
        'stats' => ['menus' => 24, 'categories' => 4, 'ingredients' => $ingredients->count(), 'today' => 38],
        'lowStock' => $low, 'movements' => $movements,
    ]));
    Route::get('categories', fn () => view('admin.categories', compact('categories')));
    Route::get('menus', fn () => view('admin.menus', compact('categories', 'menus')));
    Route::get('menus/{id}/recipe', fn ($id) => view('admin.recipe', [
        'menu' => $menus->firstWhere('id', (int) $id) ?? $menus->first(),
        'ingredients' => $ingredients,
        'recipe' => collect([
            $o(['ingredient_id' => 1, 'qty_needed' => 18, 'unit' => 'gram']),
            $o(['ingredient_id' => 2, 'qty_needed' => 120, 'unit' => 'ml']),
            $o(['ingredient_id' => 3, 'qty_needed' => 10, 'unit' => 'gram']),
        ]),
    ]));
    Route::get('ingredients', fn () => view('admin.ingredients', compact('ingredients')));
    Route::get('stock', fn () => view('admin.stock', ['ingredients' => $ingredients, 'lowStock' => $low, 'movements' => $movements]));
});

// ---------- Kasir ----------
Route::prefix('kasir')->group(function () use ($categories, $menus, $transactions, $o) {
    Route::get('dashboard', fn () => view('kasir.dashboard', [
        'stats' => ['count' => 18, 'revenue' => 612000, 'cash' => 348000, 'cashless' => 264000],
        'recent' => $transactions->take(4),
    ]));
    Route::get('pos', fn () => view('kasir.pos', ['categories' => $categories, 'menus' => $menus->where('status', 'active')->values()]));
    Route::get('history', fn () => view('kasir.history', compact('transactions')));
    Route::get('receipt', fn () => view('kasir.receipt', ['trx' => $o([
        'number' => 'INV-0142', 'date' => now()->format('d/m/Y H:i'), 'cashier' => 'Dimas',
        'items' => collect([['name' => 'Kopi Susu Kaleng', 'qty' => 2, 'price' => 15000], ['name' => 'Roti Bakar Coklat Keju', 'qty' => 1, 'price' => 12000]])->map(fn ($i) => (object) $i),
        'subtotal' => 42000, 'discount' => 2000, 'total' => 40000, 'method' => 'Tunai', 'paid' => 50000, 'change' => 10000,
    ])]));
});

// ---------- Owner ----------
Route::prefix('owner')->group(function () use ($ingredients, $many, $o) {
    Route::get('dashboard', fn () => view('owner.dashboard', [
        'stats' => ['today' => 1245000, 'today_delta' => '+12%', 'month' => 24380000, 'count' => 1126],
        'chart' => ['labels' => ['Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min', 'Sen'],
                    'data' => [820000, 910000, 760000, 1340000, 1580000, 1420000, 980000, 870000, 940000, 1010000, 1390000, 1720000, 1610000, 1245000]],
        'top' => $many([['name' => 'Kopi Susu Kaleng', 'sold' => 412], ['name' => 'Mie Goreng Rumahan', 'sold' => 287], ['name' => 'Es Teh Manis', 'sold' => 265], ['name' => 'Roti Bakar Coklat Keju', 'sold' => 198], ['name' => 'Americano', 'sold' => 154]]),
        'critical' => $ingredients->filter(fn ($i) => $i->stock_qty <= $i->min_stock)->values(),
    ]));
    Route::get('reports/sales', fn () => view('owner.report-sales', [
        'rows' => $many(collect(range(0, 6))->map(fn ($d) => ['date' => now()->subDays(6 - $d)->translatedFormat('D, d M'), 'count' => 28 + $d * 3, 'items' => 71 + $d * 7, 'total' => 820000 + $d * 120000])->all()),
        'sum' => ['total' => 8610000, 'count' => 273, 'avg' => 31538],
    ]));
    Route::get('reports/profit', fn () => view('owner.report-profit', ['pl' => ['sales' => 24380000, 'purchases' => 9150000]]));
    Route::get('reports/stock', fn () => view('owner.report-stock', ['rows' => $ingredients->map(fn ($i) => $o([...(array) $i, 'in' => $i->stock_qty + 1800, 'out' => 1800]))]));
});

<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DapurController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\WaitressController;
use Illuminate\Support\Facades\Route;

// ---------- Publik ----------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store')->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout')->middleware('auth');

// Pintasan: kirim pengguna ke beranda sesuai rolenya.
Route::get('/dashboard', fn () => redirect()->route(auth()->user()->homeRoute()))->middleware('auth');

// ---------- Admin ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('categories', [Admin\CategoryController::class, 'index'])->name('categories');
    Route::post('categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('menus', [Admin\MenuController::class, 'index'])->name('menus');
    Route::post('menus', [Admin\MenuController::class, 'store'])->name('menus.store');
    Route::put('menus/{menu}', [Admin\MenuController::class, 'update'])->name('menus.update');
    Route::delete('menus/{menu}', [Admin\MenuController::class, 'destroy'])->name('menus.destroy');
    Route::get('menus/{menu}/recipe', [Admin\MenuController::class, 'recipe'])->name('menus.recipe');
    Route::put('menus/{menu}/recipe', [Admin\MenuController::class, 'saveRecipe'])->name('menus.recipe.save');

    Route::get('ingredients', [Admin\IngredientController::class, 'index'])->name('ingredients');
    Route::post('ingredients', [Admin\IngredientController::class, 'store'])->name('ingredients.store');
    Route::put('ingredients/{ingredient}', [Admin\IngredientController::class, 'update'])->name('ingredients.update');
    Route::delete('ingredients/{ingredient}', [Admin\IngredientController::class, 'destroy'])->name('ingredients.destroy');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users');
    Route::post('users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');

    Route::get('stock', [Admin\StockController::class, 'index'])->name('stock');
    Route::post('stock/in', [Admin\StockController::class, 'store'])->name('stock.in');
    Route::post('stock/opname', [Admin\StockController::class, 'opname'])->name('stock.opname');
});

// ---------- Kasir ----------
Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('dashboard', [PosController::class, 'dashboard'])->name('dashboard');
    Route::get('pos', [PosController::class, 'index'])->name('pos');
    Route::get('history', [PosController::class, 'history'])->name('history');
    Route::post('orders', [PosController::class, 'store'])->name('orders.store');
    Route::get('orders/{transaction}/pay', [PosController::class, 'showPayment'])->name('orders.pay');
    Route::post('orders/{transaction}/pay', [PosController::class, 'pay'])->name('orders.pay.store');
    Route::post('orders/{transaction}/void', [PosController::class, 'void'])->name('orders.void');
    Route::get('orders/{transaction}/receipt', [PosController::class, 'receipt'])->name('orders.receipt');
});

// ---------- Dapur ----------
Route::middleware(['auth', 'role:dapur'])->prefix('dapur')->name('dapur.')->group(function () {
    Route::get('board', [DapurController::class, 'board'])->name('board');
    Route::post('orders/{transaction}/advance', [DapurController::class, 'advance'])->name('advance');
});

// ---------- Pelayan ----------
Route::middleware(['auth', 'role:waitress'])->prefix('pelayan')->name('waitress.')->group(function () {
    Route::get('board', [WaitressController::class, 'board'])->name('board');
    Route::post('orders/{transaction}/serve', [WaitressController::class, 'serve'])->name('serve');
});

// ---------- Owner ----------
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('dashboard', [OwnerController::class, 'dashboard'])->name('dashboard');
    Route::get('reports/sales', [OwnerController::class, 'sales_report'])->name('sales');
    Route::get('reports/profit', [OwnerController::class, 'profit_report'])->name('profit');
    Route::get('reports/stock', [OwnerController::class, 'stock_report'])->name('stock');
});

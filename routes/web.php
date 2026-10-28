<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute POS (kasir) — sudah memakai controller asli
|--------------------------------------------------------------------------
| Middleware login/role belum dipasang karena autentikasi dikerjakan anggota lain.
| Setelah tersedia, bungkus grup ini dengan middleware role kasir.
*/
Route::prefix('kasir')->name('kasir.')->group(function () {
    Route::get('dashboard', [PosController::class, 'dashboard'])->name('dashboard');
    Route::get('pos', [PosController::class, 'index'])->name('pos');
    Route::get('history', [PosController::class, 'history'])->name('history');
    Route::post('orders', [PosController::class, 'store'])->name('orders.store');
    Route::get('orders/{transaction}/pay', [PosController::class, 'showPayment'])->name('orders.pay');
    Route::post('orders/{transaction}/pay', [PosController::class, 'pay'])->name('orders.pay.store');
    Route::post('orders/{transaction}/void', [PosController::class, 'void'])->name('orders.void');
    Route::get('orders/{transaction}/receipt', [PosController::class, 'receipt'])->name('orders.receipt');
});

/*
|--------------------------------------------------------------------------
| Rute UI (pratinjau)
|--------------------------------------------------------------------------
| ui-preview.php berisi route + DATA CONTOH supaya semua halaman bisa dibuka
| tanpa backend. Saat controller sudah jadi, pindahkan route ke grup
| middleware PM (role:admin dst) dan hapus baris require di bawah.
*/
require __DIR__.'/ui-preview.php';

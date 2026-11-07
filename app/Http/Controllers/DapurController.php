<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Dapur: antrian pesanan yang sudah dibayar. Saat "siap", trigger DB memotong stok bahan. */
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

        return view('dapur.board', compact('queue', 'done'));
    }

    public function advance(Transaction $transaction): RedirectResponse
    {
        $next = ['waiting' => 'preparing', 'preparing' => 'ready'][$transaction->order_status] ?? null;

        if ($transaction->status !== Transaction::STATUS_PAID || ! $next) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini tidak bisa diproses.']);
        }

        $this->stock->asUser(auth()->id(), fn () => Transaction::where('id', $transaction->id)
            ->where('order_status', $transaction->order_status)
            ->update(['order_status' => $next]));

        return back()->with('success', "{$transaction->number}: ".Transaction::ORDER_LABEL[$next].'.');
    }
}

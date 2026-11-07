<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pelayan: mengantar pesanan yang sudah ditandai siap oleh dapur (order_status ready -> served).
 * Trigger fn_guard_order_status menjamin "served" hanya dari "ready" dan tidak bisa dibatalkan lagi.
 */
class WaitressController extends Controller
{
    public function board(): View
    {
        $base = Transaction::with('details.menu')->where('status', Transaction::STATUS_PAID);

        $ready = (clone $base)->where('order_status', 'ready')->orderBy('id')->get();
        $coming = (clone $base)->whereIn('order_status', ['waiting', 'preparing'])->orderBy('id')->get();
        $served = (clone $base)->where('order_status', 'served')->whereDate('transaction_date', today())->latest('id')->take(8)->get();

        return view('waitress.board', compact('ready', 'coming', 'served'));
    }

    public function serve(Transaction $transaction): RedirectResponse
    {
        $updated = Transaction::where('id', $transaction->id)
            ->where('status', Transaction::STATUS_PAID)
            ->where('order_status', 'ready')
            ->update(['order_status' => 'served']);

        if (! $updated) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini belum siap atau sudah diantar.']);
        }

        return back()->with('success', "{$transaction->number} sudah diantar.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pelayan: mengantar pesanan yang sudah ditandai siap oleh dapur (order_status ready -> served),
 * lalu melihat riwayat pesanan yang sudah diantar & dikonfirmasi.
 * Trigger fn_guard_order_status menjamin "served" hanya dari "ready" dan tidak bisa dibatalkan lagi.
 */
class WaitressController extends Controller
{
    public function board(): View
    {
        $base = Transaction::with('details.menu')->where('status', Transaction::STATUS_PAID);

        $ready = (clone $base)->where('order_status', 'ready')->orderBy('id')->get();
        $coming = (clone $base)->whereIn('order_status', ['waiting', 'preparing'])->orderBy('id')->get();
        $served = (clone $base)->where('order_status', 'served')
            ->whereRaw('DATE(COALESCE(served_at, transaction_date)) = ?', [today()->toDateString()])
            ->orderByRaw('COALESCE(served_at, transaction_date) DESC')->take(8)->get();

        return view('waitress.board', compact('ready', 'coming', 'served'));
    }

    /** Riwayat pesanan yang sudah diantar & dikonfirmasi, per tanggal pengantaran. */
    public function history(Request $request): View
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? today()->toDateString();

        $orders = Transaction::with(['details.menu', 'server'])
            ->where('status', Transaction::STATUS_PAID)
            ->where('order_status', 'served')
            ->whereRaw('DATE(COALESCE(served_at, transaction_date)) = ?', [$date])
            ->orderByRaw('COALESCE(served_at, transaction_date) DESC')
            ->orderByDesc('id')
            ->get();

        return view('waitress.history', compact('orders', 'date'));
    }

    public function serve(Transaction $transaction): RedirectResponse
    {
        $updated = Transaction::where('id', $transaction->id)
            ->where('status', Transaction::STATUS_PAID)
            ->where('order_status', 'ready')
            ->update(['order_status' => 'served', 'served_at' => now(), 'served_by' => Auth::id()]);

        if (! $updated) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini belum siap atau sudah diantar.']);
        }

        return back()->with('success', "{$transaction->number} sudah diantar.");
    }
}

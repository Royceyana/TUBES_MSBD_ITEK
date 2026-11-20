<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\OrderViews;
use App\Support\DbMessage;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pelayan: mengantar pesanan yang sudah ditandai siap oleh dapur (order_status ready -> served),
 * lalu melihat riwayat pesanan yang sudah diantar & dikonfirmasi.
 * Trigger fn_guard_order_status menjamin "served" hanya dari "ready" dan tidak bisa dibatalkan lagi.
 */
class WaitressController extends Controller
{
    public function __construct(private OrderViews $orders)
    {
    }

    public function board(): View
    {
        // v_order_queue + v_order_lines untuk pesanan berjalan; v_transaction_summary untuk yang sudah diantar.
        $open = $this->orders->openOrders();

        $ready = $this->orders->withLines($open->where('order_status', 'ready')->values());
        $coming = $this->orders->decorate($open->whereIn('order_status', ['waiting', 'preparing'])->values());
        $served = $this->orders->decorate($this->served(today()->toDateString())->take(8)->values());

        return view('waitress.board', compact('ready', 'coming', 'served'));
    }

    /** Riwayat pesanan yang sudah diantar & dikonfirmasi, per tanggal pengantaran. */
    public function history(Request $request): View
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? today()->toDateString();

        $orders = $this->orders->withLines($this->served($date));

        return view('waitress.history', compact('orders', 'date'));
    }

    /** Pesanan lunas yang sudah diantar pada tanggal tertentu, terbaru dulu (view v_transaction_summary). */
    private function served(string $date)
    {
        return DB::table('v_transaction_summary')
            ->where('status', 'completed')->where('order_status', 'served')
            ->whereRaw('DATE(COALESCE(served_at, tanggal)) = ?', [$date])
            ->orderByRaw('COALESCE(served_at, tanggal) DESC')->orderByDesc('id')
            ->get();
    }

    public function serve(Transaction $transaction): RedirectResponse
    {
        try {
            // sp_set_order_status: cek peran + urutan status, lalu catat served_at dan served_by.
            DB::transaction(fn () => DB::statement('CALL sp_set_order_status(?::integer, ?::text, ?::integer)', [$transaction->id, 'served', Auth::id()]));
        } catch (QueryException $e) {
            throw ValidationException::withMessages(['order' => DbMessage::from($e, 'Pesanan ini belum siap atau sudah diantar.')]);
        }

        return back()->with('success', "{$transaction->number} sudah diantar.");
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Membaca antrian pesanan dari view database (v_order_queue, v_order_lines, v_transaction_summary),
 * dipakai papan Dapur dan Pelayan serta riwayat antar.
 */
class OrderViews
{
    /** Pesanan lunas yang belum diantar, urut dari yang terlama (view v_order_queue). */
    public function openOrders(): Collection
    {
        return DB::table('v_order_queue')->where('status', 'completed')->orderBy('id')->get();
    }

    /** Tambahkan nomor, label tempat, dan jam ke tiap baris pesanan dari view. */
    public function decorate(Collection $orders): Collection
    {
        return $orders->map(function ($t) {
            $t->number = $t->nomor;
            $t->place_label = ($t->order_type ?? 'dine_in') === 'takeaway' ? 'Bawa pulang' : 'Meja '.$t->table_no;
            $t->time = Carbon::parse($t->tanggal)->format('H:i');
            $t->served_time = isset($t->served_at) || isset($t->tanggal)
                ? Carbon::parse($t->served_at ?? $t->tanggal)->format('H:i')
                : null;

            return $t;
        })->values();
    }

    /** decorate() + rincian item (lines) tiap pesanan dari view v_order_lines. */
    public function withLines(Collection $orders): Collection
    {
        $lines = $orders->isEmpty()
            ? collect()
            : DB::table('v_order_lines')->whereIn('transaction_id', $orders->pluck('id')->all())->orderBy('detail_id')->get()->groupBy('transaction_id');

        return $this->decorate($orders)->map(function ($t) use ($lines) {
            $t->lines = $lines[$t->id] ?? collect();

            return $t;
        })->values();
    }
}

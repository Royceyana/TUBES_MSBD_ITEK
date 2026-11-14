<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Laporan pemilik. Hanya transaksi berstatus completed yang dihitung sebagai penjualan. */
class OwnerController extends Controller
{
    private function sales()
    {
        return DB::table('transactions')->where('status', 'completed');
    }

    public function dashboard(): View
    {
        $today = (int) $this->sales()->whereDate('transaction_date', today())->sum('total');
        $yesterday = (int) $this->sales()->whereDate('transaction_date', today()->subDay())->sum('total');
        $monthQ = $this->sales()->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfDay()]);

        $stats = [
            'today' => $today,
            'today_delta' => $yesterday > 0 ? sprintf('%+d%% dari kemarin', round(($today - $yesterday) / $yesterday * 100)) : null,
            'month' => (int) (clone $monthQ)->sum('total'),
            'count' => (clone $monthQ)->count(),
        ];

        $perDay = $this->sales()
            ->whereDate('transaction_date', '>=', today()->subDays(13))
            ->selectRaw('transaction_date::date as d, sum(total) as t')->groupBy('d')->pluck('t', 'd');
        $labels = [];
        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $labels[] = $d->translatedFormat('D d');
            $data[] = (int) ($perDay[$d->toDateString()] ?? 0);
        }
        $chart = compact('labels', 'data');

        $top = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->join('menus as m', 'm.id', '=', 'd.menu_id')
            ->where('t.status', 'completed')
            ->whereBetween('t.transaction_date', [now()->startOfMonth(), now()->endOfDay()])
            ->groupBy('m.id', 'm.name')->orderByDesc(DB::raw('sum(d.qty)'))->limit(5)
            ->get(['m.name', DB::raw('sum(d.qty)::int as sold')]);

        $critical = DB::table('v_ingredient_stock')->where('stock_status', '!=', 'aman')->orderBy('stock_qty')->get();

        return view('owner.dashboard', [
            'stats' => $stats, 'chart' => $chart, 'top' => $top, 'critical' => $critical,
            'orders' => $this->orderSummary(), 'margins' => $this->menuMargins(), 'stock' => $this->stockSummary(),
        ]);
    }

    /** Ringkasan status pesanan hari ini: belum dibayar, tahap dapur/pelayan (hanya yang sudah dibayar), dan batal. */
    private function orderSummary(): array
    {
        $r = DB::table('transactions')->whereDate('transaction_date', today())->selectRaw("
            count(*) filter (where status = 'pending') as unpaid,
            count(*) filter (where status = 'completed' and order_status = 'waiting') as waiting,
            count(*) filter (where status = 'completed' and order_status = 'preparing') as preparing,
            count(*) filter (where status = 'completed' and order_status = 'ready') as ready,
            count(*) filter (where status = 'completed' and order_status = 'served') as served,
            count(*) filter (where status = 'cancelled') as cancelled,
            count(*) as total
        ")->first();

        return array_map('intval', (array) $r);
    }

    /** Margin per menu aktif dari view v_menu_hpp (harga jual - HPP resep), margin terendah di atas. */
    private function menuMargins()
    {
        return DB::table('v_menu_hpp as h')
            ->join('menus as m', 'm.id', '=', 'h.menu_id')
            ->where('m.status', 'active')
            ->orderByRaw('h.margin_persen asc nulls last')->orderBy('h.name')
            ->get(['h.menu_id', 'h.name', 'h.price', 'h.jumlah_bahan', 'h.hpp', 'h.margin', 'h.margin_persen']);
    }

    /** Hitungan bahan per status dari view v_ingredient_stock. */
    private function stockSummary(): array
    {
        $c = DB::table('v_ingredient_stock')->selectRaw('stock_status, count(*) as n')->groupBy('stock_status')->pluck('n', 'stock_status');

        return [
            'aman' => (int) ($c['aman'] ?? 0), 'menipis' => (int) ($c['menipis'] ?? 0),
            'habis' => (int) ($c['habis'] ?? 0), 'total' => (int) $c->sum(),
        ];
    }

    /** Rentang tanggal dari query ?from=&to= (default 7 hari terakhir). */
    private function range(Request $r): array
    {
        $v = $r->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d']]);
        $to = Carbon::parse($v['to'] ?? today());
        $from = Carbon::parse($v['from'] ?? today()->subDays(6));
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    public function sales_report(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $days = $this->sales()->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('transaction_date::date as date, count(*) as count, coalesce(sum(discount_amount),0) as discount, coalesce(sum(total),0) as total')
            ->groupBy('date')->orderBy('date')->get();

        $items = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->where('t.status', 'completed')->whereBetween('t.transaction_date', [$from, $to])
            ->selectRaw('t.transaction_date::date as date, sum(d.qty)::int as items')->groupBy('date')->pluck('items', 'date');

        $rows = $days->map(function ($r) use ($items) {
            $r->items = (int) ($items[$r->date] ?? 0);
            $r->date = Carbon::parse($r->date)->translatedFormat('D, d M Y');
            $r->total = (int) $r->total;
            $r->discount = (int) $r->discount;

            return $r;
        });

        $sum = ['total' => (int) $rows->sum('total'), 'count' => (int) $rows->sum('count'), 'discount' => (int) $rows->sum('discount')];
        $sum['avg'] = $sum['count'] ? intdiv($sum['total'], $sum['count']) : 0;

        return view('owner.report-sales', ['rows' => $rows, 'sum' => $sum, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    /** Laba kotor = penjualan bersih (setelah diskon) - HPP (qty x biaya bahan per resep, dari view v_menu_hpp). */
    public function profit_report(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $rows = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->join('menus as m', 'm.id', '=', 'd.menu_id')
            ->leftJoin('v_menu_hpp as h', 'h.menu_id', '=', 'm.id')
            ->where('t.status', 'completed')
            ->whereBetween('t.transaction_date', [$from, $to])
            ->groupBy('m.id', 'm.name')
            ->orderByDesc(DB::raw('sum(d.subtotal)'))
            ->get(['m.name', DB::raw('sum(d.qty)::int as qty'), DB::raw('sum(d.subtotal) as revenue'), DB::raw('sum(d.qty) * coalesce(max(h.hpp),0) as cogs')]);

        return view('owner.report-profit', [
            'rows' => $rows,
            'pl' => [
                'gross' => (int) $rows->sum('revenue'),
                'discount' => (int) $this->sales()->whereBetween('transaction_date', [$from, $to])->sum('discount_amount'),
                'cogs' => (int) $rows->sum('cogs'),
            ],
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
        ]);
    }

    public function stock_report(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $moves = DB::table('stock_movements')->whereBetween('created_at', [$from, $to])
            ->selectRaw("ingredient_id, sum(case when type='in' then qty else 0 end) as qin, sum(case when type='out' then qty else 0 end) as qout")
            ->groupBy('ingredient_id')->get()->keyBy('ingredient_id');

        $rows = DB::table('v_ingredient_stock')->orderBy('name')->get()->map(function ($i) use ($moves) {
            $i->in = (float) ($moves[$i->id]->qin ?? 0);
            $i->out = (float) ($moves[$i->id]->qout ?? 0);

            return $i;
        });

        return view('owner.report-stock', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }
}
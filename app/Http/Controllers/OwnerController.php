<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Laporan pemilik (baca-saja). Semua angka dibaca dari view database:
 * v_sales_daily, v_sales_by_menu_daily, v_menu_hpp, v_ingredient_stock, v_stock_flow_daily, v_transaction_summary,
 * dan function fn_sales_report.
 * Hanya transaksi berstatus completed yang dihitung sebagai penjualan (sudah disaring di dalam view).
 */
class OwnerController extends Controller
{
    private function daily()
    {
        return DB::table('v_sales_daily');
    }

    public function dashboard(): View
    {
        $today = (int) $this->daily()->where('tanggal', today()->toDateString())->sum('omzet');
        $yesterday = (int) $this->daily()->where('tanggal', today()->subDay()->toDateString())->sum('omzet');
        $monthRange = [now()->startOfMonth()->toDateString(), today()->toDateString()];

        $stats = [
            'today' => $today,
            'today_delta' => $yesterday > 0 ? sprintf('%+d%% dari kemarin', round(($today - $yesterday) / $yesterday * 100)) : null,
            'month' => (int) $this->daily()->whereBetween('tanggal', $monthRange)->sum('omzet'),
            'count' => (int) $this->daily()->whereBetween('tanggal', $monthRange)->sum('jumlah_transaksi'),
        ];

        $perDay = $this->daily()->where('tanggal', '>=', today()->subDays(13)->toDateString())->pluck('omzet', 'tanggal');
        $labels = [];
        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = today()->subDays($i);
            $labels[] = $d->translatedFormat('D d');
            $data[] = (int) ($perDay[$d->toDateString()] ?? 0);
        }
        $chart = compact('labels', 'data');

        $top = DB::table('v_sales_by_menu_daily')
            ->whereBetween('tanggal', $monthRange)
            ->groupBy('menu_id', 'name')->orderByDesc(DB::raw('sum(qty_terjual)'))->limit(5)
            ->get(['name', DB::raw('sum(qty_terjual)::int as sold')]);

        $critical = DB::table('v_ingredient_stock')->where('stock_status', '!=', 'aman')->orderBy('stock_qty')->get();

        return view('owner.dashboard', [
            'stats' => $stats, 'chart' => $chart, 'top' => $top, 'critical' => $critical,
            'orders' => $this->orderSummary(), 'margins' => $this->menuMargins(), 'stock' => $this->stockSummary(),
        ]);
    }

    /** Ringkasan status pesanan hari ini: belum dibayar, tahap dapur/pelayan (hanya yang sudah dibayar), dan batal. */
    private function orderSummary(): array
    {
        $r = DB::table('v_transaction_summary')->whereDate('tanggal', today())->selectRaw("
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
        $between = [$from->toDateString(), $to->toDateString()];

        // Ringkasan per hari dihitung function database fn_sales_report(dari, sampai).
        $days = collect(DB::select('select tanggal as date, jumlah_transaksi as count, diskon as discount, omzet as total from fn_sales_report(?::date, ?::date)', $between));

        $items = DB::table('v_sales_by_menu_daily')->whereBetween('tanggal', $between)
            ->selectRaw('tanggal as date, sum(qty_terjual)::int as items')->groupBy('tanggal')->pluck('items', 'date');

        $rows = $days->map(function ($r) use ($items) {
            $r->items = (int) ($items[$r->date] ?? 0);
            $r->date = Carbon::parse($r->date)->translatedFormat('D, d M Y');
            $r->count = (int) $r->count;
            $r->total = (int) $r->total;
            $r->discount = (int) $r->discount;

            return $r;
        });

        $sum = ['total' => (int) $rows->sum('total'), 'count' => (int) $rows->sum('count'), 'discount' => (int) $rows->sum('discount')];
        $sum['avg'] = $sum['count'] ? intdiv($sum['total'], $sum['count']) : 0;

        return view('owner.report-sales', ['rows' => $rows, 'sum' => $sum, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    /** Laba kotor = penjualan bersih (setelah diskon) - HPP, dari view v_sales_by_menu_daily (HPP asal v_menu_hpp). */
    public function profit_report(Request $request): View
    {
        [$from, $to] = $this->range($request);
        $between = [$from->toDateString(), $to->toDateString()];

        $rows = DB::table('v_sales_by_menu_daily')
            ->whereBetween('tanggal', $between)
            ->groupBy('menu_id', 'name')
            ->orderByDesc(DB::raw('sum(pendapatan)'))
            ->get(['name', DB::raw('sum(qty_terjual)::int as qty'), DB::raw('sum(pendapatan) as revenue'), DB::raw('sum(hpp_total) as cogs')]);

        return view('owner.report-profit', [
            'rows' => $rows,
            'pl' => [
                'gross' => (int) $rows->sum('revenue'),
                'discount' => (int) collect(DB::select('select diskon from fn_sales_report(?::date, ?::date)', $between))->sum('diskon'),
                'cogs' => (int) $rows->sum('cogs'),
            ],
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
        ]);
    }

    public function stock_report(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $moves = DB::table('v_stock_flow_daily')->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('ingredient_id, sum(qty_masuk) as qin, sum(qty_keluar) as qout')
            ->groupBy('ingredient_id')->get()->keyBy('ingredient_id');

        $rows = DB::table('v_ingredient_stock')->orderBy('name')->get()->map(function ($i) use ($moves) {
            $i->in = (float) ($moves[$i->id]->qin ?? 0);
            $i->out = (float) ($moves[$i->id]->qout ?? 0);

            return $i;
        });

        return view('owner.report-stock', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }
}

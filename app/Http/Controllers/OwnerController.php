<?php

namespace App\Http\Controllers;

use App\Support\XlsxExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function sales_report(Request $request): View|BinaryFileResponse
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

        if ($mode = $this->exportMode($request)) {
            return $this->exportOutput($mode, $this->buildSales($rows, $sum, $from, $to), 'laporan-penjualan', 'owner.sales', 'Laporan Penjualan', $from, $to);
        }

        return view('owner.report-sales', ['rows' => $rows, 'sum' => $sum, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    /** Laba kotor = penjualan bersih (setelah diskon) - HPP (qty x biaya bahan per resep, dari view v_menu_hpp). */
    public function profit_report(Request $request): View|BinaryFileResponse
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

        $pl = [
            'gross' => (int) $rows->sum('revenue'),
            'discount' => (int) $this->sales()->whereBetween('transaction_date', [$from, $to])->sum('discount_amount'),
            'cogs' => (int) $rows->sum('cogs'),
        ];

        if ($mode = $this->exportMode($request)) {
            return $this->exportOutput($mode, $this->buildProfit($rows, $pl, $from, $to), 'laporan-laba-kotor', 'owner.profit', 'Laporan Laba Kotor', $from, $to);
        }

        return view('owner.report-profit', [
            'rows' => $rows,
            'pl' => $pl,
            'from' => $from->toDateString(), 'to' => $to->toDateString(),
        ]);
    }

    public function stock_report(Request $request): View|BinaryFileResponse
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

        if ($mode = $this->exportMode($request)) {
            return $this->exportOutput($mode, $this->buildStock($rows, $from, $to), 'laporan-stok', 'owner.stock', 'Laporan Stok', $from, $to);
        }

        return view('owner.report-stock', ['rows' => $rows, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    // ---------- Ekspor spreadsheet (.xlsx) untuk tombol "Cetak" ----------

    /** Tiga baris kepala laporan: judul, periode, waktu cetak. */
    private function exportHead(XlsxExport $x, string $title, Carbon $from, Carbon $to): void
    {
        $x->addRow(['Kafe Kaleng - '.$title], XlsxExport::S_TITLE);
        $x->addRow(['Periode: '.$from->translatedFormat('d M Y').' s/d '.$to->translatedFormat('d M Y')], XlsxExport::S_NOTE);
        $x->addRow(['Dicetak: '.now()->translatedFormat('d M Y H:i')], XlsxExport::S_NOTE);
        $x->skipRow();
    }

    /** 'preview' (halaman pratinjau) atau 'xlsx' (unduh langsung); null = tampilan laporan biasa. */
    private function exportMode(Request $request): ?string
    {
        $m = $request->query('export');

        return in_array($m, ['preview', 'xlsx'], true) ? $m : null;
    }

    private function exportOutput(string $mode, XlsxExport $x, string $name, string $route, string $title, Carbon $from, Carbon $to): View|BinaryFileResponse
    {
        $file = sprintf('%s_%s_%s.xlsx', $name, $from->toDateString(), $to->toDateString());
        $query = ['from' => $from->toDateString(), 'to' => $to->toDateString()];

        if ($mode === 'preview') {
            return view('owner.report-preview', [
                'title' => $title, 'sheet' => $x->preview(), 'file' => $file,
                'backUrl' => route($route, $query), 'downloadUrl' => route($route, $query + ['export' => 'xlsx']),
            ]);
        }

        return response()->download($x->save(), $file, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildSales($rows, array $sum, Carbon $from, Carbon $to): XlsxExport
    {
        $x = new XlsxExport('Penjualan');
        $x->setWidths([22, 14, 16, 16, 22]);
        $this->exportHead($x, 'Laporan Penjualan per Hari', $from, $to);

        $x->addRow(['Tanggal', 'Transaksi', 'Item terjual', 'Diskon (Rp)', 'Penjualan bersih (Rp)'], XlsxExport::S_HEAD);
        $first = $x->nextRow();
        foreach ($rows as $r) {
            $x->addRow([$r->date, (int) $r->count, (int) $r->items, (int) $r->discount, (int) $r->total],
                [XlsxExport::S_TEXT, XlsxExport::S_INT, XlsxExport::S_INT, XlsxExport::S_INT, XlsxExport::S_INT]);
        }
        if ($rows->isEmpty()) {
            $x->addRow(['Tidak ada penjualan lunas pada rentang ini.', null, null, null, null], XlsxExport::S_TEXT);
        }
        $last = $x->nextRow() - 1;

        $t = $x->nextRow();
        $x->addRow([
            'Total',
            ['f' => "SUM(B{$first}:B{$last})", 'v' => $sum['count']],
            ['f' => "SUM(C{$first}:C{$last})", 'v' => (int) $rows->sum('items')],
            ['f' => "SUM(D{$first}:D{$last})", 'v' => $sum['discount']],
            ['f' => "SUM(E{$first}:E{$last})", 'v' => $sum['total']],
        ], [XlsxExport::S_TOTAL_TEXT, XlsxExport::S_TOTAL_INT, XlsxExport::S_TOTAL_INT, XlsxExport::S_TOTAL_INT, XlsxExport::S_TOTAL_INT]);

        $x->skipRow();
        $x->addRow(['Rata-rata per transaksi (Rp)', null, null, null, ['f' => "IF(B{$t}=0,0,INT(E{$t}/B{$t}))", 'v' => $sum['avg']]],
            [XlsxExport::S_LABEL, 0, 0, 0, XlsxExport::S_INT]);

        return $x;
    }

    private function buildProfit($rows, array $pl, Carbon $from, Carbon $to): XlsxExport
    {
        $net = $pl['gross'] - $pl['discount'];
        $profit = $net - $pl['cogs'];
        $margin = $net > 0 ? round($profit / $net, 3) : 0;

        $x = new XlsxExport('Laba Kotor');
        $x->setWidths([36, 12, 22, 20, 22]);
        $this->exportHead($x, 'Laporan Laba Kotor', $from, $to);

        $x->addRow(['Menu', 'Terjual', 'Penjualan kotor (Rp)', 'HPP (Rp)', 'Laba kotor menu (Rp)'], XlsxExport::S_HEAD);
        $first = $x->nextRow();
        foreach ($rows as $r) {
            $n = $x->nextRow();
            $x->addRow([$r->name, (int) $r->qty, (int) round($r->revenue), (int) round($r->cogs), ['f' => "C{$n}-D{$n}", 'v' => (int) round($r->revenue) - (int) round($r->cogs)]],
                [XlsxExport::S_TEXT, XlsxExport::S_INT, XlsxExport::S_INT, XlsxExport::S_INT, XlsxExport::S_INT]);
        }
        if ($rows->isEmpty()) {
            $x->addRow(['Tidak ada penjualan lunas pada rentang ini.', null, null, null, null], XlsxExport::S_TEXT);
        }
        $last = $x->nextRow() - 1;

        $t = $x->nextRow();
        $x->addRow([
            'Total',
            ['f' => "SUM(B{$first}:B{$last})", 'v' => (int) $rows->sum('qty')],
            ['f' => "SUM(C{$first}:C{$last})", 'v' => $pl['gross']],
            ['f' => "SUM(D{$first}:D{$last})", 'v' => $pl['cogs']],
            ['f' => "SUM(E{$first}:E{$last})", 'v' => $pl['gross'] - $pl['cogs']],
        ], [XlsxExport::S_TOTAL_TEXT, XlsxExport::S_TOTAL_INT, XlsxExport::S_TOTAL_INT, XlsxExport::S_TOTAL_INT, XlsxExport::S_TOTAL_INT]);

        // Ringkasan laba kotor: nilai diskon berasal dari tabel transaksi (bukan dari per menu).
        $x->skipRow();
        $x->addRow(['Ringkasan Laba Kotor'], XlsxExport::S_LABEL);
        $g = $x->addRow(['Penjualan kotor', null, ['f' => "C{$t}", 'v' => $pl['gross']]], [XlsxExport::S_TEXT, XlsxExport::S_TEXT, XlsxExport::S_INT]);
        $d = $x->addRow(['Diskon diberikan', null, $pl['discount']], [XlsxExport::S_TEXT, XlsxExport::S_TEXT, XlsxExport::S_INT]);
        $n = $x->addRow(['Penjualan bersih (setelah diskon)', null, ['f' => "C{$g}-C{$d}", 'v' => $net]], [XlsxExport::S_TEXT, XlsxExport::S_TEXT, XlsxExport::S_INT]);
        $h = $x->addRow(['HPP (biaya bahan)', null, ['f' => "D{$t}", 'v' => $pl['cogs']]], [XlsxExport::S_TEXT, XlsxExport::S_TEXT, XlsxExport::S_INT]);
        $p = $x->addRow(['Laba kotor', null, ['f' => "C{$n}-C{$h}", 'v' => $profit]], [XlsxExport::S_TOTAL_TEXT, XlsxExport::S_TOTAL_TEXT, XlsxExport::S_TOTAL_INT]);
        $x->addRow(['Margin laba kotor', null, ['f' => "IF(C{$n}>0,C{$p}/C{$n},0)", 'v' => $margin]], [XlsxExport::S_TEXT, XlsxExport::S_TEXT, XlsxExport::S_PCT]);

        $x->skipRow();
        $x->addRow(['Catatan: angka per menu sebelum diskon. Menu tanpa resep dihitung HPP Rp 0.'], XlsxExport::S_NOTE);

        return $x;
    }

    private function buildStock($rows, Carbon $from, Carbon $to): XlsxExport
    {
        $x = new XlsxExport('Stok');
        $x->setWidths([30, 12, 14, 14, 16, 14, 14]);
        $this->exportHead($x, 'Laporan Stok', $from, $to);

        $x->addRow(['Bahan', 'Satuan', 'Masuk', 'Keluar', 'Stok akhir', 'Minimum', 'Status'], XlsxExport::S_HEAD);
        foreach ($rows as $r) {
            $x->addRow([$r->name, $r->unit, (float) $r->in, (float) $r->out, (float) $r->stock_qty, (float) $r->min_stock, ucfirst($r->stock_status)],
                [XlsxExport::S_TEXT, XlsxExport::S_CENTER, XlsxExport::S_DEC, XlsxExport::S_DEC, XlsxExport::S_DEC, XlsxExport::S_DEC, XlsxExport::S_CENTER]);
        }
        if ($rows->isEmpty()) {
            $x->addRow(['Belum ada bahan baku.', null, null, null, null, null, null], XlsxExport::S_TEXT);
        }

        $x->skipRow();
        $x->addRow(['Catatan: Masuk/Keluar = pergerakan stok pada periode; Stok akhir = stok saat laporan dicetak.'], XlsxExport::S_NOTE);

        return $x;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kasir. Alur: simpan pesanan (pending) -> bayar (completed) -> dapur menyiapkan -> struk.
 * Total transaksi diisi trigger database dari transaction_details.
 */
class PosController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    /** Ringkasan shift: transaksi lunas hari ini milik kasir yang login. */
    public function dashboard(): View
    {
        $paidToday = Transaction::query()
            ->where('status', Transaction::STATUS_PAID)
            ->whereDate('transaction_date', today())
            ->where('user_id', Auth::id());

        $stats = [
            'count' => (clone $paidToday)->count(),
            'revenue' => (int) (clone $paidToday)->sum('total'),
            'cash' => (int) (clone $paidToday)->where('payment_method', 'cash')->sum('total'),
            'cashless' => (int) (clone $paidToday)->where('payment_method', '!=', 'cash')->sum('total'),
        ];

        $recent = (clone $paidToday)->with('details.menu')->latest('transaction_date')->latest('id')->take(5)->get()
            ->map(fn ($t) => $this->row($t));

        $pending = Transaction::where('status', Transaction::STATUS_PENDING)
            ->whereDate('transaction_date', today())->count();

        return view('kasir.dashboard', compact('stats', 'recent', 'pending'));
    }

    public function history(Request $request): View
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? today()->toDateString();

        $transactions = Transaction::query()
            ->with('details.menu')
            ->whereDate('transaction_date', $date)
            ->latest('id')
            ->get()
            ->map(fn ($t) => $this->row($t));

        return view('kasir.history', compact('transactions', 'date'));
    }

    private function row(Transaction $t): object
    {
        return (object) [
            'id' => $t->id,
            'number' => $t->number,
            'time' => $t->transaction_date?->format('H:i'),
            'table' => $t->table_no,
            'items' => $t->details->map(fn ($d) => $d->qty.'× '.($d->menu->name ?? 'Menu dihapus'))->implode(', '),
            'method' => $t->method_label,
            'total' => (int) $t->total,
            'status' => $t->status,
            'order_status' => $t->order_status,
        ];
    }

    public function index(): View
    {
        $categories = DB::table('categories')->orderBy('id')->get();

        $availability = $this->stock->availability();
        $menus = DB::table('menus')->where('status', 'active')->orderBy('category_id')->orderBy('name')->get();
        foreach ($menus as $menu) {
            $a = $availability[(int) $menu->id] ?? ['available' => true, 'portions' => null];
            $menu->available = $a['available'];
            $menu->portions = $a['portions'];
        }

        return view('kasir.pos', compact('categories', 'menus'));
    }

    /** Simpan pesanan berstatus pending. Harga selalu diambil dari database. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'table_no' => ['nullable', 'string', 'max:10'],
        ]);

        $qtyByMenu = collect($data['items'])->groupBy('menu_id')->map(fn ($rows) => (int) $rows->sum('qty'));

        $menus = DB::table('menus')->whereIn('id', $qtyByMenu->keys())->where('status', 'active')->get()->keyBy('id');

        if ($menus->count() !== $qtyByMenu->count()) {
            throw ValidationException::withMessages(['items' => 'Ada menu yang sudah tidak aktif. Muat ulang halaman kasir.']);
        }

        $availability = $this->stock->availability();
        foreach ($qtyByMenu as $menuId => $qty) {
            $a = $availability[(int) $menuId] ?? ['available' => true, 'portions' => null];
            if (! $a['available'] || ($a['portions'] !== null && $a['portions'] < $qty)) {
                throw ValidationException::withMessages([
                    'items' => "Stok bahan untuk \"{$menus[$menuId]->name}\" tidak cukup (tersisa ".($a['portions'] ?? 0).' porsi).',
                ]);
            }
        }

        $transaction = DB::transaction(function () use ($data, $qtyByMenu, $menus) {
            $t = Transaction::create([
                'user_id' => Auth::id(),
                'status' => Transaction::STATUS_PENDING,
                'order_status' => 'waiting',
                'table_no' => $data['table_no'] ?? null,
                'transaction_date' => now(),
            ]);

            foreach ($qtyByMenu as $menuId => $qty) {
                $t->details()->create([
                    'menu_id' => (int) $menuId,
                    'qty' => $qty,
                    'subtotal' => (int) $menus[$menuId]->price * $qty,
                ]);
            }

            return $t;
        });

        return redirect()->route('kasir.orders.pay', $transaction)
            ->with('success', "Pesanan {$transaction->number} tersimpan. Lanjut ke pembayaran.");
    }

    public function showPayment(Transaction $transaction): View|RedirectResponse
    {
        if ($transaction->status === Transaction::STATUS_PAID) {
            return redirect()->route('kasir.orders.receipt', $transaction);
        }
        if ($transaction->status === Transaction::STATUS_VOID) {
            return redirect()->route('kasir.pos')->withErrors(['order' => 'Pesanan ini sudah dibatalkan.']);
        }

        $transaction->load('details.menu');

        return view('kasir.pay', compact('transaction'));
    }

    /**
     * pending -> completed. Diskon (jika ada) disimpan lebih dulu: trigger menghitung ulang total.
     * UPDATE bersyarat (status = pending) mencegah bayar dua kali; semuanya satu transaksi DB.
     */
    public function pay(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,qris,debit'],
            'paid' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percent,amount'],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $type = $data['discount_type'] ?? 'none';
        $value = $type === 'none' ? 0 : (float) ($data['discount_value'] ?? 0);
        if ($type === 'percent' && $value > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Diskon persen maksimal 100.']);
        }

        $paid = DB::transaction(function () use ($transaction, $data, $type, $value) {
            $locked = Transaction::where('id', $transaction->id)->where('status', Transaction::STATUS_PENDING)->lockForUpdate()->first();
            if (! $locked) {
                throw ValidationException::withMessages(['order' => 'Pesanan ini sudah dibayar atau dibatalkan.']);
            }

            // Trigger fn_apply_discount menghitung discount_amount dan total.
            $locked->update(['discount_type' => $type, 'discount_value' => $value]);
            $total = (int) $locked->fresh()->total;
            $paid = $total;

            if ($data['payment_method'] === 'cash') {
                $paid = (int) ($data['paid'] ?? 0);
                if ($paid < $total) {
                    throw ValidationException::withMessages(['paid' => 'Uang diterima kurang dari total pembayaran.']);
                }
            }

            $locked->update(['status' => Transaction::STATUS_PAID, 'payment_method' => $data['payment_method']]);

            return $paid;
        });

        // Uang diterima/kembalian tidak disimpan di database, hanya ditampilkan di struk pertama.
        return redirect()->route('kasir.orders.receipt', $transaction)
            ->with('success', 'Pembayaran berhasil.')
            ->with('paid', $paid);
    }

    /** Batalkan: boleh selama belum "siap" di dapur / diantar. Aturan akhir dijaga trigger fn_guard_cancel. */
    public function void(Transaction $transaction): RedirectResponse
    {
        try {
            // Dibungkus transaksi (savepoint) agar error trigger tidak merusak transaksi luar.
            $updated = DB::transaction(fn () => Transaction::where('id', $transaction->id)
                ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PAID])
                ->update(['status' => Transaction::STATUS_VOID]));
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan sudah dikonfirmasi siap oleh dapur atau sudah diantar, tidak dapat dibatalkan.',
            ]);
        }

        if (! $updated) {
            throw ValidationException::withMessages(['order' => 'Pesanan ini sudah dibatalkan.']);
        }

        return redirect()->route('kasir.pos')->with('success', "Pesanan {$transaction->number} dibatalkan.");
    }

    public function receipt(Transaction $transaction): View|RedirectResponse
    {
        if ($transaction->status === Transaction::STATUS_PENDING) {
            return redirect()->route('kasir.orders.pay', $transaction);
        }
        if ($transaction->status === Transaction::STATUS_VOID) {
            return redirect()->route('kasir.pos')->withErrors(['order' => 'Pesanan ini sudah dibatalkan, tidak ada struk.']);
        }

        $transaction->load('details.menu', 'user');
        $total = (int) $transaction->total;
        $paid = session('paid');

        $trx = (object) [
            'number' => $transaction->number,
            'date' => $transaction->transaction_date?->format('d/m/Y H:i'),
            'cashier' => $transaction->user->name ?? '-',
            'table' => $transaction->table_no,
            'items' => $transaction->details->map(function ($d) {
                return (object) [
                    'name' => $d->menu->name ?? 'Menu dihapus',
                    'qty' => $d->qty,
                    'price' => $d->qty ? intdiv((int) $d->subtotal, $d->qty) : 0,
                    'subtotal' => (int) $d->subtotal,
                ];
            }),
            'subtotal' => (int) $transaction->subtotal,
            'discount' => (int) $transaction->discount_amount,
            'discount_label' => match ($transaction->discount_type) {
                'percent' => 'Diskon '.($transaction->discount_value + 0).'%',
                'amount' => 'Diskon',
                default => null,
            },
            'total' => $total,
            'method' => $transaction->method_label,
            'paid' => $paid,
            'change' => $paid !== null ? max(0, $paid - $total) : null,
        ];

        return view('kasir.receipt', compact('trx'));
    }
}

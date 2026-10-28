<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PosController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    /** Ringkasan shift kasir: transaksi lunas hari ini milik kasir yang login. */
    public function dashboard(): View
    {
        $paidToday = Transaction::query()
            ->where('status', Transaction::STATUS_PAID)
            ->whereDate('paid_at', today())
            ->when(Auth::check(), function ($q) {
    $q->where('user_id', Auth::id());
});

        $stats = [
            'count' => (clone $paidToday)->count(),
            'revenue' => (int) (clone $paidToday)->sum('total'),
            'cash' => (int) (clone $paidToday)->where('payment_method', 'cash')->sum('total'),
            'cashless' => (int) (clone $paidToday)->where('payment_method', '!=', 'cash')->sum('total'),
        ];

        $recent = (clone $paidToday)->with('details')->latest('paid_at')->latest('id')->take(4)->get()
            ->map(fn ($t) => $this->historyRow($t));

        return view('kasir.dashboard', compact('stats', 'recent'));
    }

    /** Riwayat transaksi pada satu tanggal (default hari ini). */
    public function history(Request $request): View
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? today()->toDateString();

        $transactions = Transaction::query()
            ->with('details')
            ->whereDate('created_at', $date)
            ->latest('id')
            ->get()
            ->map(fn ($t) => $this->historyRow($t));

        return view('kasir.history', compact('transactions', 'date'));
    }

    /** Bentuk data satu baris riwayat/ringkasan sesuai yang dipakai view. */
    private function historyRow(Transaction $t): object
    {
        $methodLabels = ['cash' => 'Tunai', 'qris' => 'QRIS', 'debit' => 'Debit'];

        return (object) [
            'id' => $t->id,
            'number' => $t->invoice_number,
            'time' => ($t->paid_at ?? $t->created_at)->format('H:i'),
            'items' => $t->details->map(fn ($d) => "{$d->qty}× {$d->menu_name}")->implode(', '),
            'method' => $t->payment_method ? ($methodLabels[$t->payment_method] ?? $t->payment_method) : '-',
            'total' => $t->total,
            'status' => $t->status,
        ];
    }

    /**
     * Halaman POS: daftar kategori + menu aktif.
     *
     * Membaca tabel `categories` dan `menus` langsung lewat query builder
     * (belum memakai model) karena model & migration-nya dibuat anggota lain.
     */
    public function index(): View
    {
        $categories = DB::table('categories')->orderBy('name')->get();

        $menus = DB::table('menus')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Menu tanpa resep / stok bahan kurang ditandai tidak tersedia ("Stok habis").
        $availability = $this->stock->availability($menus->pluck('id'));
        // foreach (bukan ->each) karena each() berhenti saat callback mengembalikan false.
        foreach ($menus as $menu) {
            $menu->available = $availability[(int) $menu->id] ?? false;
        }

        return view('kasir.pos', compact('categories', 'menus'));
    }

    /**
     * Simpan pesanan (status "pending"). Pembayaran dilakukan di langkah terpisah.
     *
     * Total SELALU dihitung ulang di sini dari harga di database,
     * angka dari browser tidak dipercaya.
     */
    public function store(Request $request): RedirectResponse
    {
        // Login/role dikerjakan anggota lain. Tanpa user login, transaksi tidak boleh disimpan.
        $userId = Auth::id();

        abort_if($userId === null, 403, 'Anda harus login sebagai kasir.');
        abort_if($userId === null, 403, 'Anda harus login sebagai kasir.');

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'discount_type' => ['required', 'in:none,percent,amount'],
            'discount_value' => ['nullable', 'integer', 'min:0'],
        ]);

        // Gabungkan menu yang sama (jika terkirim dua kali) menjadi satu baris.
        $qtyByMenu = collect($data['items'])
            ->groupBy('menu_id')
            ->map(fn ($rows) => (int) $rows->sum('qty'));

        // Ambil harga & nama dari database, hanya menu yang masih aktif.
        $menus = DB::table('menus')
            ->whereIn('id', $qtyByMenu->keys())
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        if ($menus->count() !== $qtyByMenu->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada menu yang sudah tidak tersedia. Muat ulang halaman kasir.',
            ]);
        }

        $lines = $qtyByMenu->map(function (int $qty, $menuId) use ($menus) {
            $menu = $menus[$menuId];
            $price = (int) $menu->price;

            return [
                'menu_id' => (int) $menuId,
                'menu_name' => $menu->name,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $price * $qty,
            ];
        })->values();

        $subtotal = (int) $lines->sum('subtotal');

        $discountType = $data['discount_type'];
        $discountValue = $discountType === 'none' ? 0 : (int) ($data['discount_value'] ?? 0);

        $discountAmount = match ($discountType) {
            'percent' => (int) round($subtotal * min($discountValue, 100) / 100),
            'amount' => min($discountValue, $subtotal),
            default => 0,
        };

        $total = $subtotal - $discountAmount;

        $transaction = DB::transaction(function () use ($userId, $lines, $subtotal, $discountType, $discountValue, $discountAmount, $total) {
            // Nomor invoice sementara (harus unik), lalu diganti setelah id diketahui.
            $transaction = Transaction::create([
                'invoice_number' => 'TMP-'.Str::uuid(),
                'user_id' => $userId,
                'status' => Transaction::STATUS_PENDING,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);

            $transaction->update([
                'invoice_number' => 'INV-'.str_pad((string) $transaction->id, 4, '0', STR_PAD_LEFT),
            ]);

            $transaction->details()->createMany($lines->all());

            // Kurangi stok di dalam transaksi database yang sama. Jika menu tanpa resep
            // atau stok kurang, StockService melempar ValidationException dan SELURUH
            // penyimpanan (transaksi + detail + stok) dibatalkan.
            $this->stock->deduct($transaction->load('details'));

            return $transaction;
        });

        return redirect()
            ->route('kasir.orders.pay', $transaction)
            ->with('success', "Pesanan {$transaction->invoice_number} tersimpan. Silakan lanjut ke pembayaran.");
    }

    /** Halaman pembayaran untuk pesanan yang masih "pending". */
    public function showPayment(Transaction $transaction): View|RedirectResponse
    {
        if ($transaction->status === Transaction::STATUS_PAID) {
            return redirect()->route('kasir.orders.receipt', $transaction);
        }

        if ($transaction->status === Transaction::STATUS_VOID) {
            return redirect()->route('kasir.pos')->withErrors(['order' => 'Pesanan ini sudah dibatalkan.']);
        }

        $transaction->load('details');

        return view('kasir.pay', compact('transaction'));
    }

    /**
     * Proses pembayaran: pending -> paid. Setelah paid, transaksi terkunci.
     */
    public function pay(Request $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,qris,debit'],
            'paid' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($data['payment_method'] === 'cash') {
            $paid = $data['paid'] ?? null;

            if ($paid === null || $paid < $transaction->total) {
                throw ValidationException::withMessages([
                    'paid' => 'Uang diterima kurang dari total pembayaran.',
                ]);
            }

            $change = $paid - $transaction->total;
        } else {
            // QRIS / debit: dianggap dibayar pas.
            $paid = $transaction->total;
            $change = 0;
        }

        // UPDATE bersyarat: hanya berhasil bila status MASIH pending.
        // Jika sudah dibayar/dibatalkan (misalnya dari tab lain), jumlah baris yang
        // berubah = 0 dan pembayaran ditolak. Ini mencegah bayar dua kali.
        $updated = Transaction::where('id', $transaction->id)
            ->where('status', Transaction::STATUS_PENDING)
            ->update([
                'status' => Transaction::STATUS_PAID,
                'payment_method' => $data['payment_method'],
                'paid_amount' => $paid,
                'change_amount' => $change,
                'paid_at' => now(),
            ]);

        if (! $updated) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan ini sudah dibayar atau dibatalkan.',
            ]);
        }

        return redirect()
            ->route('kasir.orders.receipt', $transaction)
            ->with('success', 'Pembayaran berhasil.');
    }

    /**
     * Batalkan pesanan: HANYA dari "pending" (belum dibayar). Pesanan lunas tidak bisa dibatalkan.
     */
    public function void(Transaction $transaction): RedirectResponse
    {
        $updated = 0;

        DB::transaction(function () use ($transaction, &$updated) {
            $updated = Transaction::where('id', $transaction->id)
                ->where('status', Transaction::STATUS_PENDING)
                ->update([
                    'status' => Transaction::STATUS_VOID,
                    'voided_at' => now(),
                ]);

            if ($updated) {
                // Kembalikan stok yang dulu dipotong (dalam transaksi database yang sama).
                $this->stock->restore($transaction);
            }
        });

        if (! $updated) {
            throw ValidationException::withMessages([
                'order' => 'Pesanan tidak bisa dibatalkan karena sudah dibayar atau dibatalkan.',
            ]);
        }

        return redirect()
            ->route('kasir.pos')
            ->with('success', "Pesanan {$transaction->invoice_number} dibatalkan.");
    }

    /** Struk: data diambil dari transaksi yang sudah tersimpan & lunas. */
    public function receipt(Transaction $transaction): View|RedirectResponse
    {
        if ($transaction->status === Transaction::STATUS_PENDING) {
            return redirect()->route('kasir.orders.pay', $transaction);
        }

        if ($transaction->status === Transaction::STATUS_VOID) {
            return redirect()->route('kasir.pos')->withErrors(['order' => 'Pesanan ini sudah dibatalkan, tidak ada struk.']);
        }

        $transaction->load('details', 'user');

        $methodLabels = ['cash' => 'Tunai', 'qris' => 'QRIS', 'debit' => 'Debit'];

        // Bentuk objek disesuaikan dengan yang dipakai view kasir/receipt.blade.php
        $trx = (object) [
            'number' => $transaction->invoice_number,
            'date' => $transaction->paid_at->format('d/m/Y H:i'),
            'cashier' => $transaction->user->name,
            'items' => $transaction->details->map(fn ($d) => (object) [
                'name' => $d->menu_name,
                'qty' => $d->qty,
                'price' => $d->price,
            ]),
            'subtotal' => $transaction->subtotal,
            'discount' => $transaction->discount_amount,
            'total' => $transaction->total,
            'method' => $methodLabels[$transaction->payment_method] ?? $transaction->payment_method,
            'paid' => $transaction->paid_amount,
            'change' => $transaction->change_amount,
        ];

        return view('kasir.receipt', compact('trx'));
    }
}

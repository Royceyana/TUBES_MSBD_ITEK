<?php

namespace Database\Seeders;

use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleTransactionSeeder extends Seeder
{
    /** Jumlah hari ke belakang yang diisi transaksi contoh. */
    public const DAYS = 14;

    /** Kategori makanan (Nasi, Mie, Snack); sisanya dianggap minuman. */
    private const FOOD_CATEGORIES = [1, 2, 3];

    private int $dapurId;

    private bool $canBackdate = false;

    /** @var array<int, float> menu_id => harga */
    private array $prices = [];

    /**
     * Pesanan contoh yang dijalankan lewat alur asli (buat pesanan -> bayar -> dapur -> diantar),
     * sehingga subtotal, diskon, total, dan pemotongan stok dihitung oleh trigger database.
     * Hanya berjalan bila tabel transactions masih kosong. Hasilnya acak tetapi tetap sama tiap kali
     * dijalankan (seed tetap), dan hanya memilih menu yang stoknya cukup.
     */
    public function run(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->command?->warn('SampleTransactionSeeder dilewati: trigger hanya ada di PostgreSQL.');

            return;
        }

        if (DB::table('transactions')->exists()) {
            $this->command?->info('Transaksi sudah ada, data contoh dilewati.');

            return;
        }

        $kasirId = DB::table('users')->where('role', 'kasir')->orderBy('id')->value('id');
        $waitressId = DB::table('users')->where('role', 'waitress')->orderBy('id')->value('id');
        $this->dapurId = (int) DB::table('users')->where('role', 'dapur')->orderBy('id')->value('id');

        if (! $kasirId || ! $waitressId || ! $this->dapurId) {
            $this->command?->warn('Akun kasir/waitress/dapur belum ada; jalankan UserSeeder dulu.');

            return;
        }

        $menus = DB::table('menus')->where('status', 'active')->get(['id', 'category_id', 'price']);
        foreach ($menus as $m) {
            $this->prices[$m->id] = (float) $m->price;
        }
        $food = $menus->filter(fn ($m) => in_array($m->category_id, self::FOOD_CATEGORIES))->pluck('id')->all();
        $drink = $menus->reject(fn ($m) => in_array($m->category_id, self::FOOD_CATEGORIES))->pluck('id')->all();

        $count = DB::transaction(function () use ($kasirId, $waitressId, $food, $drink) {
            // stock_movements append-only (trigger trg_sm_immutable). Agar riwayat pemakaian bisa diberi
            // tanggal sesuai pesanan, trigger dimatikan sementara selama seeding. Itu hanya bisa dilakukan
            // pemilik tabel (php artisan db:seed --database=pgsql_admin); tanpa itu tanggalnya = hari ini.
            $this->canBackdate = $this->unlockStockLedger();
            $count = $this->generate($kasirId, $waitressId, $food, $drink);
            if ($this->canBackdate) {
                $this->lockStockLedger();
            }

            return $count;
        });

        $this->command?->info("{$count} transaksi contoh dibuat.");
    }

    private function unlockStockLedger(): bool
    {
        try {
            // Savepoint: bila ditolak (bukan pemilik tabel), transaksi seeding tidak ikut gagal.
            DB::transaction(fn () => DB::unprepared('ALTER TABLE stock_movements DISABLE TRIGGER trg_sm_immutable'));

            return true;
        } catch (\Throwable) {
            $this->command?->warn('Trigger trg_sm_immutable tidak bisa dimatikan (bukan pemilik tabel); tanggal pemakaian stok = hari ini.');

            return false;
        }
    }

    private function lockStockLedger(): void
    {
        DB::unprepared('ALTER TABLE stock_movements ENABLE TRIGGER trg_sm_immutable');
    }

    /** @return int jumlah transaksi yang dibuat */
    private function generate(int $kasirId, int $waitressId, array $food, array $drink): int
    {
        mt_srand(13112026);
        $count = 0;

        // 1) Riwayat: hari-hari sebelumnya, semuanya sudah selesai (atau dibatalkan sebagian kecil).
        for ($d = self::DAYS; $d >= 1; $d--) {
            $day = now()->subDays($d)->startOfDay();
            $perDay = mt_rand(4, 8) + ($day->isWeekend() ? 3 : 0);

            $minutes = [];
            for ($i = 0; $i < $perDay; $i++) {
                $minutes[] = mt_rand(10 * 60, 21 * 60 + 30);
            }
            sort($minutes);

            foreach ($minutes as $min) {
                $cancelled = mt_rand(1, 100) <= 7;
                $this->order([
                    'at' => $day->copy()->addMinutes($min),
                    'user_id' => mt_rand(0, 1) ? $waitressId : $kasirId,
                    'items' => $this->pickItems($food, $drink),
                    'progress' => $cancelled ? 'waiting' : 'served',
                    'paid' => ! $cancelled,
                    'cancel' => $cancelled,
                ]);
                $count++;
            }
        }

        // 2) Hari ini: satu pesanan di setiap tahap supaya antrian dapur, daftar "Siap diantar",
        //    dan "Pesanan belum dibayar" tidak kosong saat didemokan.
        $today = [
            ['m' => 3,   'by' => $waitressId, 'progress' => 'waiting',   'paid' => false],
            ['m' => 8,   'by' => $waitressId, 'progress' => 'waiting',   'paid' => false],
            ['m' => 15,  'by' => $kasirId,    'progress' => 'preparing', 'paid' => true],
            ['m' => 22,  'by' => $waitressId, 'progress' => 'ready',     'paid' => false],
            ['m' => 30,  'by' => $kasirId,    'progress' => 'ready',     'paid' => true],
            ['m' => 45,  'by' => $waitressId, 'progress' => 'served',    'paid' => false],
            ['m' => 75,  'by' => $kasirId,    'progress' => 'served',    'paid' => true],
            ['m' => 120, 'by' => $kasirId,    'progress' => 'served',    'paid' => true, 'discount' => ['percent', 10]],
            ['m' => 90,  'by' => $waitressId, 'progress' => 'waiting',   'paid' => false, 'cancel' => true],
        ];

        foreach ($today as $t) {
            $this->order([
                'at' => now()->subMinutes($t['m']),
                'user_id' => $t['by'],
                'items' => $this->pickItems($food, $drink),
                'progress' => $t['progress'],
                'paid' => $t['paid'],
                'cancel' => $t['cancel'] ?? false,
                'discount' => $t['discount'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * @param array{at: CarbonInterface, user_id: int, items: array<int,int>, progress: string, paid: bool,
     *              cancel?: bool, discount?: array{0:string,1:int}|null} $o
     */
    private function order(array $o): void
    {
        if ($o['items'] === []) {
            return;
        }

        $discount = $o['discount'] ?? (mt_rand(1, 100) <= 10 ? (mt_rand(0, 1) ? ['percent', 10] : ['amount', 5000]) : null);
        $method = ['cash', 'cash', 'cash', 'qris', 'qris', 'debit'][mt_rand(0, 5)];
        $payFirst = mt_rand(0, 1) === 1;

        DB::transaction(function () use ($o, $discount, $method, $payFirst) {
            // Dibaca trigger pemotongan stok agar pergerakan stok tercatat atas nama dapur.
            DB::select("select set_config('app.user_id', ?, true)", [(string) $this->dapurId]);

            $dineIn = mt_rand(1, 100) <= 85;
            $id = DB::table('transactions')->insertGetId([
                'user_id' => $o['user_id'],
                'transaction_date' => $o['at']->format('Y-m-d H:i:s'),
                'status' => 'pending',
                'order_status' => 'waiting',
                'order_type' => $dineIn ? 'dine_in' : 'takeaway',
                'table_no' => $dineIn ? (string) mt_rand(1, 12) : null,
                'discount_type' => $discount[0] ?? 'none',
                'discount_value' => $discount[1] ?? 0,
            ]);

            // subtotal, discount_amount, dan total diisi trigger setiap detail masuk.
            foreach ($o['items'] as $menuId => $qty) {
                DB::table('transaction_details')->insert([
                    'transaction_id' => $id, 'menu_id' => $menuId, 'qty' => $qty,
                    'subtotal' => $this->prices[$menuId] * $qty,
                ]);
            }

            $pay = fn () => DB::table('transactions')->where('id', $id)
                ->update(['status' => 'completed', 'payment_method' => $method]);

            if ($o['cancel'] ?? false) {
                DB::table('transactions')->where('id', $id)->update(['status' => 'cancelled']);

                return;
            }

            if ($o['paid'] && $payFirst) {
                $pay();
            }

            foreach (['preparing', 'ready', 'served'] as $step) {
                if ($o['progress'] === 'waiting') {
                    break;
                }

                DB::table('transactions')->where('id', $id)->update(['order_status' => $step]
                    + ($step === 'served' ? [
                        'served_at' => $o['at']->copy()->addMinutes(35)->format('Y-m-d H:i:s'),
                        'served_by' => DB::table('users')->where('role', 'waitress')->orderBy('id')->value('id'),
                    ] : []));

                if ($step === 'ready' && $this->canBackdate) {
                    // Pemakaian bahan dicatat trigger dengan waktu "sekarang"; selaraskan dengan tanggal pesanan.
                    DB::table('stock_movements')->where('transaction_id', $id)
                        ->update(['created_at' => $o['at']->copy()->addMinutes(20)->format('Y-m-d H:i:s')]);
                }

                if ($step === $o['progress']) {
                    break;
                }
            }

            if ($o['paid'] && ! $payFirst) {
                $pay();
            }
        });
    }

    /**
     * 1-2 makanan dan 1-2 minuman (acak), hanya dari menu yang stoknya cukup saat ini.
     *
     * @return array<int, int> menu_id => qty
     */
    private function pickItems(array $food, array $drink): array
    {
        $portions = DB::table('v_menu_availability')->where('is_available', true)
            ->get(['menu_id', 'porsi_tersedia', 'jumlah_bahan'])
            ->mapWithKeys(fn ($r) => [$r->menu_id => $r->jumlah_bahan > 0 ? (int) $r->porsi_tersedia : PHP_INT_MAX])
            ->all();

        $items = [];
        $wantFood = mt_rand(1, 100) <= 85 ? mt_rand(1, 2) : 0;
        $wantDrink = ($wantFood === 0 || mt_rand(1, 100) <= 75) ? mt_rand(1, 2) : 0;

        foreach ([[$food, $wantFood], [$drink, $wantDrink]] as [$pool, $want]) {
            $pool = array_values(array_filter($pool, fn ($id) => isset($portions[$id]) && ! isset($items[$id])));
            for ($i = 0; $i < $want && $pool !== []; $i++) {
                $k = mt_rand(0, count($pool) - 1);
                $menuId = $pool[$k];
                array_splice($pool, $k, 1);

                $roll = mt_rand(1, 100);
                $qty = $roll <= 60 ? 1 : ($roll <= 90 ? 2 : 3);
                $items[$menuId] = max(1, min($qty, $portions[$menuId]));
            }
        }

        return $items;
    }
}

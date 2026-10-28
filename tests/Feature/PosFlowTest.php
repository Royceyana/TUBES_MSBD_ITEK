<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Menguji alur POS: simpan pesanan -> bayar -> struk, batal, dan integrasi stok.
 *
 * Tes memakai database SQLite di memori (lihat phpunit.xml), jadi TIDAK menyentuh
 * database asli. Tabel menu/bahan/resep/stok dibuat ulang di sini sebagai data uji
 * (sesuai nama kolom di README-UI), supaya tes tetap jalan walau migration milik
 * anggota lain belum ada.
 *
 * Jalankan: php artisan test --filter=PosFlowTest
 */
class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->makeFixtureTables();

        $this->kasir = User::factory()->create();
        $this->actingAs($this->kasir);
    }

    private function makeFixtureTables(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['stock_movements', 'menu_ingredients', 'menus', 'ingredients', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('categories', function ($t) {
            $t->id();
            $t->string('name');
        });
        Schema::create('menus', function ($t) {
            $t->id();
            $t->unsignedBigInteger('category_id');
            $t->string('name');
            $t->unsignedBigInteger('price');
            $t->string('image')->nullable();
            $t->string('status')->default('active');
        });
        Schema::create('ingredients', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('unit');
            $t->unsignedBigInteger('stock_qty');
            $t->unsignedBigInteger('min_stock')->default(0);
        });
        Schema::create('menu_ingredients', function ($t) {
            $t->id();
            $t->unsignedBigInteger('menu_id');
            $t->unsignedBigInteger('ingredient_id');
            $t->unsignedBigInteger('qty_needed');
            $t->string('unit')->nullable();
        });
        Schema::create('stock_movements', function ($t) {
            $t->id();
            $t->unsignedBigInteger('ingredient_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('type');
            $t->unsignedBigInteger('qty');
            $t->string('notes')->nullable();
            $t->timestamps();
        });

        DB::table('categories')->insert(['id' => 1, 'name' => 'Kopi']);
        DB::table('menus')->insert([
            ['id' => 1, 'category_id' => 1, 'name' => 'Kopi Susu Kaleng', 'price' => 15000, 'status' => 'active'],
            ['id' => 2, 'category_id' => 1, 'name' => 'Americano', 'price' => 12000, 'status' => 'active'],
            ['id' => 3, 'category_id' => 1, 'name' => 'Roti Bakar (tanpa resep)', 'price' => 12000, 'status' => 'active'],
            ['id' => 4, 'category_id' => 1, 'name' => 'Kentang (nonaktif)', 'price' => 10000, 'status' => 'inactive'],
        ]);
        DB::table('ingredients')->insert([
            ['id' => 1, 'name' => 'Biji kopi', 'unit' => 'gram', 'stock_qty' => 1000],
            ['id' => 2, 'name' => 'Susu cair', 'unit' => 'ml', 'stock_qty' => 1000],
        ]);
        DB::table('menu_ingredients')->insert([
            ['menu_id' => 1, 'ingredient_id' => 1, 'qty_needed' => 18],
            ['menu_id' => 1, 'ingredient_id' => 2, 'qty_needed' => 120],
            ['menu_id' => 2, 'ingredient_id' => 1, 'qty_needed' => 18],
        ]);
    }

    private function stock(int $ingredientId): int
    {
        return (int) DB::table('ingredients')->where('id', $ingredientId)->value('stock_qty');
    }

    private function order(array $items, string $discountType = 'none', ?int $discountValue = null)
    {
        return $this->post(route('kasir.orders.store'), [
            'items' => $items,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
        ]);
    }

    public function test_pos_page_lists_active_menus_and_marks_menu_without_recipe_unavailable(): void
    {
        $response = $this->get(route('kasir.pos'));

        $response->assertOk();
        $response->assertSee('Kopi Susu Kaleng');
        $response->assertDontSee('Kentang (nonaktif)');
        $response->assertSee('Stok habis'); // Roti Bakar tidak punya resep
    }

    public function test_store_saves_pending_order_with_server_side_totals_and_deducts_stock(): void
    {
        $response = $this->order([['menu_id' => 1, 'qty' => 2], ['menu_id' => 2, 'qty' => 1]]);

        $trx = Transaction::firstOrFail();
        $response->assertRedirect(route('kasir.orders.pay', $trx));

        $this->assertSame('pending', $trx->status);
        $this->assertSame($this->kasir->id, $trx->user_id);
        $this->assertSame(42000, (int) $trx->subtotal); // 2x15000 + 12000
        $this->assertSame(42000, (int) $trx->total);
        $this->assertMatchesRegularExpression('/^INV-\d{4}$/', $trx->invoice_number);
        $this->assertCount(2, $trx->details);

        // Kopi: 2x18 + 1x18 = 54 ; Susu: 2x120 = 240
        $this->assertSame(1000 - 54, $this->stock(1));
        $this->assertSame(1000 - 240, $this->stock(2));
        $this->assertSame(2, DB::table('stock_movements')->where('type', 'out')->count());
    }

    public function test_discount_is_calculated_on_server(): void
    {
        $this->order([['menu_id' => 1, 'qty' => 2]], 'percent', 10);
        $this->assertSame(27000, (int) Transaction::latest('id')->first()->total);

        $this->order([['menu_id' => 2, 'qty' => 1]], 'amount', 99999); // dibatasi sebesar subtotal
        $this->assertSame(0, (int) Transaction::latest('id')->first()->total);
    }

    public function test_menu_without_recipe_cannot_be_sold(): void
    {
        $response = $this->order([['menu_id' => 3, 'qty' => 1]]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Transaction::count());
    }

    public function test_inactive_or_unknown_menu_is_rejected(): void
    {
        $this->order([['menu_id' => 4, 'qty' => 1]])->assertSessionHasErrors('items');
        $this->order([['menu_id' => 999, 'qty' => 1]])->assertSessionHasErrors('items');
        $this->assertSame(0, Transaction::count());
    }

    public function test_insufficient_stock_rolls_back_everything(): void
    {
        DB::table('ingredients')->where('id', 2)->update(['stock_qty' => 100]); // susu kurang (butuh 120)

        // Americano (kopi) dipotong dulu, lalu Kopi Susu gagal di susu -> semuanya harus batal.
        $response = $this->order([['menu_id' => 2, 'qty' => 1], ['menu_id' => 1, 'qty' => 1]]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, DB::table('transaction_details')->count());
        $this->assertSame(1000, $this->stock(1));
        $this->assertSame(100, $this->stock(2));
        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->order([])->assertSessionHasErrors('items');
        $this->order([['menu_id' => 1, 'qty' => 0]])->assertSessionHasErrors('items.0.qty');
        $this->order([['menu_id' => 1, 'qty' => 1]], 'bogus')->assertSessionHasErrors('discount_type');
        $this->assertSame(0, Transaction::count());
    }

    public function test_store_requires_login(): void
    {
        $this->app['auth']->logout();

        $this->order([['menu_id' => 1, 'qty' => 1]])->assertForbidden();
        $this->assertSame(0, Transaction::count());
    }

    public function test_cash_payment_calculates_change_and_shows_receipt(): void
    {
        $this->order([['menu_id' => 1, 'qty' => 2]]); // 30000
        $trx = Transaction::firstOrFail();

        $this->get(route('kasir.orders.pay', $trx))->assertOk()->assertSee($trx->invoice_number);

        $this->post(route('kasir.orders.pay.store', $trx), ['payment_method' => 'cash', 'paid' => 50000])
            ->assertRedirect(route('kasir.orders.receipt', $trx));

        $trx->refresh();
        $this->assertSame('paid', $trx->status);
        $this->assertSame(50000, (int) $trx->paid_amount);
        $this->assertSame(20000, (int) $trx->change_amount);
        $this->assertNotNull($trx->paid_at);

        $this->get(route('kasir.orders.receipt', $trx))
            ->assertOk()
            ->assertSee($trx->invoice_number)
            ->assertSee('Kopi Susu Kaleng')
            ->assertSee('Tunai');
    }

    public function test_cash_payment_below_total_is_rejected(): void
    {
        $this->order([['menu_id' => 1, 'qty' => 2]]);
        $trx = Transaction::firstOrFail();

        $this->post(route('kasir.orders.pay.store', $trx), ['payment_method' => 'cash', 'paid' => 10000])
            ->assertSessionHasErrors('paid');

        $this->assertSame('pending', $trx->refresh()->status);
    }

    public function test_qris_payment_is_exact_and_cannot_be_paid_twice(): void
    {
        $this->order([['menu_id' => 2, 'qty' => 1]]);
        $trx = Transaction::firstOrFail();

        $this->post(route('kasir.orders.pay.store', $trx), ['payment_method' => 'qris']);
        $trx->refresh();
        $this->assertSame('paid', $trx->status);
        $this->assertSame((int) $trx->total, (int) $trx->paid_amount);
        $this->assertSame(0, (int) $trx->change_amount);

        $this->post(route('kasir.orders.pay.store', $trx), ['payment_method' => 'cash', 'paid' => 99999])
            ->assertSessionHasErrors('order');
        $this->assertSame('qris', $trx->refresh()->payment_method);
    }

    public function test_void_pending_order_restores_stock(): void
    {
        $this->order([['menu_id' => 1, 'qty' => 2]]);
        $trx = Transaction::firstOrFail();
        $this->assertSame(1000 - 36, $this->stock(1));

        $this->post(route('kasir.orders.void', $trx))->assertRedirect(route('kasir.pos'));

        $this->assertSame('void', $trx->refresh()->status);
        $this->assertNotNull($trx->voided_at);
        $this->assertSame(1000, $this->stock(1));
        $this->assertSame(1000, $this->stock(2));
        $this->assertSame(2, DB::table('stock_movements')->where('type', 'in')->count());

        // Batal dua kali tidak boleh mengembalikan stok dua kali.
        $this->post(route('kasir.orders.void', $trx))->assertSessionHasErrors('order');
        $this->assertSame(1000, $this->stock(1));
    }

    public function test_paid_order_cannot_be_voided(): void
    {
        $this->order([['menu_id' => 1, 'qty' => 1]]);
        $trx = Transaction::firstOrFail();
        $this->post(route('kasir.orders.pay.store', $trx), ['payment_method' => 'qris']);

        $this->post(route('kasir.orders.void', $trx))->assertSessionHasErrors('order');

        $this->assertSame('paid', $trx->refresh()->status);
        $this->assertSame(1000 - 18, $this->stock(1)); // stok tetap terpotong
    }

    public function test_voided_order_cannot_be_paid(): void
    {
        $this->order([['menu_id' => 2, 'qty' => 1]]);
        $trx = Transaction::firstOrFail();
        $this->post(route('kasir.orders.void', $trx));

        $this->post(route('kasir.orders.pay.store', $trx), ['payment_method' => 'qris'])
            ->assertSessionHasErrors('order');
        $this->assertSame('void', $trx->refresh()->status);
    }

    public function test_history_and_dashboard_show_real_data(): void
    {
        $this->order([['menu_id' => 1, 'qty' => 2]]); // 30000, akan dibayar tunai
        $paid = Transaction::firstOrFail();
        $this->post(route('kasir.orders.pay.store', $paid), ['payment_method' => 'cash', 'paid' => 30000]);

        $this->order([['menu_id' => 2, 'qty' => 1]]); // 12000, dibiarkan pending
        $pending = Transaction::latest('id')->first();

        $this->get(route('kasir.history'))
            ->assertOk()
            ->assertSee($paid->invoice_number)
            ->assertSee($pending->invoice_number)
            ->assertSee('Lunas')
            ->assertSee('Belum bayar');

        $this->get(route('kasir.dashboard'))->assertOk();
    }
}

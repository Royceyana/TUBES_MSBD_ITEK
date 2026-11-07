<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Berjalan di database hasil impor kafe_kaleng.sql (trigger & view ikut diuji).
 * DatabaseTransactions membatalkan semua perubahan setelah tiap test.
 */
class PosFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Test', 'email' => $role.'-'.uniqid().'@test.local',
            'password' => password_hash('rahasia123', PASSWORD_BCRYPT), 'role' => $role, 'status' => 'active',
        ]);
    }

    /** Menu aktif pertama beserta stok bahan resepnya diisi lewat stock_movements (trigger menambah stock_qty). */
    private function stockedMenu(): object
    {
        $menu = DB::table('menus')->where('status', 'active')->orderBy('id')->first();
        foreach (DB::table('menu_ingredients')->where('menu_id', $menu->id)->get() as $r) {
            DB::table('stock_movements')->insert(['ingredient_id' => $r->ingredient_id, 'type' => 'in', 'qty' => $r->qty_needed * 20, 'notes' => 'test']);
        }

        return $menu;
    }

    public function test_tamu_diarahkan_ke_login_dan_role_dibatasi(): void
    {
        $this->get('/kasir/pos')->assertRedirect('/login');
        $this->actingAs($this->user('kasir'))->get('/admin/menus')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/admin/menus')->assertOk();
    }

    public function test_login_memakai_hash_bcrypt_2b_dari_sql(): void
    {
        $u = $this->user('kasir');
        DB::table('users')->where('id', $u->id)->update(['password' => '$2b'.substr(password_hash('rahasia123', PASSWORD_BCRYPT), 3)]);

        $this->post('/login', ['email' => $u->email, 'password' => 'rahasia123'])->assertRedirect(route('kasir.dashboard'));
        $this->assertAuthenticatedAs($u->fresh());
    }

    public function test_alur_pesan_bayar_masak_memotong_stok_lewat_trigger(): void
    {
        $kasir = $this->user('kasir');
        $menu = $this->stockedMenu();

        $this->actingAs($kasir)->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 2]], 'table_no' => '4']);
        $trx = Transaction::latest('id')->first();
        $this->assertSame('pending', $trx->status);
        $this->assertEquals($menu->price * 2, $trx->total);            // total dihitung trigger

        $this->post("/kasir/orders/{$trx->id}/pay", ['payment_method' => 'cash', 'paid' => $menu->price * 2 + 5000])
            ->assertRedirect(route('kasir.orders.receipt', $trx));
        $this->assertSame('completed', $trx->fresh()->status);

        // Bayar dua kali ditolak
        $this->post("/kasir/orders/{$trx->id}/pay", ['payment_method' => 'qris'])->assertSessionHasErrors('order');

        // Dapur: waiting -> preparing -> ready, stok keluar tercatat oleh trigger
        $dapur = $this->user('dapur');
        $out = fn () => DB::table('stock_movements')->where('transaction_id', $trx->id)->where('type', 'out')->count();
        $this->actingAs($dapur)->post("/dapur/orders/{$trx->id}/advance");
        $this->assertSame(0, $out());
        $this->post("/dapur/orders/{$trx->id}/advance");
        $this->assertSame('ready', $trx->fresh()->order_status);
        $this->assertSame(DB::table('menu_ingredients')->where('menu_id', $menu->id)->count(), $out());

        // Setelah siap, pesanan tidak boleh dibatalkan (trigger fn_guard_cancel)
        $this->actingAs($kasir)->post("/kasir/orders/{$trx->id}/void")->assertSessionHasErrors('order');
        $this->assertSame('completed', $trx->fresh()->status);

        // Pelayan mengantar: ready -> served; tidak bisa dua kali
        $pelayan = $this->user('waitress');
        $this->actingAs($pelayan)->post("/pelayan/orders/{$trx->id}/serve");
        $this->assertSame('served', $trx->fresh()->order_status);
        $this->post("/pelayan/orders/{$trx->id}/serve")->assertSessionHasErrors('order');
    }

    public function test_diskon_persen_dihitung_trigger_dan_debit_diterima(): void
    {
        $kasir = $this->user('kasir');
        $menu = $this->stockedMenu();

        $this->actingAs($kasir)->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 2]]]);
        $trx = Transaction::latest('id')->first();
        $subtotal = (int) $menu->price * 2;

        $this->post("/kasir/orders/{$trx->id}/pay", ['payment_method' => 'debit', 'discount_type' => 'percent', 'discount_value' => 10])
            ->assertRedirect(route('kasir.orders.receipt', $trx));

        $trx->refresh();
        $this->assertSame('completed', $trx->status);
        $this->assertSame('debit', $trx->payment_method);
        $this->assertSame($subtotal, $trx->subtotal);
        $this->assertSame((int) round($subtotal * 0.10), $trx->discount_amount);
        $this->assertSame($subtotal - $trx->discount_amount, $trx->total);
    }

    public function test_tunai_kurang_dari_total_setelah_diskon_ditolak_dan_diskon_tidak_tersimpan(): void
    {
        $kasir = $this->user('kasir');
        $menu = $this->stockedMenu();

        $this->actingAs($kasir)->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 1]]]);
        $trx = Transaction::latest('id')->first();

        $this->post("/kasir/orders/{$trx->id}/pay", ['payment_method' => 'cash', 'paid' => 1, 'discount_type' => 'amount', 'discount_value' => 1000])
            ->assertSessionHasErrors('paid');

        $trx->refresh();
        $this->assertSame('pending', $trx->status);
        $this->assertSame('none', $trx->discount_type);              // transaksi di-rollback
    }

    public function test_pelayan_tidak_bisa_mengantar_pesanan_yang_belum_siap(): void
    {
        $kasir = $this->user('kasir');
        $menu = $this->stockedMenu();
        $this->actingAs($kasir)->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 1]]]);
        $trx = Transaction::latest('id')->first();
        $this->post("/kasir/orders/{$trx->id}/pay", ['payment_method' => 'qris']);

        $this->actingAs($this->user('waitress'))->post("/pelayan/orders/{$trx->id}/serve")->assertSessionHasErrors('order');
        $this->assertSame('waiting', $trx->fresh()->order_status);
        $this->actingAs($this->user('kasir'))->get('/pelayan/board')->assertForbidden();
    }

    public function test_pesanan_belum_bayar_bisa_dibatalkan_dan_stok_tidak_cukup_ditolak(): void
    {
        $kasir = $this->user('kasir');
        $menu = $this->stockedMenu();

        $this->actingAs($kasir)->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 1]]]);
        $trx = Transaction::latest('id')->first();
        $this->post("/kasir/orders/{$trx->id}/void");
        $this->assertSame('cancelled', $trx->fresh()->status);

        $this->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 99]]])->assertSessionHasErrors('items');
    }

    public function test_halaman_utama_menampilkan_menu_dari_database(): void
    {
        $name = DB::table('menus')->where('status', 'active')->value('name');
        $this->get('/')->assertOk()->assertSee($name);
    }
}

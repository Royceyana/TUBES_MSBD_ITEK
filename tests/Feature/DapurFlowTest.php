<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Dapur: peringatan stok kurang, stok bahan, dan resep. Memakai database hasil impor SQL. */
class DapurFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Test', 'email' => $role.'-'.uniqid().'@test.local',
            'password' => password_hash('rahasia123', PASSWORD_BCRYPT), 'role' => $role, 'status' => 'active',
        ]);
    }

    /** Menu aktif ber-resep, stok bahannya dikosongkan lewat opname, lalu satu pesanan dibayar dan siap dimasak. */
    private function preparingOrderWithoutStock(): Transaction
    {
        $menu = DB::table('menus as m')->join('menu_ingredients as mi', 'mi.menu_id', '=', 'm.id')
            ->where('m.status', 'active')->orderBy('m.id')->first(['m.id', 'm.price']);

        // Kasir tidak bisa memesan menu yang stoknya habis, jadi isi dulu, bayar, lalu kosongkan sebelum "siap".
        foreach (DB::table('menu_ingredients')->where('menu_id', $menu->id)->get() as $r) {
            DB::table('stock_movements')->insert(['ingredient_id' => $r->ingredient_id, 'type' => 'in', 'qty' => $r->qty_needed * 5, 'notes' => 'test']);
        }

        $this->actingAs($this->user('kasir'))->post('/kasir/orders', ['items' => [['menu_id' => $menu->id, 'qty' => 1]], 'table_no' => '3']);
        $trx = Transaction::latest('id')->first();
        $this->post("/kasir/orders/{$trx->id}/pay", ['payment_method' => 'qris']);

        foreach (DB::table('menu_ingredients')->where('menu_id', $menu->id)->get() as $r) {
            DB::selectOne('select public.fn_stock_adjust_to(?, 0, null, ?) as d', [$r->ingredient_id, 'test kosongkan']);
        }

        $this->actingAs($this->user('dapur'))->post("/dapur/orders/{$trx->id}/advance"); // waiting -> preparing

        return $trx->fresh();
    }

    public function test_menandai_siap_saat_stok_kurang_butuh_konfirmasi(): void
    {
        $trx = $this->preparingOrderWithoutStock();
        $this->assertSame('preparing', $trx->order_status);

        $this->get('/dapur/board')->assertOk()->assertSee('Stok bahan kurang');

        // Tanpa konfirmasi: ditolak dan status tidak berubah
        $this->post("/dapur/orders/{$trx->id}/advance")->assertSessionHasErrors('order');
        $this->assertSame('preparing', $trx->fresh()->order_status);

        // Dengan konfirmasi: lanjut, stok terpotong atas nama user dapur
        $this->post("/dapur/orders/{$trx->id}/advance", ['confirm_shortage' => 1])->assertSessionHasNoErrors();
        $this->assertSame('ready', $trx->fresh()->order_status);
        $this->assertNotNull(DB::table('stock_movements')->where('transaction_id', $trx->id)->where('type', 'out')->value('user_id'));
    }

    public function test_halaman_stok_dan_resep_dapur_serta_pembatasan_role(): void
    {
        $this->actingAs($this->user('dapur'));
        $this->get('/dapur/stock')->assertOk();
        $this->get('/dapur/recipes')->assertOk();

        $this->actingAs($this->user('kasir'))->get('/dapur/stock')->assertForbidden();
        $this->actingAs($this->user('waitress'))->get('/dapur/recipes')->assertForbidden();
    }

    public function test_dapur_mendata_stok_fisik_lewat_opname(): void
    {
        $ing = DB::table('ingredients')->where('is_active', true)->first();

        $this->actingAs($this->user('dapur'))
            ->post('/dapur/stock/opname', ['ingredient_id' => $ing->id, 'qty' => 12.5, 'notes' => 'hitung ulang'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(12.5, (float) DB::table('ingredients')->where('id', $ing->id)->value('stock_qty'));
    }
}

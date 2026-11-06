<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel, view, function, dan trigger Kafe Kaleng, diambil dari kafe_kaleng.sql
 * (tanpa data dan tanpa tabel users). Hanya untuk PostgreSQL karena stok dan total
 * transaksi dihitung oleh trigger di database.
 *
 * Dilewati bila database sudah di-restore dari kafe_kaleng.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException(
                'Skema Kafe Kaleng memakai trigger PostgreSQL. Set DB_CONNECTION=pgsql di .env (lihat .env.example).'
            );
        }

        if (Schema::hasTable('transactions')) {
            return;
        }

        DB::unprepared(file_get_contents(database_path('sql/kafe_kaleng_schema.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP VIEW IF EXISTS v_menu_hpp, v_menu_availability, v_ingredient_stock;
            DROP TABLE IF EXISTS transaction_details, stock_movements, menu_ingredients, transactions, menus, ingredients, categories CASCADE;
            DROP FUNCTION IF EXISTS fn_apply_discount(), fn_calc_discount(numeric, text, numeric), fn_guard_order_status(),
                fn_guard_cancel(), fn_sync_transaction_total(), fn_consume_stock_on_ready(),
                fn_apply_stock_movement(), fn_stock_adjust_to(integer, numeric, integer, text);
        SQL);
    }
};

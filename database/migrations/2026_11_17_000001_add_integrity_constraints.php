<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * III.5: NOT NULL, CHECK, UNIQUE, FK RESTRICT, indeks, dan kamus data (COMMENT).
 * Isi: database/sql/2026_11_17_integrity.sql (bisa juga dijalankan manual lewat psql).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_17_integrity.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP INDEX IF EXISTS idx_trx_open_orders, idx_sm_user, idx_trx_user, idx_td_menu,
                idx_menu_ingredients_ing, idx_menus_category, uq_sm_out_per_trx, uq_users_email_lower;
            ALTER TABLE transaction_details DROP CONSTRAINT IF EXISTS uq_td_trx_menu,
                DROP CONSTRAINT IF EXISTS transaction_details_subtotal_check;
            ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_amounts_check,
                DROP CONSTRAINT IF EXISTS transactions_discount_calc_check,
                DROP CONSTRAINT IF EXISTS transactions_table_no_check;
            ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_out_needs_trx;
            ALTER TABLE users DROP CONSTRAINT IF EXISTS users_name_not_blank,
                DROP CONSTRAINT IF EXISTS users_email_format_check,
                DROP CONSTRAINT IF EXISTS users_password_hashed_check;
            ALTER TABLE ingredients DROP CONSTRAINT IF EXISTS ingredients_name_unit_not_blank,
                DROP CONSTRAINT IF EXISTS ingredients_min_stock_check;
            ALTER TABLE menus DROP CONSTRAINT IF EXISTS menus_name_not_blank;
            ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_name_not_blank;
        SQL);
    }
};

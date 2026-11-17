<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * III.6/III.7: audit_logs + trigger pengaman (transaksi tidak bisa dihapus, stok hanya lewat
 * stock_movements, riwayat append-only, minimal satu admin aktif).
 * Isi: database/sql/2026_11_17_protection_audit.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_17_protection_audit.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_users_keep_admin ON users;
            DROP TRIGGER IF EXISTS trg_td_protect ON transaction_details;
            DROP TRIGGER IF EXISTS trg_trx_verify_amounts ON transactions;
            DROP TRIGGER IF EXISTS trg_trx_protect ON transactions;
            DROP TRIGGER IF EXISTS trg_ing_protect_stock_upd ON ingredients;
            DROP TRIGGER IF EXISTS trg_ing_protect_stock_ins ON ingredients;
            DROP TRIGGER IF EXISTS trg_sm_immutable ON stock_movements;
            DROP TRIGGER IF EXISTS trg_audit_immutable ON audit_logs;
            DROP TRIGGER IF EXISTS trg_no_truncate ON users, categories, menus, menu_ingredients, ingredients,
                transactions, transaction_details, stock_movements, audit_logs;
            DROP TRIGGER IF EXISTS trg_audit_users ON users;
            DROP TRIGGER IF EXISTS trg_audit_categories ON categories;
            DROP TRIGGER IF EXISTS trg_audit_menus ON menus;
            DROP TRIGGER IF EXISTS trg_audit_menu_ingredients ON menu_ingredients;
            DROP TRIGGER IF EXISTS trg_audit_ingredients_iud ON ingredients;
            DROP TRIGGER IF EXISTS trg_audit_ingredients_upd ON ingredients;
            DROP TRIGGER IF EXISTS trg_audit_transactions_id ON transactions;
            DROP TRIGGER IF EXISTS trg_audit_transactions_upd ON transactions;
            DROP VIEW IF EXISTS v_audit_log;
            DROP TABLE IF EXISTS audit_logs;
            DROP FUNCTION IF EXISTS fn_protect_users(), fn_protect_transaction_details(), fn_verify_transaction_amounts(),
                fn_protect_transactions(), fn_protect_ingredient_stock(), fn_block_truncate(),
                fn_block_modification(), fn_audit_row();
        SQL);
    }
};

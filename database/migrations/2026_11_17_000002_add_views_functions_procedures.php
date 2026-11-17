<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * III.5 view tambahan dan III.6 fungsi + prosedur tersimpan (sp_create_order, sp_pay_order, dst).
 * Isi: database/sql/2026_11_17_views_routines.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_17_views_routines.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP PROCEDURE IF EXISTS sp_stock_opname(integer, numeric, integer, text, numeric),
                sp_restock(integer, numeric, integer, text), sp_set_order_status(integer, text, integer),
                sp_cancel_order(integer, integer, text), sp_pay_order(integer, text, integer, text, numeric),
                sp_create_order(integer, text, jsonb, integer);
            DROP FUNCTION IF EXISTS fn_assert_role(integer, text[]), fn_sales_report(date, date),
                fn_menu_portions(integer), fn_order_number(integer);
            DROP VIEW IF EXISTS v_user_public, v_stock_history, v_order_queue, v_transaction_summary,
                v_sales_by_menu, v_sales_daily;
        SQL);
    }
};

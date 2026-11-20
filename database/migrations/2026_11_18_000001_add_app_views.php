<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * III.5 view tambahan yang dipakai halaman aplikasi (riwayat kasir, papan dapur/pelayan, laporan pemilik, resep).
 * Isi: database/sql/2026_11_18_app_views.sql. Hak SELECT diberikan oleh database/sql/2026_11_17_roles.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_18_app_views.sql')));
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_admin_summary, v_menu_recipe, v_stock_flow_daily, v_sales_by_menu_daily, v_transaction_history, v_order_lines;');
    }
};

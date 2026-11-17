<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyiapkan database untuk mode SET ROLE (opsional): fungsi trigger lintas tabel dijalankan sebagai
 * pemilik dan audit_logs mencatat role aktif. Tidak membuat role apa pun; role dibuat manual lewat
 * database/sql/2026_11_17_roles.sql. Isi: database/sql/2026_11_17_set_role_support.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_17_set_role_support.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER FUNCTION fn_apply_stock_movement()   SECURITY INVOKER RESET search_path;
            ALTER FUNCTION fn_consume_stock_on_ready() SECURITY INVOKER RESET search_path;
            ALTER FUNCTION fn_sync_transaction_total() SECURITY INVOKER RESET search_path;
            ALTER FUNCTION fn_stock_adjust_to(integer, numeric, integer, text) SECURITY INVOKER RESET search_path;
        SQL);
    }
};

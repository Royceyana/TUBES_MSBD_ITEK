<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Jenis pesanan (makan di tempat / bawa pulang) dan jejak pengantaran oleh pelayan.
 * Isinya ada di database/sql/2026_11_14_order_type_served_at.sql (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_14_order_type_served_at.sql')));
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP INDEX IF EXISTS idx_transactions_served_at;
            ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_served_by_fkey;
            ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_dine_in_needs_table;
            ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_order_type_check;
            ALTER TABLE transactions DROP COLUMN IF EXISTS served_by, DROP COLUMN IF EXISTS served_at, DROP COLUMN IF EXISTS order_type;
        SQL);
    }
};

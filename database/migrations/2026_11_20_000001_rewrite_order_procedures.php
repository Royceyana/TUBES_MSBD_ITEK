<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * III.6: sp_create_order dan sp_pay_order ditulis ulang (order_type, tanpa debit, p_paid/p_total) dan
 * dipakai PosController. Isi: database/sql/2026_11_20_order_procedures.sql. Jalankan sebagai pemilik tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_20_order_procedures.sql')));
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_create_order(integer, text, text, jsonb, integer);'
            .'DROP PROCEDURE IF EXISTS sp_pay_order(integer, text, integer, text, numeric, numeric, numeric);');
    }
};

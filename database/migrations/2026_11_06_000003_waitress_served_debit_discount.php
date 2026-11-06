<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Role waitress, status pesanan `served`, metode bayar `debit`, dan diskon transaksi.
 * Isinya ada di database/sql/2026_11_06_waitress_served_debit_discount.sql (idempotent),
 * file yang sama bisa dijalankan manual lewat psql.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_06_waitress_served_debit_discount.sql')));
    }

    public function down(): void
    {
        // Sengaja tidak dibalik: mengembalikan constraint lama akan gagal bila sudah ada
        // pesanan `served`, pembayaran `debit`, atau akun waitress. Pakai migrate:fresh di lingkungan dev.
    }
};

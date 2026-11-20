<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * III.6: fn_order_number dipakai view, sp_set_order_status diperbarui (served_at/served_by).
 * Isi: database/sql/2026_11_19_routines_in_use.sql. Jalankan sebagai pemilik tabel (postgres).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/2026_11_19_routines_in_use.sql')));
    }

    public function down(): void
    {
        // View dikembalikan ke definisi 2026_11_18_app_views.sql (nomor dihitung langsung, tanpa fn_order_number).
        DB::unprepared(file_get_contents(database_path('sql/2026_11_18_app_views.sql')));
    }
};

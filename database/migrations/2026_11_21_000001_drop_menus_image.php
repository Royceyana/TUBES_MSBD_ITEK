<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Foto menu tidak dipakai lagi (fitur unggah foto dihapus dari Kelola Menu), jadi kolom menus.image dibuang.
 * Jalankan sebagai pemilik tabel (postgres). Isi kolom (path foto) hilang permanen, backup dulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('ALTER TABLE menus DROP COLUMN IF EXISTS image;');
    }

    public function down(): void
    {
        DB::unprepared("ALTER TABLE menus ADD COLUMN IF NOT EXISTS image character varying(255);\n"
            ."COMMENT ON COLUMN menus.image IS 'Path foto menu (opsional).';");
    }
};

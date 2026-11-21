<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Penanda saja, tidak mengubah database.
 *
 * Migrasi ini sudah tercatat di tabel `migrations` pada dump kafe_kaleng (batch 2), tetapi file-nya tidak
 * ada di repository. Berkas ini mengembalikan nama migrasi tersebut supaya daftar migrasi di repository
 * sama dengan yang ada di database. Role database (kafe_web_*, dst.) TIDAK dibuat otomatis; buat manual
 * lewat database/sql/2026_11_17_roles.sql bila mode DB_SET_ROLE=true ingin dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sengaja kosong.
    }

    public function down(): void
    {
        // Sengaja kosong.
    }
};

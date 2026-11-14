<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyiapkan tabel users untuk aplikasi. Bila tabelnya belum ada (database kosong, mis. setelah
 * migrate:fresh) dibuat di sini lengkap dengan role dan status; bila sudah ada (hasil impor
 * kafe_kaleng.sql) hanya dilengkapi kolom bawaan Laravel yang tidak ada di dump.
 * Constraint role/status dipasang oleh migrasi waitress_served_debit_discount.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('email', 100)->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role', 20);
                $table->string('status', 20)->default('active');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('kasir');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status', 20)->default('active');
            }
        });

        // Role harus selalu diisi eksplisit; default sementara di atas hanya untuk baris lama.
        DB::statement('ALTER TABLE users ALTER COLUMN role DROP DEFAULT');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
    }
};

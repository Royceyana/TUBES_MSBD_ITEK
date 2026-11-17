<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * 1. Mengisi setting PostgreSQL app.user_id dengan id pengguna yang login (dipakai trigger audit dan
 *    pemotongan stok untuk mencatat pelaku). Kosong untuk tamu.
 * 2. Bila DB_SET_ROLE=true: menjalankan SET ROLE kafe_web_<peran> selama request, lalu RESET ROLE,
 *    sehingga akses database mengikuti hak peran (admin/kasir/dapur/waitress/owner).
 *
 * Tamu (halaman login, beranda publik) tetap memakai akun koneksi apa adanya. Sesi disimpan oleh
 * StartSession setelah middleware ini selesai, yaitu sudah kembali ke akun koneksi.
 * Peran database diambil dari daftar tetap config('database.web_roles'), bukan dari input pengguna.
 */
class SetDatabaseUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DB::getDriverName() !== 'pgsql') {
            return $next($request);
        }

        $user = $request->user();

        DB::select("select set_config('app.user_id', ?, false)", [(string) ($user?->id ?? '')]);

        $role = $this->webRole($user?->role);
        if ($role === null) {
            return $next($request);
        }

        DB::statement('SET ROLE "'.str_replace('"', '""', $role).'"');

        try {
            return $next($request);
        } finally {
            DB::statement('RESET ROLE');
        }
    }

    private function webRole(?string $appRole): ?string
    {
        if (! config('database.set_role') || $appRole === null) {
            return null;
        }

        return config('database.web_roles', [])[$appRole] ?? null;
    }
}

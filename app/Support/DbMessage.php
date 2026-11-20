<?php

namespace App\Support;

use Illuminate\Database\QueryException;

/** Mengambil pesan RAISE EXCEPTION dari function/prosedur PostgreSQL agar bisa ditampilkan ke pengguna. */
class DbMessage
{
    public static function from(QueryException $e, string $fallback): string
    {
        $raw = (string) ($e->errorInfo[2] ?? '');

        if (preg_match('/ERROR:\s+(.+?)(?:\R|$)/u', $raw, $m)) {
            return trim($m[1]);
        }

        return $fallback;
    }
}

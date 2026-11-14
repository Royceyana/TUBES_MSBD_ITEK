<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait SyncsSequence
{
    /**
     * Data master di-insert dengan id eksplisit (sama seperti kafe_kaleng.sql), jadi sequence
     * PostgreSQL harus dimajukan agar insert berikutnya dari aplikasi tidak bentrok id.
     */
    protected function syncSequence(string $table): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), (SELECT COUNT(*) > 0 FROM {$table}))"
        );
    }
}

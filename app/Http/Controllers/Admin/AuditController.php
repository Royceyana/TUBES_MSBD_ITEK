<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Audit log (baca-saja) dari view v_audit_log: siapa mengubah apa, kapan, lewat akun dan peran database apa. */
class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $f = $request->validate([
            'tabel' => ['nullable', 'string', 'max:64'],
            'aksi' => ['nullable', 'in:INSERT,UPDATE,DELETE'],
        ]);

        $logs = DB::table('v_audit_log')
            ->when($f['tabel'] ?? null, fn ($q, $t) => $q->where('tabel', $t))
            ->when($f['aksi'] ?? null, fn ($q, $a) => $q->where('aksi', $a))
            ->orderByDesc('id')->limit(100)
            ->get(['id', 'waktu', 'tabel', 'record_id', 'aksi', 'kolom_berubah', 'pelaku_aplikasi', 'akun_database', 'peran_database', 'catatan']);

        $tables = DB::table('v_audit_log')->distinct()->orderBy('tabel')->pluck('tabel');

        return view('admin.audit', ['logs' => $logs, 'tables' => $tables, 'filter' => ['tabel' => $f['tabel'] ?? '', 'aksi' => $f['aksi'] ?? '']]);
    }
}

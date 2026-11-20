@extends('layouts.app')
@section('title', 'Audit Log | Kafe Kaleng')

@section('content')
<x-page-head title="Audit Log" script="jejak perubahan data" />

<p class="muted mt-6 max-w-2xl text-lg">Setiap tambah, ubah, atau hapus data tercatat otomatis oleh trigger database. Halaman ini hanya membaca view <code>v_audit_log</code> dan menampilkan 100 catatan terbaru.</p>

<form method="GET" action="{{ route('admin.audit') }}" class="mt-8 flex flex-wrap items-end gap-4">
    <div>
        <label for="tabel" class="label">Tabel</label>
        <select id="tabel" name="tabel" class="input">
            <option value="">Semua tabel</option>
            @foreach ($tables as $t)
                <option value="{{ $t }}" @selected($filter['tabel'] === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="aksi" class="label">Aksi</label>
        <select id="aksi" name="aksi" class="input">
            <option value="">Semua aksi</option>
            @foreach (['INSERT' => 'Tambah', 'UPDATE' => 'Ubah', 'DELETE' => 'Hapus'] as $k => $label)
                <option value="{{ $k }}" @selected($filter['aksi'] === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Terapkan</button>
</form>

<section class="mt-10" aria-labelledby="audit-h">
    <h2 id="audit-h" class="script text-4xl">Catatan terbaru</h2>
    @if ($logs->isEmpty())
        <p class="muted mt-4 text-lg">Belum ada catatan yang cocok dengan filter ini.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[52rem]">
                <thead><tr><th>Waktu</th><th>Tabel</th><th>Aksi</th><th>Record</th><th>Kolom berubah</th><th>Pelaku</th><th>Akun / peran DB</th><th>Catatan</th></tr></thead>
                <tbody>
                    @foreach ($logs as $l)
                        <tr>
                            <td class="whitespace-nowrap text-muted">{{ \Illuminate\Support\Carbon::parse($l->waktu)->translatedFormat('d M Y, H:i') }}</td>
                            <td class="font-semibold">{{ $l->tabel }}</td>
                            <td><span class="status {{ ['INSERT' => 'status-ok', 'UPDATE' => 'status-warn'][$l->aksi] ?? 'status-mute' }}">{{ ['INSERT' => 'Tambah', 'UPDATE' => 'Ubah', 'DELETE' => 'Hapus'][$l->aksi] ?? $l->aksi }}</span></td>
                            <td class="text-muted">{{ $l->record_id }}</td>
                            <td class="max-w-48 truncate text-muted">{{ trim((string) $l->kolom_berubah, '{}') }}</td>
                            <td class="text-muted">{{ $l->pelaku_aplikasi ?? 'Sistem' }}</td>
                            <td class="text-muted">{{ $l->akun_database }}@if ($l->peran_database) / {{ $l->peran_database }}@endif</td>
                            <td class="max-w-56 truncate text-muted">{{ $l->catatan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection

@extends('layouts.app')
@section('title', 'Kelola Pengguna | Kafe Kaleng')
@php $roles = \App\Models\User::ROLE_LABEL; @endphp

@section('content')
<x-page-head title="Pengguna" script="akun & akses">
    <button type="button" class="btn btn-primary" data-modal-open="user-form" data-title="Tambah pengguna" data-action="{{ route('admin.users.store') }}" data-method="POST"
        data-fill-name="" data-fill-email="" data-fill-role="kasir" data-fill-status="active" data-fill-password="">Tambah pengguna</button>
</x-page-head>

<div class="mt-10 max-w-sm">
    <label for="q" class="label">Cari pengguna</label>
    <input id="q" type="search" class="input" data-search="#user-rows tr" data-empty="#user-none" placeholder="Nama atau email">
</div>
<div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Filter role" data-filter="#user-rows tr" data-empty="#user-none">
    <button type="button" class="chip" aria-pressed="true" data-cat="all">Semua</button>
    @foreach ($roles as $key => $label)<button type="button" class="chip" aria-pressed="false" data-cat="{{ $key }}">{{ $label }}</button>@endforeach
</div>

<div class="mt-8 overflow-x-auto">
    <table class="tbl min-w-[40rem]">
        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th class="r">Aksi</th></tr></thead>
        <tbody id="user-rows">
            @foreach ($users as $u)
                <tr data-cat="{{ $u->role }}">
                    <td class="font-semibold">{{ $u->name }}@if ($u->id === auth()->id()) <span class="muted font-normal">(Anda)</span>@endif</td>
                    <td class="text-muted">{{ $u->email }}</td>
                    <td>{{ $roles[$u->role] ?? $u->role }}</td>
                    <td><span class="status {{ $u->status === 'active' ? 'status-ok' : 'status-mute' }}">{{ $u->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="r">
                        <button type="button" class="btn btn-line btn-sm" aria-label="Ubah {{ $u->name }}" data-modal-open="user-form" data-title="Ubah pengguna" data-action="{{ route('admin.users.update', $u) }}" data-method="PUT"
                            data-fill-name="{{ $u->name }}" data-fill-email="{{ $u->email }}" data-fill-role="{{ $u->role }}" data-fill-status="{{ $u->status }}" data-fill-password="">Ubah</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p id="user-none" class="muted mt-6 text-lg" hidden>Tidak ada pengguna yang cocok.</p>
<p class="help mt-6">Akun tidak dihapus karena transaksi dan riwayat stok mencatat siapa yang mengerjakannya. Nonaktifkan akun yang sudah tidak dipakai.</p>

@push('modals')
<x-modal id="user-form" title="Tambah pengguna">
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5" autocomplete="off">@csrf
        <input type="hidden" name="_method" value="POST">
        <div><label class="label" for="u-name">Nama</label><input id="u-name" name="name" class="input" required maxlength="100"></div>
        <div><label class="label" for="u-email">Email</label><input id="u-email" name="email" type="email" class="input" required maxlength="100"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="u-role">Role</label>
                <select id="u-role" name="role" class="select">@foreach ($roles as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div><label class="label" for="u-status">Status</label>
                <select id="u-status" name="status" class="select"><option value="active">Aktif</option><option value="inactive">Nonaktif, tidak bisa masuk</option></select></div>
        </div>
        <div><label class="label" for="u-pass">Kata sandi</label><input id="u-pass" name="password" type="password" class="input" minlength="8" maxlength="100" autocomplete="new-password">
            <p class="help">Minimal 8 karakter. Wajib untuk akun baru; saat mengubah, kosongkan jika tidak ingin mengganti.</p></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endpush
@endsection

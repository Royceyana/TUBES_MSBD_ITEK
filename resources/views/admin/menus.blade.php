@extends('layouts.app')
@section('title', 'Menu & Resep | Kafe Kaleng')

@section('content')
<x-page-head title="Menu" script="& resep">
    <button type="button" class="btn btn-primary" data-modal-open="menu-form" data-title="Tambah menu" data-action="{{ route('admin.menus.store') }}" data-method="POST"
        data-fill-name="" data-fill-price="" data-fill-category_id="{{ $categories->first()->id ?? '' }}" data-fill-status="active">Tambah menu</button>
</x-page-head>

@if ($menus->isEmpty())
    <p class="muted mt-12 text-lg">Belum ada menu. Tambahkan menu pertama, lalu atur resepnya.</p>
@else
    <div class="mt-10 flex flex-wrap items-end gap-4">
        <div class="min-w-52 flex-1 md:max-w-xs">
            <label for="q" class="label">Cari menu</label>
            <input id="q" type="search" class="input" data-search="#menu-rows tr" data-empty="#menu-none" placeholder="mis. nasi goreng">
        </div>
    </div>
    <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Filter kategori" data-filter="#menu-rows tr" data-empty="#menu-none">
        <button type="button" class="chip" aria-pressed="true" data-cat="all">Semua</button>
        @foreach ($categories as $c)<button type="button" class="chip" aria-pressed="false" data-cat="{{ $c->id }}">{{ $c->name }}</button>@endforeach
    </div>

    <div class="mt-8 overflow-x-auto">
        <table class="tbl min-w-[52rem]">
            <thead><tr><th>Menu</th><th>Kategori</th><th class="r">Harga</th><th class="r">HPP</th><th>Resep</th><th class="r">Sisa porsi</th><th>Status</th><th class="r">Aksi</th></tr></thead>
            <tbody id="menu-rows">
                @foreach ($menus as $m)
                    <tr data-cat="{{ $m->category_id }}">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="font-semibold">{{ $m->name }}</span>
                            </div>
                        </td>
                        <td class="text-muted">{{ $m->category ?? 'Tanpa kategori' }}</td>
                        <td class="num r font-bold">{{ number_format($m->price, 0, ',', '.') }}</td>
                        <td class="num r text-muted">{{ $m->recipe_count ? number_format($m->hpp, 0, ',', '.') : '-' }}</td>
                        <td>@if ($m->recipe_count)<span class="text-muted">{{ $m->recipe_count }} bahan</span>@else<span class="status status-bad">Belum ada resep</span>@endif</td>
                        <td class="num r">{{ $m->portions === null ? '-' : $m->portions }}</td>
                        <td><span class="status {{ $m->status === 'active' ? 'status-ok' : 'status-mute' }}">{{ $m->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="r">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('admin.menus.recipe', $m->id) }}" class="btn btn-dark btn-sm" aria-label="Resep {{ $m->name }}">Resep</a>
                                <button type="button" class="btn btn-line btn-sm" aria-label="Ubah {{ $m->name }}" data-modal-open="menu-form" data-title="Ubah menu" data-action="{{ route('admin.menus.update', $m->id) }}" data-method="PUT"
                                    data-fill-name="{{ $m->name }}" data-fill-price="{{ (int) $m->price }}" data-fill-category_id="{{ $m->category_id }}" data-fill-status="{{ $m->status }}">Ubah</button>
                                <form method="POST" action="{{ route('admin.menus.destroy', $m->id) }}" data-confirm="Menu “{{ $m->name }}” akan dihapus beserta resepnya. Menu yang pernah terjual tidak bisa dihapus, nonaktifkan saja." data-confirm-yes="Ya, hapus">@csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" aria-label="Hapus {{ $m->name }}">Hapus</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p id="menu-none" class="muted mt-6 text-lg" hidden>Tidak ada menu yang cocok.</p>
@endif

@push('modals')
<x-modal id="menu-form" title="Tambah menu" :large="true">
    <form method="POST" action="{{ route('admin.menus.store') }}" class="space-y-5">@csrf
        <input type="hidden" name="_method" value="POST">
        <input type="hidden" name="_form" value="menu"><input type="hidden" name="_return_action" value=""><input type="hidden" name="_return_title" value="">
        <div><label class="label" for="m-name">Nama menu</label><input id="m-name" name="name" class="input" required maxlength="100" placeholder="mis. Nasi Goreng Kampung"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="m-cat">Kategori</label>
                <select id="m-cat" name="category_id" class="select"><option value="">Tanpa kategori</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="label" for="m-price">Harga (Rp)</label><input id="m-price" name="price" type="number" min="1" step="1" inputmode="numeric" class="input num" required placeholder="15000"></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="m-status">Status</label>
                <select id="m-status" name="status" class="select"><option value="active">Aktif, tampil di kasir</option><option value="inactive">Nonaktif, disembunyikan</option></select></div>
        </div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Simpan menu</button></div>
    </form>
</x-modal>
@endpush

@php
    $oldMenu = collect(old())->only(['name', 'category_id', 'price', 'status', '_return_action', '_return_title', '_method'])->all();
@endphp
@push('scripts')
<script>
(() => {
    const dlg = document.getElementById('menu-form');
    if (!dlg) return;
    const form = dlg.querySelector('form');

    // Dipasang setelah handler umum di app.js, jadi form.action & judul sudah terisi.
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-modal-open="menu-form"]');
        if (!b) return;
        form.querySelector('[name=_return_action]').value = form.action;
        form.querySelector('[name=_return_title]').value = dlg.querySelector('[data-modal-title]').textContent;
    });

    @if ($errors->any() && old('_form') === 'menu')
    // Validasi gagal: buka lagi dialog dengan isian terakhir supaya admin tidak mengetik ulang.
    addEventListener('DOMContentLoaded', () => {
        const old = @json($oldMenu);
        ['name', 'category_id', 'price', 'status'].forEach((n) => { const el = form.querySelector(`[name="${n}"]`); if (el && old[n] != null) el.value = old[n]; });
        if (old._return_action) form.action = old._return_action;
        form.querySelector('[name=_method]').value = old._method || 'POST';
        form.querySelector('[name=_return_action]').value = form.action;
        form.querySelector('[name=_return_title]').value = old._return_title || '';
        if (old._return_title) dlg.querySelector('[data-modal-title]').textContent = old._return_title;
        rm.hidden = true;
        openModal(dlg);
    });
    @endif
})();
</script>
@endpush
@endsection

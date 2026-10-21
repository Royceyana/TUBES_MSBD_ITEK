@extends('layouts.app')
@section('title', 'Kategori Menu — Kafe Kaleng')
@section('page-heading', 'Kategori Menu')
@section('actions')
    <button class="btn btn-primary btn-sm" data-modal-open="cat-form" data-title="Tambah kategori" data-fill-name="" data-fill-_method="POST"><x-icon name="plus" size="size-4" /> Tambah kategori</button>
@endsection

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($categories as $i => $c)
        <article class="card card-pad rise flex items-center gap-4" style="--i:{{ $i }}">
            <span class="grid size-14 place-items-center rounded-2xl bg-amber-soft text-amber-deep"><x-icon name="tags" size="size-6" /></span>
            <div class="min-w-0 flex-1">
                <h2 class="truncate text-lg font-bold">{{ $c->name }}</h2>
                <p class="text-sm text-bark">{{ $c->menus_count }} menu</p>
            </div>
            <div class="flex gap-1.5">
                <button class="btn btn-ghost btn-icon btn-sm" aria-label="Ubah {{ $c->name }}" data-modal-open="cat-form" data-title="Ubah kategori" data-fill-name="{{ $c->name }}"><x-icon name="pencil" size="size-4" /></button>
                <form method="POST" action="#" data-demo="Kategori dihapus (demo)" data-confirm="Kategori “{{ $c->name }}” akan dihapus. Menu di dalamnya perlu dipindahkan dulu.">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-icon btn-sm" aria-label="Hapus {{ $c->name }}"><x-icon name="trash" size="size-4" /></button>
                </form>
            </div>
        </article>
    @endforeach
</div>

@push('modals')
<x-modal id="cat-form" title="Tambah kategori">
    <form method="POST" action="#" data-demo class="space-y-5">
        @csrf
        <input type="hidden" name="_method" value="POST">
        <div>
            <label for="cat-name" class="label">Nama kategori</label>
            <input id="cat-name" name="name" class="input" maxlength="50" required placeholder="mis. Kopi, Makanan Berat">
        </div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endpush
@endsection

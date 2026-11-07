@extends('layouts.app')
@section('title', 'Kategori Menu | Kafe Kaleng')

@section('content')
<x-page-head title="Kategori" script="menu">
    <button type="button" class="btn btn-primary" data-modal-open="cat-form" data-title="Tambah kategori" data-action="{{ route('admin.categories.store') }}" data-method="POST" data-fill-name="">Tambah kategori</button>
</x-page-head>

@if ($categories->isEmpty())
    <p class="muted mt-12 text-lg">Belum ada kategori. Tambahkan yang pertama untuk mengelompokkan menu.</p>
@else
    <div class="mt-10 overflow-x-auto">
        <table class="tbl min-w-[28rem]">
            <thead><tr><th>Nama kategori</th><th class="r">Jumlah menu</th><th class="r">Aksi</th></tr></thead>
            <tbody>
                @foreach ($categories as $c)
                    <tr>
                        <td class="display text-lg">{{ $c->name }}</td>
                        <td class="num r">{{ $c->menus_count }}</td>
                        <td class="r">
                            <div class="inline-flex gap-2">
                                <button type="button" class="btn btn-line btn-sm" data-modal-open="cat-form" data-title="Ubah kategori" data-action="{{ route('admin.categories.update', $c) }}" data-method="PUT" data-fill-name="{{ $c->name }}" aria-label="Ubah {{ $c->name }}">Ubah</button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" data-confirm="Kategori “{{ $c->name }}” akan dihapus.{{ $c->menus_count ? ' '.$c->menus_count.' menu di dalamnya tetap ada, tetapi tanpa kategori.' : '' }}" data-confirm-yes="Ya, hapus">@csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" aria-label="Hapus {{ $c->name }}">Hapus</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@push('modals')
<x-modal id="cat-form" title="Tambah kategori">
    <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-5">@csrf
        <input type="hidden" name="_method" value="POST">
        <div><label class="label" for="c-name">Nama kategori</label><input id="c-name" name="name" class="input" required maxlength="50" placeholder="mis. Nasi"></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Simpan kategori</button></div>
    </form>
</x-modal>
@endpush
@endsection

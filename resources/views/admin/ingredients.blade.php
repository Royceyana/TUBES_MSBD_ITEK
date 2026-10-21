@extends('layouts.app')
@section('title', 'Bahan Baku — Kafe Kaleng')
@section('page-heading', 'Bahan Baku')
@section('actions')
    <button class="btn btn-primary btn-sm" data-modal-open="ing-form" data-title="Tambah bahan baku" data-fill-name="" data-fill-unit="" data-fill-min_stock=""><x-icon name="plus" size="size-4" /> Tambah bahan</button>
@endsection

@php $fmt = fn($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<div class="card rise overflow-hidden">
    <div class="flex flex-wrap items-center gap-3 p-4 md:p-5">
        <div class="relative min-w-52 flex-1 md:max-w-sm">
            <x-icon name="search" size="size-[1.1rem]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-bark" />
            <input type="search" data-search="#ing-rows tr" class="input !pl-11" placeholder="Cari bahan baku…" aria-label="Cari bahan baku">
        </div>
        <p class="text-sm text-bark">{{ count($ingredients) }} bahan · <span class="font-semibold text-wine">{{ collect($ingredients)->filter(fn($i) => $i->stock_qty <= $i->min_stock)->count() }} menipis</span></p>
    </div>
    <div class="overflow-x-auto px-2 pb-3 md:px-4">
        <table class="tbl min-w-[40rem]">
            <thead><tr><th>Bahan</th><th>Satuan</th><th class="r">Stok</th><th class="r">Batas min.</th><th>Status</th><th class="r">Aksi</th></tr></thead>
            <tbody id="ing-rows">
                @foreach ($ingredients as $ing)
                    @php $empty = $ing->stock_qty <= 0; $low = !$empty && $ing->stock_qty <= $ing->min_stock; @endphp
                    <tr>
                        <td class="font-semibold">{{ $ing->name }}</td>
                        <td class="text-bark-dark">{{ $ing->unit }}</td>
                        <td class="num r font-bold {{ $empty ? 'text-wine' : '' }}">{{ $fmt($ing->stock_qty) }}</td>
                        <td class="num r text-bark-dark">{{ $fmt($ing->min_stock) }}</td>
                        <td><span class="badge {{ $empty ? 'badge-bad' : ($low ? 'badge-warn' : 'badge-ok') }}">{{ $empty ? 'Habis' : ($low ? 'Menipis' : 'Aman') }}</span></td>
                        <td class="r">
                            <div class="inline-flex gap-1.5">
                                <button class="btn btn-ghost btn-icon btn-sm" aria-label="Ubah {{ $ing->name }}" data-modal-open="ing-form" data-title="Ubah bahan baku" data-fill-name="{{ $ing->name }}" data-fill-unit="{{ $ing->unit }}" data-fill-min_stock="{{ $ing->min_stock }}"><x-icon name="pencil" size="size-4" /></button>
                                <form method="POST" action="#" data-demo="Bahan dihapus (demo)" data-confirm="Bahan “{{ $ing->name }}” akan dihapus dari semua resep.">@csrf @method('DELETE')
                                    <button class="btn btn-danger btn-icon btn-sm" aria-label="Hapus {{ $ing->name }}"><x-icon name="trash" size="size-4" /></button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('modals')
<x-modal id="ing-form" title="Tambah bahan baku">
    <form method="POST" action="#" data-demo class="space-y-5">@csrf
        <div><label class="label" for="ing-name">Nama bahan</label><input id="ing-name" name="name" class="input" required maxlength="100" placeholder="mis. Biji kopi arabika"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="ing-unit">Satuan</label>
                <input id="ing-unit" name="unit" class="input" list="units" required maxlength="20" placeholder="gram, ml, pcs">
                <datalist id="units"><option>gram</option><option>ml</option><option>pcs</option><option>kg</option><option>liter</option></datalist></div>
            <div><label class="label" for="ing-min">Batas minimum stok</label><input id="ing-min" name="min_stock" type="number" step="0.01" min="0" class="input num" required placeholder="0">
                <p class="help">Di bawah angka ini bahan ditandai menipis.</p></div>
        </div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endpush
@endsection

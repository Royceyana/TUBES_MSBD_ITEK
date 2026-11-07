@extends('layouts.app')
@section('title', 'Bahan Baku | Kafe Kaleng')
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<x-page-head title="Bahan baku" script="& harga satuan">
    <button type="button" class="btn btn-primary" data-modal-open="ing-form" data-title="Tambah bahan" data-action="{{ route('admin.ingredients.store') }}" data-method="POST" data-fill-name="" data-fill-unit="" data-fill-min_stock="0" data-fill-cost_per_unit="0">Tambah bahan</button>
</x-page-head>

@if ($ingredients->isEmpty())
    <p class="muted mt-12 text-lg">Belum ada bahan baku. Tambahkan bahan lalu isi stoknya di halaman Stok.</p>
@else
    <div class="mt-10 max-w-sm">
        <label for="q" class="label">Cari bahan</label>
        <input id="q" type="search" class="input" data-search="#ing-rows tr" data-empty="#ing-none" placeholder="mis. beras">
    </div>
    <div class="mt-6 overflow-x-auto">
        <table class="tbl min-w-[42rem]">
            <thead><tr><th>Bahan</th><th>Satuan</th><th class="r">Stok</th><th class="r">Minimum</th><th class="r">Harga / satuan</th><th class="r">Aksi</th></tr></thead>
            <tbody id="ing-rows">
                @foreach ($ingredients as $i)
                    <tr>
                        <td class="font-semibold">{{ $i->name }}</td>
                        <td class="text-muted">{{ $i->unit }}</td>
                        <td class="num r font-bold {{ $i->stock_qty <= 0 ? 'text-bad' : '' }}">{{ $fmt($i->stock_qty) }}</td>
                        <td class="num r text-muted">{{ $fmt($i->min_stock) }}</td>
                        <td class="num r">Rp {{ $fmt($i->cost_per_unit) }}</td>
                        <td class="r">
                            <div class="inline-flex gap-2">
                                <button type="button" class="btn btn-line btn-sm" data-modal-open="ing-form" data-title="Ubah bahan" data-action="{{ route('admin.ingredients.update', $i) }}" data-method="PUT"
                                    data-fill-name="{{ $i->name }}" data-fill-unit="{{ $i->unit }}" data-fill-min_stock="{{ $i->min_stock + 0 }}" data-fill-cost_per_unit="{{ $i->cost_per_unit + 0 }}" aria-label="Ubah {{ $i->name }}">Ubah</button>
                                <form method="POST" action="{{ route('admin.ingredients.destroy', $i) }}" data-confirm="Bahan “{{ $i->name }}” akan diarsipkan dan tidak muncul lagi di daftar. Riwayat stok tetap tersimpan." data-confirm-yes="Ya, arsipkan">@csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" aria-label="Arsipkan {{ $i->name }}">Arsipkan</button></form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p id="ing-none" class="muted mt-6 text-lg" hidden>Tidak ada bahan yang cocok.</p>
@endif

@push('modals')
<x-modal id="ing-form" title="Tambah bahan">
    <form method="POST" action="{{ route('admin.ingredients.store') }}" class="space-y-5">@csrf
        <input type="hidden" name="_method" value="POST">
        <div><label class="label" for="i-name">Nama bahan</label><input id="i-name" name="name" class="input" required maxlength="100" placeholder="mis. Beras"></div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="label" for="i-unit">Satuan</label><input id="i-unit" name="unit" class="input" required maxlength="20" placeholder="g, ml, pcs"></div>
            <div><label class="label" for="i-min">Stok minimum</label><input id="i-min" name="min_stock" type="number" step="0.01" min="0" class="input num" required></div>
            <div><label class="label" for="i-cost">Harga / satuan (Rp)</label><input id="i-cost" name="cost_per_unit" type="number" step="0.01" min="0" class="input num" required></div>
        </div>
        <p class="help">Stok awal 0. Isi stok lewat halaman Stok supaya tercatat di riwayat.</p>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Simpan bahan</button></div>
    </form>
</x-modal>
@endpush
@endsection

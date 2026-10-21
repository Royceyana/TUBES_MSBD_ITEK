@extends('layouts.app')
@section('title', 'Manajemen Stok — Kafe Kaleng')
@section('page-heading', 'Manajemen Stok')
@section('actions')
    <button class="btn btn-ghost btn-sm" data-modal-open="opname-form"><x-icon name="clipboard" size="size-4" /> <span class="hidden sm:inline">Stok opname</span></button>
    <button class="btn btn-primary btn-sm" data-modal-open="in-form"><x-icon name="plus" size="size-4" /> Stok masuk</button>
@endsection

@php $fmt = fn($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<div class="space-y-6">
    @if (count($lowStock))
        <div class="rise flex flex-wrap items-center gap-4 rounded-2xl border border-terra/30 bg-amber-soft p-4 md:p-5" role="alert">
            <span class="grid size-11 place-items-center rounded-xl bg-terra text-white"><x-icon name="alert" /></span>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-bean">{{ count($lowStock) }} bahan hampir habis</p>
                <p class="text-sm text-bark-dark">{{ collect($lowStock)->pluck('name')->take(4)->join(', ') }}{{ count($lowStock) > 4 ? ', dan lainnya' : '' }}</p>
            </div>
            <button class="btn btn-dark btn-sm" data-modal-open="in-form">Catat pembelian</button>
        </div>
    @endif

    <div class="card rise overflow-hidden" style="--i:1">
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 md:p-5">
            <h2 class="text-lg font-bold">Riwayat pergerakan</h2>
            <div class="no-scrollbar flex gap-2 overflow-x-auto" id="mv-filter" role="group" aria-label="Filter jenis">
                <button class="chip" aria-pressed="true" data-t="all">Semua</button>
                <button class="chip" aria-pressed="false" data-t="in">Masuk</button>
                <button class="chip" aria-pressed="false" data-t="out">Keluar</button>
                <button class="chip" aria-pressed="false" data-t="adjustment">Opname</button>
            </div>
        </div>
        <div class="overflow-x-auto px-2 pb-3 md:px-4">
            <table class="tbl min-w-[40rem]">
                <thead><tr><th>Waktu</th><th>Bahan</th><th>Jenis</th><th class="r">Jumlah</th><th>Catatan</th><th>Oleh</th></tr></thead>
                <tbody id="mv-rows">
                    @foreach ($movements as $m)
                        <tr data-t="{{ $m->type }}">
                            <td class="whitespace-nowrap text-bark-dark">{{ $m->time }}</td>
                            <td class="font-semibold">{{ $m->ingredient }}</td>
                            <td><span class="badge {{ $m->type === 'in' ? 'badge-ok' : ($m->type === 'out' ? 'badge-warn' : 'badge-mute') }}">{{ ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Opname'][$m->type] ?? $m->type }}</span></td>
                            <td class="num r font-bold">{{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '−' : '') }}{{ $m->qty }} <span class="font-normal text-bark">{{ $m->unit }}</span></td>
                            <td class="max-w-56 truncate text-bark-dark">{{ $m->notes }}</td>
                            <td class="text-bark-dark">{{ $m->user }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('modals')
{{-- Stok masuk --}}
<x-modal id="in-form" title="Catat stok masuk">
    <form method="POST" action="#" data-demo="Stok masuk tercatat (demo)" class="space-y-5">@csrf
        <input type="hidden" name="type" value="in">
        <div><label class="label" for="in-ing">Bahan baku</label>
            <select id="in-ing" name="ingredient_id" class="select" required>@foreach ($ingredients as $ing)<option value="{{ $ing->id }}">{{ $ing->name }} (stok {{ $fmt($ing->stock_qty) }} {{ $ing->unit }})</option>@endforeach</select></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="in-qty">Jumlah dibeli</label><input id="in-qty" name="qty" type="number" step="0.01" min="0.01" class="input num" required></div>
            {{-- CATATAN BACKEND: kolom total_cost belum ada di tabel stock_movements; dibutuhkan untuk laporan laba-rugi --}}
            <div><label class="label" for="in-cost">Total harga beli (Rp)</label><input id="in-cost" name="total_cost" type="number" min="0" step="100" class="input num" placeholder="opsional"></div>
        </div>
        <div><label class="label" for="in-notes">Catatan</label><input id="in-notes" name="notes" class="input" placeholder="mis. Beli di Pasar Petisah"></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>

{{-- Stok opname --}}
<x-modal id="opname-form" title="Stok opname">
    <form method="POST" action="#" data-demo="Stok disesuaikan (demo)" class="space-y-5">@csrf
        <input type="hidden" name="type" value="adjustment">
        <div><label class="label" for="op-ing">Bahan baku</label>
            <select id="op-ing" name="ingredient_id" class="select" required onchange="document.getElementById('op-sys').textContent=this.selectedOptions[0].dataset.stock">
                @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-stock="{{ $fmt($ing->stock_qty) }} {{ $ing->unit }}">{{ $ing->name }}</option>@endforeach</select>
            <p class="help">Stok di sistem: <strong id="op-sys" class="text-bean">{{ $fmt($ingredients[0]->stock_qty ?? 0) }} {{ $ingredients[0]->unit ?? '' }}</strong></p></div>
        <div><label class="label" for="op-qty">Jumlah fisik sebenarnya</label><input id="op-qty" name="qty" type="number" step="0.01" min="0" class="input num" required></div>
        <div><label class="label" for="op-notes">Alasan selisih</label><input id="op-notes" name="notes" class="input" placeholder="mis. Tumpah, kadaluarsa, salah hitung"></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-primary">Sesuaikan stok</button></div>
    </form>
</x-modal>
<script>
    document.getElementById('mv-filter').addEventListener('click', (e) => {
        const b = e.target.closest('.chip'); if (!b) return;
        document.querySelectorAll('#mv-filter .chip').forEach(c => c.setAttribute('aria-pressed', c === b));
        document.querySelectorAll('#mv-rows tr').forEach(r => r.hidden = b.dataset.t !== 'all' && r.dataset.t !== b.dataset.t);
    });
</script>
@endpush
@endsection

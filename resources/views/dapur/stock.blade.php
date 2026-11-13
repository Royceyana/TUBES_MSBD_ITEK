@extends('layouts.app')
@section('title', 'Stok Bahan | Kafe Kaleng')
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<x-page-head title="Stok bahan" script="di dapur">
    <button type="button" class="btn btn-primary" data-modal-open="opname-form">Data stok fisik</button>
</x-page-head>

@if ($lowStock->isNotEmpty())
    <section class="band mt-10 px-6 py-5" role="alert">
        <p class="display text-2xl">{{ $lowStock->count() }} bahan menipis atau habis</p>
        <p class="mt-1 font-semibold">{{ $lowStock->pluck('name')->take(6)->join(', ') }}{{ $lowStock->count() > 6 ? ', dan lainnya' : '' }}</p>
        <p class="mt-1 text-sm">Beri tahu admin untuk mencatat stok masuk.</p>
    </section>
@endif

<section class="mt-12" aria-labelledby="stk-h">
    <h2 id="stk-h" class="script text-4xl">Stok saat ini</h2>
    @if ($ingredients->isEmpty())
        <p class="muted mt-4 text-lg">Belum ada bahan baku.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[32rem]">
                <thead><tr><th>Bahan</th><th class="r">Stok</th><th class="r">Minimum</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($ingredients as $i)
                        <tr><td class="font-semibold">{{ $i->name }}</td>
                            <td class="num r font-bold">{{ $fmt($i->stock_qty) }} <span class="font-normal text-muted">{{ $i->unit }}</span></td>
                            <td class="num r text-muted">{{ $fmt($i->min_stock) }}</td>
                            <td><span class="status {{ ['aman' => 'status-ok', 'menipis' => 'status-warn', 'habis' => 'status-bad'][$i->stock_status] }}">{{ ucfirst($i->stock_status) }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@push('modals')
<x-modal id="opname-form" title="Data stok fisik">
    <form method="POST" action="{{ route('dapur.stock.opname') }}" class="space-y-5">@csrf
        <div><label class="label" for="op-ing">Bahan baku</label>
            <select id="op-ing" name="ingredient_id" class="select" required onchange="document.getElementById('op-sys').textContent=this.selectedOptions[0].dataset.stock">
                @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-stock="{{ $fmt($ing->stock_qty) }} {{ $ing->unit }}">{{ $ing->name }}</option>@endforeach</select>
            <p class="help">Stok di sistem: <strong id="op-sys" class="text-cocoa">{{ isset($ingredients[0]) ? $fmt($ingredients[0]->stock_qty).' '.$ingredients[0]->unit : '-' }}</strong></p></div>
        <div><label class="label" for="op-qty">Jumlah fisik di dapur</label><input id="op-qty" name="qty" type="number" step="0.01" min="0" class="input num" required></div>
        <div><label class="label" for="op-notes">Catatan</label><input id="op-notes" name="notes" class="input" maxlength="255" placeholder="mis. Tumpah, kedaluwarsa, salah hitung"></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Simpan</button></div>
    </form>
</x-modal>
@endpush
@endsection

@extends('layouts.app')
@section('title', 'Laporan Penjualan | Kafe Kaleng')

@section('content')
<x-page-head title="Penjualan" script="per hari">
    <button type="button" onclick="window.print()" class="btn btn-line btn-sm">Cetak</button>
</x-page-head>
@include('owner._range', ['action' => route('owner.sales')])

<dl class="stats mt-8">
    <x-stat label="Total penjualan" :value="$sum['total']" :money="true" />
    <x-stat label="Jumlah transaksi" :value="$sum['count']" />
    <x-stat label="Rata-rata per transaksi" :value="$sum['avg']" :money="true" />
    <x-stat label="Diskon diberikan" :value="$sum['discount']" :money="true" />
</dl>

<div class="print-area mt-10 overflow-x-auto">
    @if ($rows->isEmpty())
        <p class="muted text-lg">Tidak ada penjualan lunas pada rentang ini.</p>
    @else
        <table class="tbl min-w-[30rem]">
            <thead><tr><th>Tanggal</th><th class="r">Transaksi</th><th class="r">Item terjual</th><th class="r">Diskon</th><th class="r">Penjualan bersih</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr><td class="font-semibold">{{ $r->date }}</td><td class="num r">{{ $r->count }}</td><td class="num r">{{ $r->items }}</td><td class="num r text-muted">{{ $r->discount ? number_format($r->discount, 0, ',', '.') : '-' }}</td><td class="num r font-bold">Rp {{ number_format($r->total, 0, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td>Total</td><td class="num r">{{ $sum['count'] }}</td><td class="num r">{{ $rows->sum('items') }}</td><td class="num r">{{ number_format($sum['discount'], 0, ',', '.') }}</td><td class="num r">Rp {{ number_format($sum['total'], 0, ',', '.') }}</td></tr></tfoot>
        </table>
    @endif
</div>
@endsection

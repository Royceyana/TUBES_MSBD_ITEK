@extends('layouts.app')
@section('title', 'Laporan Stok | Kafe Kaleng')
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<x-page-head title="Laporan stok" script="masuk & keluar">
    <button type="button" onclick="window.print()" class="btn btn-line btn-sm">Cetak</button>
</x-page-head>
@include('owner._range', ['action' => route('owner.stock')])

<div class="print-area mt-10 overflow-x-auto">
    @if ($rows->isEmpty())
        <p class="muted text-lg">Belum ada bahan baku.</p>
    @else
        <table class="tbl min-w-[40rem]">
            <thead><tr><th>Bahan</th><th class="r">Masuk</th><th class="r">Keluar</th><th class="r">Stok akhir</th><th class="r">Minimum</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr><td class="font-semibold">{{ $r->name }}</td><td class="num r">{{ $fmt($r->in) }}</td><td class="num r">{{ $fmt($r->out) }}</td>
                        <td class="num r font-bold">{{ $fmt($r->stock_qty) }} <span class="font-normal text-muted">{{ $r->unit }}</span></td><td class="num r text-muted">{{ $fmt($r->min_stock) }}</td>
                        <td><span class="status {{ ['aman' => 'status-ok', 'menipis' => 'status-warn', 'habis' => 'status-bad'][$r->stock_status] }}">{{ ucfirst($r->stock_status) }}</span></td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

@extends('layouts.app')
@section('title', 'Laporan Laba Kotor | Kafe Kaleng')
@php
    $net = $pl['gross'] - $pl['discount'];
    $profit = $net - $pl['cogs'];
    $margin = $net > 0 ? round($profit / $net * 100, 1) : 0;
@endphp

@section('content')
<x-page-head title="Laba kotor" script="penjualan dikurangi HPP">
    <button type="button" onclick="window.print()" class="btn btn-line btn-sm">Cetak</button>
</x-page-head>
@include('owner._range', ['action' => route('owner.profit')])

<dl class="stats mt-8">
    <x-stat label="Penjualan bersih" :value="$net" :money="true" :hint="'setelah diskon Rp '.number_format($pl['discount'], 0, ',', '.')" />
    <x-stat label="HPP (biaya bahan)" :value="$pl['cogs']" :money="true" hint="dari resep × harga bahan" />
    <x-stat label="Laba kotor" :value="$profit" :money="true" :hint="$margin.'% margin'" />
</dl>

<div class="print-area mt-10 overflow-x-auto">
    @if ($rows->isEmpty())
        <p class="muted text-lg">Tidak ada penjualan lunas pada rentang ini.</p>
    @else
        <table class="tbl min-w-[36rem]">
            <thead><tr><th>Menu</th><th class="r">Terjual</th><th class="r">Penjualan kotor</th><th class="r">HPP</th><th class="r">Margin</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    <tr><td class="font-semibold">{{ $r->name }}</td><td class="num r">{{ $r->qty }}</td><td class="num r">{{ number_format($r->revenue, 0, ',', '.') }}</td><td class="num r text-muted">{{ number_format($r->cogs, 0, ',', '.') }}</td><td class="num r font-bold">{{ number_format($r->revenue - $r->cogs, 0, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <p class="help mt-4">Angka per menu sebelum diskon; diskon dihitung di ringkasan atas. Menu tanpa resep dihitung HPP Rp 0, sehingga labanya tampak lebih besar. Lengkapi resep di halaman Menu &amp; Resep.</p>
    @endif
</div>
@endsection

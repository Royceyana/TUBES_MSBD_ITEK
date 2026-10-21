@extends('layouts.app')
@section('title', 'Laporan Penjualan — Kafe Kaleng')
@section('page-heading', 'Laporan Penjualan')
@section('actions')<a href="#" data-demo-export class="btn btn-primary btn-sm" onclick="toast('Mengunduh .xlsx (demo)');return false"><x-icon name="download" size="size-4" /> Export Excel</a>@endsection

@section('content')
<div class="space-y-6">
    <form method="GET" class="card card-pad rise flex flex-wrap items-end gap-3">
        <div><label class="label" for="f-mode">Periode</label><select id="f-mode" name="mode" class="select !w-40"><option>Harian</option><option>Bulanan</option></select></div>
        <div><label class="label" for="f-from">Dari</label><input id="f-from" type="date" name="from" class="input" value="{{ now()->startOfMonth()->toDateString() }}"></div>
        <div><label class="label" for="f-to">Sampai</label><input id="f-to" type="date" name="to" class="input" value="{{ now()->toDateString() }}"></div>
        <button class="btn btn-dark"><x-icon name="filter" size="size-4" /> Terapkan</button>
    </form>

    <section class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Total penjualan" :value="$sum['total']" :money="true" icon="wallet" :i="1" />
        <x-stat label="Jumlah transaksi" :value="$sum['count']" icon="receipt" tone="bean" :i="2" />
        <x-stat label="Rata-rata / transaksi" :value="$sum['avg']" :money="true" icon="chart" tone="leaf" :i="3" />
    </section>

    <div class="card rise overflow-hidden" style="--i:4">
        <div class="overflow-x-auto p-2 md:p-4">
            <table class="tbl min-w-[36rem]">
                <thead><tr><th>Tanggal</th><th class="r">Transaksi</th><th class="r">Item terjual</th><th class="r">Penjualan</th></tr></thead>
                <tbody>@foreach ($rows as $r)
                    <tr><td class="font-semibold">{{ $r->date }}</td><td class="num r">{{ $r->count }}</td><td class="num r">{{ $r->items }}</td><td class="num r font-bold">Rp {{ number_format($r->total, 0, ',', '.') }}</td></tr>
                @endforeach</tbody>
                <tfoot><tr><td class="font-bold">Total</td><td class="num r font-bold">{{ $sum['count'] }}</td><td class="num r font-bold">{{ collect($rows)->sum('items') }}</td><td class="num r text-base font-bold text-amber-deep">Rp {{ number_format($sum['total'], 0, ',', '.') }}</td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

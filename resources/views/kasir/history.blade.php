@extends('layouts.app')
@section('title', 'Riwayat Transaksi — Kafe Kaleng')
@section('page-heading', 'Riwayat Transaksi')

@section('content')
<div class="card rise overflow-hidden">
    <div class="flex flex-wrap items-center gap-3 p-4 md:p-5">
        <div class="relative min-w-52 flex-1 md:max-w-sm">
            <x-icon name="search" size="size-[1.1rem]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-bark" />
            <input type="search" data-search="#trx-rows tr" class="input !pl-11" placeholder="Cari nomor / menu…" aria-label="Cari transaksi">
        </div>
        <input type="date" class="input !w-auto" value="{{ now()->toDateString() }}" aria-label="Tanggal">
    </div>
    <div class="overflow-x-auto px-2 pb-3 md:px-4">
        <table class="tbl min-w-[40rem]">
            <thead><tr><th>No</th><th>Waktu</th><th>Item</th><th>Metode</th><th class="r">Total</th><th>Status</th><th class="r"></th></tr></thead>
            <tbody id="trx-rows">
                @foreach ($transactions as $t)
                    <tr>
                        <td class="num font-semibold">{{ $t->number }}</td>
                        <td class="whitespace-nowrap text-bark-dark">{{ $t->time }}</td>
                        <td class="max-w-64 truncate text-bark-dark">{{ $t->items }}</td>
                        <td>{{ $t->method }}</td>
                        <td class="num r font-bold">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                        <td><span class="badge {{ $t->status === 'paid' ? 'badge-ok' : 'badge-bad' }}">{{ $t->status === 'paid' ? 'Lunas' : 'Batal' }}</span></td>
                        <td class="r"><a href="{{ url('/kasir/receipt') }}" class="btn btn-ghost btn-sm"><x-icon name="printer" size="size-4" /> Struk</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Ringkasan Shift | Kafe Kaleng')

@section('content')
<x-page-head title="Ringkasan" script="shift hari ini">
    <a href="{{ route('kasir.pos') }}" class="btn btn-primary">Mulai transaksi</a>
</x-page-head>

<dl class="stats mt-10">
    <x-stat label="Transaksi lunas" :value="$stats['count']" />
    <x-stat label="Omzet shift" :value="$stats['revenue']" :money="true" />
    <x-stat label="Tunai" :value="$stats['cash']" :money="true" hint="uang di laci kasir" />
    <x-stat label="QRIS &amp; debit" :value="$stats['cashless']" :money="true" />
</dl>

@if ($pending > 0)
    <p class="band mt-10 flex flex-wrap items-center justify-between gap-3 px-5 py-4 font-semibold" role="status">
        <span>{{ $pending }} pesanan hari ini belum dibayar.</span>
        <a href="{{ route('kasir.history') }}" class="link">Lihat riwayat</a>
    </p>
@endif

<section class="mt-14" aria-labelledby="recent-h">
    <h2 id="recent-h" class="script text-4xl">Transaksi terakhir</h2>
    @if ($recent->isEmpty())
        <p class="muted mt-4 text-lg">Belum ada transaksi lunas pada shift ini.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[34rem]">
                <thead><tr><th>No</th><th>Waktu</th><th>Item</th><th>Bayar</th><th class="r">Total</th></tr></thead>
                <tbody>
                    @foreach ($recent as $t)
                        <tr><td class="num font-bold">{{ $t->number }}</td><td>{{ $t->time }}</td><td class="max-w-72 truncate text-muted">{{ $t->items }}</td><td>{{ $t->method }}</td><td class="num r font-bold">Rp {{ number_format($t->total, 0, ',', '.') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection

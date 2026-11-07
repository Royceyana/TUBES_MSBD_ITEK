@extends('layouts.app')
@section('title', 'Riwayat Transaksi | Kafe Kaleng')

@section('content')
<x-page-head title="Riwayat" script="transaksi" />

<div class="mt-10 flex flex-wrap items-end gap-4">
    <div class="min-w-52 flex-1 md:max-w-sm">
        <label for="q" class="label">Cari</label>
        <input id="q" type="search" data-search="#trx-rows tr" data-empty="#trx-none" class="input" placeholder="Nomor atau menu">
    </div>
    <form method="GET" action="{{ route('kasir.history') }}">
        <label for="date" class="label">Tanggal</label>
        <input id="date" type="date" name="date" class="input !w-auto" value="{{ $date }}" onchange="this.form.submit()">
    </form>
</div>

<div class="mt-8 overflow-x-auto">
    <table class="tbl min-w-[48rem]">
        <thead><tr><th>No</th><th>Waktu</th><th>Meja</th><th>Item</th><th>Bayar</th><th class="r">Total</th><th>Status</th><th>Dapur</th><th></th></tr></thead>
        <tbody id="trx-rows">
            @foreach ($transactions as $t)
                <tr>
                    <td class="num font-bold">{{ $t->number }}</td>
                    <td>{{ $t->time }}</td>
                    <td>{{ $t->table ?: '-' }}</td>
                    <td class="max-w-64 truncate text-muted">{{ $t->items }}</td>
                    <td>{{ $t->method }}</td>
                    <td class="num r font-bold">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                    <td><span class="status {{ ['completed' => 'status-ok', 'pending' => 'status-warn', 'cancelled' => 'status-bad'][$t->status] ?? 'status-mute' }}">{{ \App\Models\Transaction::STATUS_LABEL[$t->status] ?? $t->status }}</span></td>
                    <td class="text-muted">{{ $t->status === 'completed' ? (\App\Models\Transaction::ORDER_LABEL[$t->order_status] ?? '-') : '-' }}</td>
                    <td class="r">
                        @if ($t->status === 'completed')<a href="{{ route('kasir.orders.receipt', $t->id) }}" class="link text-sm">Struk</a>
                        @elseif ($t->status === 'pending')<a href="{{ route('kasir.orders.pay', $t->id) }}" class="btn btn-primary btn-sm">Bayar</a>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p id="trx-none" class="muted mt-8 text-lg" @if ($transactions->count()) hidden @endif>{{ $transactions->count() ? 'Tidak ada yang cocok.' : 'Belum ada transaksi pada tanggal ini.' }}</p>
@endsection

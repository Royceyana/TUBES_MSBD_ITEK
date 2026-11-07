@extends('layouts.app')
@section('title', 'Struk '.$trx->number.' | Kafe Kaleng')

@section('content')
<x-page-head title="Lunas" :script="$trx->number">
    <button type="button" onclick="window.print()" class="btn btn-line btn-sm">Cetak struk</button>
    <a href="{{ route('kasir.pos') }}" class="btn btn-primary btn-sm">Transaksi baru</a>
</x-page-head>

<div class="mt-10 grid gap-12 md:grid-cols-[1fr_22rem] md:items-start">
    <section class="md:order-2">
        <h2 class="script text-4xl">Pembayaran berhasil</h2>
        @if ($trx->change !== null)
            <p class="mt-6 text-sm font-bold text-muted">Kembalian</p>
            <p class="display num text-6xl">Rp {{ number_format($trx->change, 0, ',', '.') }}</p>
        @endif
        <p class="mt-6 max-w-sm text-muted">Pesanan sudah masuk antrian dapur. Stok bahan berkurang otomatis setelah dapur menandai pesanan siap, lalu pelayan mengantarnya.</p>
    </section>

    <article class="print-area receipt mx-auto w-full max-w-[22rem] border border-sand-2 p-6 md:order-1" aria-label="Struk pembayaran">
        <div class="text-center">
            <p class="text-base font-bold tracking-widest">KAFE KALENG</p>
            <p>Jl. Jamin Ginting Gg. Senina No.11</p><p>Medan 20155</p>
        </div>
        <hr>
        <div class="flex justify-between"><span>No</span><span>{{ $trx->number }}</span></div>
        <div class="flex justify-between"><span>Tanggal</span><span>{{ $trx->date }}</span></div>
        <div class="flex justify-between"><span>Kasir</span><span>{{ $trx->cashier }}</span></div>
        <div class="flex justify-between"><span>Meja</span><span>{{ $trx->table ?: 'Bawa pulang' }}</span></div>
        <hr>
        @foreach ($trx->items as $it)
            <div>{{ $it->name }}</div>
            <div class="flex justify-between"><span>&nbsp;&nbsp;{{ $it->qty }} x {{ number_format($it->price, 0, ',', '.') }}</span><span>{{ number_format($it->subtotal, 0, ',', '.') }}</span></div>
        @endforeach
        <hr>
        @if ($trx->discount > 0)
            <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($trx->subtotal, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span>{{ $trx->discount_label }}</span><span>-{{ number_format($trx->discount, 0, ',', '.') }}</span></div>
        @endif
        <div class="flex justify-between text-sm font-bold"><span>TOTAL</span><span>{{ number_format($trx->total, 0, ',', '.') }}</span></div>
        @if ($trx->paid !== null)
            <div class="flex justify-between"><span>{{ $trx->method }}</span><span>{{ number_format($trx->paid, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span>Kembali</span><span>{{ number_format($trx->change, 0, ',', '.') }}</span></div>
        @else
            <div class="flex justify-between"><span>Bayar</span><span>{{ $trx->method }}</span></div>
        @endif
        <hr>
        <p class="text-center">Terima kasih sudah mampir!</p>
        <p class="text-center">IG @kafekaleng_medan</p>
    </article>
</div>
@endsection

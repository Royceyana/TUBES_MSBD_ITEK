@extends('layouts.app')
@section('title', 'Struk '.$trx->number.' — Kafe Kaleng')
@section('page-heading', 'Struk Pembayaran')
@section('actions')
    <button onclick="window.print()" class="btn btn-dark btn-sm"><x-icon name="printer" size="size-4" /> Cetak</button>
    <a href="{{ url('/kasir/pos') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="size-4" /> Transaksi baru</a>
@endsection

@section('content')
<div class="mx-auto grid max-w-3xl gap-8 md:grid-cols-[1fr_20rem] md:items-start">
    <div class="rise rounded-3xl bg-leaf-soft p-6 text-leaf md:order-2">
        <span class="grid size-12 place-items-center rounded-full bg-leaf text-white"><x-icon name="check" size="size-6" /></span>
        <h2 class="display mt-4 text-3xl text-bean">Pembayaran berhasil</h2>
        <p class="mt-1 text-sm text-bark-dark">Stok bahan baku sudah otomatis berkurang sesuai resep.</p>
        <p class="num mt-5 text-sm font-semibold">Kembalian</p>
        <p class="num display text-4xl text-bean">Rp {{ number_format($trx->change, 0, ',', '.') }}</p>
    </div>

    {{-- Struk — hanya area ini yang dicetak (80mm) --}}
    <article class="print-area receipt rise mx-auto w-full max-w-[22rem] rounded-xl p-6 shadow-xl ring-1 ring-black/5 md:order-1" style="--i:1">
        <div class="text-center">
            <p class="text-base font-bold tracking-widest">KAFE KALENG</p>
            <p>Jl. Jamin Ginting Gg. Senina No.11</p><p>Medan 20155</p>
        </div>
        <hr>
        <div class="flex justify-between"><span>No</span><span>{{ $trx->number }}</span></div>
        <div class="flex justify-between"><span>Tanggal</span><span>{{ $trx->date }}</span></div>
        <div class="flex justify-between"><span>Kasir</span><span>{{ $trx->cashier }}</span></div>
        <hr>
        @foreach ($trx->items as $it)
            <div>{{ $it->name }}</div>
            <div class="flex justify-between"><span>&nbsp;&nbsp;{{ $it->qty }} x {{ number_format($it->price, 0, ',', '.') }}</span><span>{{ number_format($it->qty * $it->price, 0, ',', '.') }}</span></div>
        @endforeach
        <hr>
        <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($trx->subtotal, 0, ',', '.') }}</span></div>
        @if ($trx->discount)<div class="flex justify-between"><span>Diskon</span><span>-{{ number_format($trx->discount, 0, ',', '.') }}</span></div>@endif
        <div class="flex justify-between text-sm font-bold"><span>TOTAL</span><span>{{ number_format($trx->total, 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span>{{ $trx->method }}</span><span>{{ number_format($trx->paid, 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span>Kembali</span><span>{{ number_format($trx->change, 0, ',', '.') }}</span></div>
        <hr>
        <p class="text-center">Terima kasih sudah mampir!</p>
        <p class="text-center">IG @kafekaleng_medan</p>
    </article>
</div>
@endsection

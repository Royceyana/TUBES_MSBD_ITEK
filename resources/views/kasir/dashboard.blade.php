@extends('layouts.app')
@section('title', 'Ringkasan Shift — Kafe Kaleng')
@section('page-heading', 'Ringkasan Shift')
@section('actions')<a href="{{ url('/kasir/pos') }}" class="btn btn-primary btn-sm"><x-icon name="cart" size="size-4" /> Buka kasir</a>@endsection

@section('content')
<div class="space-y-6">
    <section class="card-dark sunburst relative isolate overflow-hidden p-6 md:p-8 rise">
        <p class="eyebrow !text-sand">Halo, {{ auth()->user()->name ?? 'Dimas' }}</p>
        <h2 class="display mt-2 max-w-md text-4xl text-cream md:text-5xl">Siap melayani pelanggan hari ini?</h2>
        <a href="{{ url('/kasir/pos') }}" class="btn btn-primary btn-lg mt-6">Mulai transaksi <x-icon name="arrow" size="size-5" /></a>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Transaksi" :value="$stats['count']" icon="receipt" :i="1" hint="shift ini" />
        <x-stat label="Omzet shift" :value="$stats['revenue']" :money="true" icon="trend" tone="leaf" :i="2" />
        <x-stat label="Tunai" :value="$stats['cash']" :money="true" icon="cash" tone="bean" :i="3" hint="uang di laci kasir" />
        <x-stat label="Non-tunai" :value="$stats['cashless']" :money="true" icon="wallet" :i="4" hint="QRIS & debit" />
    </section>

    <section class="card card-pad rise" style="--i:5">
        <h2 class="text-xl font-bold">Transaksi terakhir</h2>
        <ul class="mt-3 divide-y divide-bark/10">
            @foreach ($recent as $t)
                <li class="flex items-center gap-4 py-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-parchment text-bark-dark"><x-icon name="receipt" size="size-[1.1rem]" /></span>
                    <div class="min-w-0 flex-1"><p class="num text-sm font-semibold">{{ $t->number }} · {{ $t->method }}</p><p class="truncate text-xs text-bark">{{ $t->items }}</p></div>
                    <div class="text-right"><p class="num font-bold">Rp {{ number_format($t->total, 0, ',', '.') }}</p><p class="text-xs text-bark">{{ $t->time }}</p></div>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection

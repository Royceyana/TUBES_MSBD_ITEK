@extends('layouts.app')
@section('title', 'Dashboard Owner | Kafe Kaleng')

@php
    $max = max(1, max($chart['data']));
    $n = count($chart['data']);
    $W = 840; $H = 260; $padB = 34; $slot = $W / $n; $bw = $slot * .62;
@endphp

@section('content')
<x-page-head title="Dashboard" script="pemilik">
    <a href="{{ route('owner.sales') }}" class="btn btn-line btn-sm">Laporan penjualan</a>
</x-page-head>

<dl class="stats mt-10">
    <x-stat label="Penjualan hari ini" :value="$stats['today']" :money="true" :hint="$stats['today_delta']" />
    <x-stat label="Penjualan bulan ini" :value="$stats['month']" :money="true" />
    <x-stat label="Transaksi bulan ini" :value="$stats['count']" />
    <x-stat label="Bahan stok kritis" :value="count($critical)" hint="di bawah batas minimum" />
</dl>

<div class="mt-14 grid gap-12 xl:grid-cols-[1.7fr_1fr] xl:gap-0">
    <section class="xl:pr-12" aria-labelledby="ch-h">
        <h2 id="ch-h" class="script text-4xl">Penjualan 14 hari terakhir</h2>
        @if (array_sum($chart['data']) === 0)
            <p class="muted mt-4 text-lg">Belum ada penjualan lunas dalam 14 hari terakhir.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <svg viewBox="0 0 {{ $W }} {{ $H }}" class="min-w-[34rem] w-full text-clay" role="img" aria-labelledby="ch-t">
                    <title id="ch-t">Grafik batang penjualan harian. Tertinggi {{ 'Rp '.number_format($max, 0, ',', '.') }}.</title>
                    <line x1="0" x2="{{ $W }}" y1="{{ $H - $padB }}" y2="{{ $H - $padB }}" stroke="var(--color-cocoa)" stroke-width="2"/>
                    @foreach ($chart['data'] as $k => $v)
                        @php $h = ($H - $padB - 16) * $v / $max; $x = $k * $slot + ($slot - $bw) / 2; @endphp
                        <rect x="{{ $x }}" y="{{ $H - $padB - $h }}" width="{{ $bw }}" height="{{ max($h, $v > 0 ? 2 : 0) }}" fill="currentColor" rx="2"><title>{{ $chart['labels'][$k] }}: Rp {{ number_format($v, 0, ',', '.') }}</title></rect>
                        @if ($k % 2 === ($n - 1) % 2)<text x="{{ $x + $bw / 2 }}" y="{{ $H - 12 }}" text-anchor="middle" font-size="12" fill="var(--color-muted)">{{ $chart['labels'][$k] }}</text>@endif
                    @endforeach
                </svg>
            </div>
        @endif
    </section>

    <section class="xl:border-l xl:border-sand-2 xl:pl-12" aria-labelledby="top-h">
        <h2 id="top-h" class="script text-4xl">Terlaris bulan ini</h2>
        @if ($top->isEmpty())
            <p class="muted mt-4 text-lg">Belum ada penjualan bulan ini.</p>
        @else
            <ol class="mt-4">
                @foreach ($top as $k => $p)
                    <li class="border-b border-sand py-3">
                        <div class="flex items-baseline gap-3"><span class="display w-6 text-lg">{{ $k + 1 }}</span><span class="flex-1 font-semibold">{{ $p->name }}</span><span class="num font-extrabold">{{ $p->sold }}<span class="font-normal text-muted"> terjual</span></span></div>
                        <div class="ml-9 mt-2 h-1 bg-sand"><div class="h-full bg-clay" style="width: {{ $p->sold / $top[0]->sold * 100 }}%"></div></div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>

@if ($critical->isNotEmpty())
<section class="band mt-14 px-6 py-6" aria-labelledby="crit-h">
    <div class="flex items-baseline justify-between gap-3"><h2 id="crit-h" class="display text-2xl">Stok kritis</h2><a href="{{ route('owner.stock') }}" class="link text-sm">Laporan stok</a></div>
    <ul class="mt-3 grid gap-x-10 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($critical as $c)
            <li class="menu-line"><span class="font-semibold">{{ $c->name }}</span><span class="dots" style="border-color: var(--color-cocoa)" aria-hidden="true"></span><span class="num font-extrabold">{{ $c->stock_qty + 0 }} {{ $c->unit }}</span></li>
        @endforeach
    </ul>
</section>
@endif
@endsection

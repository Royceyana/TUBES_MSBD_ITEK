@extends('layouts.app')
@section('title', 'Laba-Rugi — Kafe Kaleng')
@section('page-heading', 'Laba-Rugi Sederhana')
@section('actions')<a href="#" class="btn btn-primary btn-sm" onclick="toast('Mengunduh .xlsx (demo)');return false"><x-icon name="download" size="size-4" /> Export Excel</a>@endsection

@section('content')
@php $profit = $pl['sales'] - $pl['purchases']; $margin = $pl['sales'] ? $profit / $pl['sales'] * 100 : 0; @endphp
<div class="space-y-6">
    <form method="GET" class="card card-pad rise flex flex-wrap items-end gap-3">
        <div><label class="label" for="p-m">Bulan</label><input id="p-m" type="month" name="month" class="input" value="{{ now()->format('Y-m') }}"></div>
        <button class="btn btn-dark"><x-icon name="filter" size="size-4" /> Terapkan</button>
    </form>

    <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
        <section class="card card-pad rise" style="--i:1">
            <p class="eyebrow">Rincian</p><h2 class="mt-1 text-xl font-bold">Perhitungan bulan ini</h2>
            <dl class="num mt-5 space-y-4">
                <div class="flex items-center justify-between"><dt class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-leaf-soft text-leaf"><x-icon name="arrow-down" size="size-4" /></span> Total penjualan</dt><dd class="font-bold">Rp {{ number_format($pl['sales'], 0, ',', '.') }}</dd></div>
                <div class="flex items-center justify-between"><dt class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-wine-soft text-wine"><x-icon name="arrow-up" size="size-4" /></span> Pembelian bahan baku</dt><dd class="font-bold text-wine">− Rp {{ number_format($pl['purchases'], 0, ',', '.') }}</dd></div>
                <div class="flex items-baseline justify-between border-t-2 border-dashed border-bark/25 pt-4"><dt class="text-lg font-bold">Laba bersih</dt><dd class="display text-3xl {{ $profit >= 0 ? 'text-leaf' : 'text-wine' }}">Rp {{ number_format($profit, 0, ',', '.') }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-bark">Laba = penjualan − pembelian bahan baku (stock_movements tipe <code>in</code>). Belum termasuk biaya operasional lain.</p>
        </section>

        <section class="card-dark sunburst relative isolate flex flex-col justify-between overflow-hidden p-6 rise" style="--i:2">
            <p class="eyebrow !text-sand">Margin laba</p>
            <div><p class="display num text-7xl text-amber">{{ number_format($margin, 1, ',', '.') }}<span class="text-4xl">%</span></p>
            <p class="mt-3 max-w-xs text-sm text-cream/70">Dari setiap Rp 100.000 penjualan, sekitar Rp {{ number_format($margin * 1000, 0, ',', '.') }} menjadi laba.</p></div>
            <div class="mt-8 h-3 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-amber" style="width: {{ max(2, min(100, $margin)) }}%"></div></div>
        </section>
    </div>
</div>
@endsection

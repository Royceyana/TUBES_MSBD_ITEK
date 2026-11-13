@extends('layouts.app')
@section('title', 'Antrian Dapur | Kafe Kaleng')
@push('head')<meta http-equiv="refresh" content="30">@endpush

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    $groups = [
        'waiting' => ['Menunggu', 'Mulai masak'],
        'preparing' => ['Sedang dimasak', 'Tandai siap'],
    ];
@endphp

@section('content')
<x-page-head title="Dapur" script="antrian pesanan">
    <span class="muted text-sm">Halaman menyegarkan diri tiap 30 detik</span>
</x-page-head>

@if ($queue->isEmpty())
    <p class="band mt-10 px-5 py-6 text-lg font-semibold" role="status">Tidak ada pesanan yang menunggu. Pesanan baru muncul setelah kasir menerima pembayaran.</p>
@endif

<div class="mt-10 grid gap-12 lg:grid-cols-2 lg:gap-0">
    @foreach ($groups as $status => [$title, $action])
        @php $orders = $queue->where('order_status', $status); @endphp
        <section class="{{ $loop->last ? 'lg:border-l lg:border-sand-2 lg:pl-12' : 'lg:pr-12' }}" aria-labelledby="g-{{ $status }}">
            <h2 id="g-{{ $status }}" class="script border-b-2 border-cocoa pb-2 text-4xl">{{ $title }} <span class="num text-2xl text-muted">({{ $orders->count() }})</span></h2>
            @forelse ($orders as $t)
                <article class="border-b border-sand-2 py-6">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="display text-2xl">{{ $t->number }}</h3>
                        <p class="text-sm font-bold text-muted">{{ $t->table_no ? 'Meja '.$t->table_no : 'Bawa pulang' }} · {{ $t->transaction_date?->format('H:i') }}</p>
                    </div>
                    <ul class="mt-3 space-y-1.5 text-lg">
                        @foreach ($t->details as $d)
                            <li class="flex gap-3"><span class="num w-9 font-extrabold text-clay">{{ $d->qty }}×</span><span>{{ $d->menu->name ?? 'Menu dihapus' }}</span></li>
                        @endforeach
                    </ul>
                    @php $short = $shortages[$t->id] ?? []; $warnReady = $status === 'preparing' && $short; @endphp
                    @if ($short)
                        <div class="band mt-4 px-4 py-3" role="alert">
                            <p class="font-extrabold">Stok bahan kurang</p>
                            <ul class="mt-1 text-sm font-semibold">
                                @foreach ($short as $s)
                                    <li>{{ $s->name }}: perlu {{ $fmt($s->needed) }} {{ $s->unit }}, stok {{ $fmt($s->stock) }} {{ $s->unit }} (kurang {{ $fmt($s->short) }})</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('dapur.stock') }}" class="link mt-2 inline-block text-sm font-bold">Cek stok bahan</a>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('dapur.advance', $t) }}" class="mt-5"
                          @if ($warnReady) data-confirm="Stok {{ collect($short)->pluck('name')->join(', ') }} tidak cukup. Menandai siap akan memotong stok sehingga bisa menjadi minus. Lanjutkan?" data-confirm-yes="Ya, tandai siap" @endif>@csrf
                        @if ($warnReady)<input type="hidden" name="confirm_shortage" value="1">@endif
                        <button class="btn {{ $status === 'waiting' ? 'btn-line' : 'btn-primary' }} btn-lg w-full sm:w-auto">{{ $action }}</button>
                    </form>
                </article>
            @empty
                <p class="muted py-6">Kosong.</p>
            @endforelse
        </section>
    @endforeach
</div>

@if ($done->isNotEmpty())
    <section class="mt-14" aria-labelledby="g-done">
        <h2 id="g-done" class="script text-4xl">Siap, menunggu diantar</h2>
        <ul class="mt-3 divide-y divide-sand">
            @foreach ($done as $t)
                <li class="flex flex-wrap gap-x-6 gap-y-1 py-3"><span class="num font-bold">{{ $t->number }}</span><span class="muted">{{ $t->table_no ? 'Meja '.$t->table_no : 'Bawa pulang' }}</span><span class="text-muted">{{ $t->details->map(fn ($d) => $d->qty.'× '.($d->menu->name ?? '-'))->implode(', ') }}</span></li>
            @endforeach
        </ul>
    </section>
@endif
@endsection

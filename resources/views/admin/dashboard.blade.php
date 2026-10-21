@extends('layouts.app')
@section('title', 'Dashboard Admin — Kafe Kaleng')
@section('page-heading', 'Dashboard')
@section('actions')
    <a href="{{ url('/admin/stock') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="size-4" /> Stok masuk</a>
@endsection

@section('content')
<div class="space-y-6">
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Menu aktif" :value="$stats['menus']" icon="coffee" hint="di {{ $stats['categories'] }} kategori" :i="0" />
        <x-stat label="Bahan baku" :value="$stats['ingredients']" icon="flask" tone="bean" hint="terdaftar di gudang" :i="1" />
        <x-stat label="Stok menipis" :value="count($lowStock)" icon="alert" tone="wine" hint="perlu segera dibeli" :i="2" />
        <x-stat label="Transaksi hari ini" :value="$stats['today']" icon="receipt" tone="leaf" hint="dari semua kasir" :i="3" />
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.3fr_1fr]">
        {{-- Stok menipis --}}
        <section class="card card-pad rise" style="--i:4">
            <div class="flex items-center justify-between">
                <div><p class="eyebrow">Perlu perhatian</p><h2 class="mt-1 text-xl font-bold">Stok menipis</h2></div>
                <a href="{{ url('/admin/ingredients') }}" class="text-sm font-semibold text-amber-deep hover:underline">Lihat semua</a>
            </div>
            @forelse ($lowStock as $ing)
                @php $pct = min(100, $ing->min_stock > 0 ? ($ing->stock_qty / ($ing->min_stock * 2)) * 100 : 0); $empty = $ing->stock_qty <= 0; @endphp
                <div class="mt-4 flex items-center gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="truncate font-semibold">{{ $ing->name }}</span>
                            <span class="num shrink-0 text-bark-dark">{{ rtrim(rtrim(number_format($ing->stock_qty, 2, ',', '.'), '0'), ',') }} / min {{ rtrim(rtrim(number_format($ing->min_stock, 2, ',', '.'), '0'), ',') }} {{ $ing->unit }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-parchment"><div class="h-full rounded-full {{ $empty ? 'bg-wine' : 'bg-terra' }}" style="width: {{ max(4, $pct) }}%"></div></div>
                    </div>
                    <span class="badge {{ $empty ? 'badge-bad' : 'badge-warn' }}">{{ $empty ? 'Habis' : 'Menipis' }}</span>
                </div>
            @empty
                <div class="mt-6 rounded-2xl bg-leaf-soft p-5 text-center text-sm font-medium text-leaf">Semua stok aman 🎉</div>
            @endforelse
        </section>

        {{-- Aktivitas stok terbaru --}}
        <section class="card card-pad rise" style="--i:5">
            <p class="eyebrow">Terbaru</p><h2 class="mt-1 text-xl font-bold">Pergerakan stok</h2>
            <ul class="mt-4 divide-y divide-bark/10">
                @foreach ($movements as $m)
                    <li class="flex items-center gap-3 py-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $m->type === 'in' ? 'bg-leaf-soft text-leaf' : ($m->type === 'out' ? 'bg-amber-soft text-amber-deep' : 'bg-parchment text-bark-dark') }}">
                            <x-icon :name="$m->type === 'in' ? 'arrow-down' : ($m->type === 'out' ? 'arrow-up' : 'clipboard')" size="size-4" />
                        </span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $m->ingredient }}</p><p class="truncate text-xs text-bark">{{ $m->notes }}</p></div>
                        <div class="text-right"><p class="num text-sm font-bold">{{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '−' : '±') }}{{ $m->qty }} {{ $m->unit }}</p><p class="text-xs text-bark">{{ $m->time }}</p></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Pintasan --}}
    <section class="grid gap-4 sm:grid-cols-3">
        @foreach ([['Tambah menu', 'Buat menu baru dan atur resepnya', 'coffee', '/admin/menus'], ['Catat stok masuk', 'Setelah belanja bahan baku', 'package', '/admin/stock'], ['Stok opname', 'Samakan stok sistem dengan fisik', 'clipboard', '/admin/stock']] as $i => [$t, $d, $ic, $u])
            <a href="{{ url($u) }}" class="card card-pad group rise flex items-center gap-4 transition duration-300 hover:-translate-y-0.5 hover:shadow-lg active:scale-[.98]" style="--i:{{ $i + 6 }}">
                <span class="grid size-12 place-items-center rounded-2xl bg-bean text-amber"><x-icon :name="$ic" /></span>
                <span class="flex-1"><span class="block font-bold">{{ $t }}</span><span class="text-xs text-bark">{{ $d }}</span></span>
                <x-icon name="arrow" size="size-4" class="text-bark transition group-hover:translate-x-1" />
            </a>
        @endforeach
    </section>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Dashboard Admin | Kafe Kaleng')

@section('content')
<x-page-head title="Dashboard" script="admin kafe" />

<dl class="stats mt-10">
    <x-stat label="Menu aktif" :value="$stats['menus']" />
    <x-stat label="Kategori" :value="$stats['categories']" />
    <x-stat label="Bahan baku" :value="$stats['ingredients']" />
    <x-stat label="Transaksi lunas hari ini" :value="$stats['today']" />
</dl>

@if ($lowStock->isNotEmpty())
    <section class="band mt-10 flex flex-wrap items-center justify-between gap-4 px-6 py-5" role="alert">
        <div>
            <p class="display text-2xl">{{ $lowStock->count() }} bahan perlu diisi ulang</p>
            <p class="mt-1 font-semibold">{{ $lowStock->pluck('name')->take(5)->join(', ') }}{{ $lowStock->count() > 5 ? ', dan lainnya' : '' }}</p>
        </div>
        <a href="{{ route('admin.stock') }}" class="btn btn-dark">Catat stok masuk</a>
    </section>
@endif

<section class="mt-14" aria-labelledby="mv-h">
    <div class="flex items-end justify-between gap-3"><h2 id="mv-h" class="script text-4xl">Pergerakan stok terbaru</h2><a href="{{ route('admin.stock') }}" class="link text-sm">Semua riwayat</a></div>
    @if ($movements->isEmpty())
        <p class="muted mt-4 text-lg">Belum ada pergerakan stok. Mulai dengan mencatat stok masuk.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[32rem]">
                <thead><tr><th>Waktu</th><th>Bahan</th><th>Jenis</th><th class="r">Jumlah</th><th>Catatan</th></tr></thead>
                <tbody>
                    @foreach ($movements as $m)
                        <tr><td class="whitespace-nowrap text-muted">{{ $m->time }}</td><td class="font-semibold">{{ $m->ingredient }}</td>
                            <td><span class="status {{ ['in' => 'status-ok', 'out' => 'status-warn'][$m->type] ?? 'status-mute' }}">{{ ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Opname'][$m->type] ?? $m->type }}</span></td>
                            <td class="num r font-bold">{{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '−' : '') }}{{ rtrim(rtrim(number_format($m->qty, 2, ',', '.'), '0'), ',') }} <span class="font-normal text-muted">{{ $m->unit }}</span></td>
                            <td class="max-w-64 truncate text-muted">{{ $m->notes }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection

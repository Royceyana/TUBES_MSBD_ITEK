@extends('layouts.app')
@section('title', 'Dashboard Owner — Kafe Kaleng')
@section('page-heading', 'Dashboard Owner')
@section('actions')<a href="{{ url('/owner/reports/sales') }}" class="btn btn-dark btn-sm"><x-icon name="file" size="size-4" /> Laporan</a>@endsection

@section('content')
<div class="space-y-6">
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Penjualan hari ini" :value="$stats['today']" :money="true" icon="wallet" :i="0" hint="{{ $stats['today_delta'] }} dibanding kemarin" />
        <x-stat label="Penjualan bulan ini" :value="$stats['month']" :money="true" icon="trend" tone="leaf" :i="1" />
        <x-stat label="Transaksi bulan ini" :value="$stats['count']" icon="receipt" tone="bean" :i="2" />
        <x-stat label="Stok kritis" :value="count($critical)" icon="alert" tone="wine" :i="3" hint="bahan di bawah batas minimum" />
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
        <section class="card card-pad rise" style="--i:4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><p class="eyebrow">Tren</p><h2 class="mt-1 text-xl font-bold">Penjualan harian</h2></div>
                <div class="flex gap-2" id="range" role="group" aria-label="Rentang"><button class="chip" aria-pressed="true" data-d="7">7 hari</button><button class="chip" aria-pressed="false" data-d="14">14 hari</button></div>
            </div>
            <div class="relative mt-5 h-72"><canvas id="salesChart" role="img" aria-label="Grafik penjualan harian"></canvas></div>
        </section>

        <section class="card card-pad rise" style="--i:5">
            <p class="eyebrow">Peringkat</p><h2 class="mt-1 text-xl font-bold">Produk terlaris</h2>
            <ol class="mt-4 space-y-3">
                @foreach ($top as $k => $p)
                    <li class="flex items-center gap-3">
                        <span class="display grid size-9 place-items-center rounded-xl {{ $k === 0 ? 'bg-amber text-ink' : 'bg-parchment text-bark-dark' }}">{{ $k + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $p->name }}</p>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-parchment"><div class="h-full rounded-full bg-amber" style="width: {{ $p->sold / $top[0]->sold * 100 }}%"></div></div>
                        </div>
                        <span class="num text-sm font-bold">{{ $p->sold }}<span class="font-normal text-bark"> terjual</span></span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <section class="card card-pad rise" style="--i:6">
        <div class="flex items-center justify-between"><h2 class="text-xl font-bold">Status stok kritis</h2><a href="{{ url('/owner/reports/stock') }}" class="text-sm font-semibold text-amber-deep hover:underline">Laporan stok</a></div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($critical as $c)
                <div class="rounded-2xl border {{ $c->stock_qty <= 0 ? 'border-wine/30 bg-wine-soft' : 'border-terra/25 bg-amber-soft' }} p-4">
                    <p class="truncate font-semibold">{{ $c->name }}</p>
                    <p class="num mt-1 text-2xl font-bold {{ $c->stock_qty <= 0 ? 'text-wine' : 'text-terra' }}">{{ $c->stock_qty + 0 }} <span class="text-sm font-medium">{{ $c->unit }}</span></p>
                    <p class="text-xs text-bark-dark">minimum {{ $c->min_stock + 0 }} {{ $c->unit }}</p>
                </div>
            @endforeach
        </div>
    </section>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    const sales = @json($chart); // { labels: [...], data: [...] }  ← isi dari DashboardController
    const css = getComputedStyle(document.documentElement);
    const amber = css.getPropertyValue('--color-amber-deep').trim() || '#d9822b';
    const ctx = document.getElementById('salesChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 300); grad.addColorStop(0, 'rgba(237,162,74,.45)'); grad.addColorStop(1, 'rgba(237,162,74,0)');
    Chart.defaults.font.family = "'Instrument Sans', system-ui, sans-serif";
    Chart.defaults.color = '#7b654d';
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const chart = new Chart(ctx, {
        type: 'line',
        data: { labels: sales.labels, datasets: [{ data: sales.data, borderColor: amber, backgroundColor: grad, fill: true, tension: .4, borderWidth: 3, pointRadius: 0, pointHoverRadius: 6, pointHoverBackgroundColor: '#1b130c' }] },
        options: {
            maintainAspectRatio: false, animation: reduce ? false : { duration: 900, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1b130c', padding: 12, cornerRadius: 12, displayColors: false, callbacks: { label: (c) => rupiah(c.parsed.y) } } },
            scales: { x: { grid: { display: false }, border: { display: false } }, y: { beginAtZero: true, border: { display: false }, grid: { color: 'rgba(123,101,77,.12)' }, ticks: { callback: (v) => v >= 1e6 ? (v / 1e6) + ' jt' : (v / 1e3) + ' rb' } } },
        },
    });
    document.getElementById('range').addEventListener('click', (e) => {
        const b = e.target.closest('.chip'); if (!b) return;
        document.querySelectorAll('#range .chip').forEach(c => c.setAttribute('aria-pressed', c === b));
        const n = Number(b.dataset.d); chart.data.labels = sales.labels.slice(-n); chart.data.datasets[0].data = sales.data.slice(-n); chart.update();
    });
</script>
@endpush
@endsection

@extends('layouts.app')
@section('title', 'Manajemen Stok | Kafe Kaleng')
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<x-page-head title="Stok" script="bahan baku">
    <button type="button" class="btn btn-line" data-modal-open="opname-form">Stok opname</button>
    <button type="button" class="btn btn-primary" data-modal-open="in-form">Stok masuk</button>
</x-page-head>

@if ($lowStock->isNotEmpty())
    <section class="band mt-10 flex flex-wrap items-center justify-between gap-4 px-6 py-5" role="alert">
        <div>
            <p class="display text-2xl">{{ $lowStock->count() }} bahan hampir habis</p>
            <p class="mt-1 font-semibold">{{ $lowStock->pluck('name')->take(6)->join(', ') }}{{ $lowStock->count() > 6 ? ', dan lainnya' : '' }}</p>
        </div>
        <button type="button" class="btn btn-dark" data-modal-open="in-form">Catat pembelian</button>
    </section>
@endif

<section class="mt-12" aria-labelledby="stk-h">
    <h2 id="stk-h" class="script text-4xl">Stok saat ini</h2>
    @if ($ingredients->isEmpty())
        <p class="muted mt-4 text-lg">Belum ada bahan baku. Tambahkan dulu di halaman Bahan baku.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[32rem]">
                <thead><tr><th>Bahan</th><th class="r">Stok</th><th class="r">Minimum</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($ingredients as $i)
                        <tr><td class="font-semibold">{{ $i->name }}</td>
                            <td class="num r font-bold">{{ $fmt($i->stock_qty) }} <span class="font-normal text-muted">{{ $i->unit }}</span></td>
                            <td class="num r text-muted">{{ $fmt($i->min_stock) }}</td>
                            <td><span class="status {{ ['aman' => 'status-ok', 'menipis' => 'status-warn', 'habis' => 'status-bad'][$i->stock_status] }}">{{ ucfirst($i->stock_status) }}</span></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="mt-14" aria-labelledby="mv-h">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h2 id="mv-h" class="script text-4xl">Riwayat pergerakan</h2>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filter jenis" id="mv-filter">
            <button type="button" class="chip" aria-pressed="true" data-t="all">Semua</button>
            <button type="button" class="chip" aria-pressed="false" data-t="in">Masuk</button>
            <button type="button" class="chip" aria-pressed="false" data-t="out">Keluar</button>
            <button type="button" class="chip" aria-pressed="false" data-t="adjustment">Opname</button>
        </div>
    </div>
    @if ($movements->isEmpty())
        <p class="muted mt-4 text-lg">Belum ada pergerakan stok.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tbl min-w-[40rem]">
                <thead><tr><th>Waktu</th><th>Bahan</th><th>Jenis</th><th class="r">Jumlah</th><th>Catatan</th><th>Oleh</th></tr></thead>
                <tbody id="mv-rows">
                    @foreach ($movements as $m)
                        <tr data-t="{{ $m->type }}">
                            <td class="whitespace-nowrap text-muted">{{ $m->time }}</td>
                            <td class="font-semibold">{{ $m->ingredient }}</td>
                            <td><span class="status {{ ['in' => 'status-ok', 'out' => 'status-warn'][$m->type] ?? 'status-mute' }}">{{ ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Opname'][$m->type] ?? $m->type }}</span></td>
                            <td class="num r font-bold">{{ $m->type === 'in' ? '+' : ($m->type === 'out' ? '−' : ($m->qty > 0 ? '+' : '')) }}{{ $fmt($m->qty) }} <span class="font-normal text-muted">{{ $m->unit }}</span></td>
                            <td class="max-w-56 truncate text-muted">{{ $m->notes }}</td>
                            <td class="text-muted">{{ $m->user ?? 'Sistem' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@push('modals')
<x-modal id="in-form" title="Catat stok masuk">
    <form method="POST" action="{{ route('admin.stock.in') }}" class="space-y-5">@csrf
        <div><label class="label" for="in-ing">Bahan baku</label>
            <select id="in-ing" name="ingredient_id" class="select" required>@foreach ($ingredients as $ing)<option value="{{ $ing->id }}">{{ $ing->name }} (stok {{ $fmt($ing->stock_qty) }} {{ $ing->unit }})</option>@endforeach</select></div>
        <div><label class="label" for="in-qty">Jumlah dibeli</label><input id="in-qty" name="qty" type="number" step="0.01" min="0.01" class="input num" required></div>
        <div><label class="label" for="in-notes">Catatan</label><input id="in-notes" name="notes" class="input" maxlength="255" placeholder="mis. Beli di Pasar Petisah"></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Simpan stok masuk</button></div>
    </form>
</x-modal>

<x-modal id="opname-form" title="Stok opname">
    <form method="POST" action="{{ route('admin.stock.opname') }}" class="space-y-5">@csrf
        <div><label class="label" for="op-ing">Bahan baku</label>
            <select id="op-ing" name="ingredient_id" class="select" required onchange="document.getElementById('op-sys').textContent=this.selectedOptions[0].dataset.stock">
                @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-stock="{{ $fmt($ing->stock_qty) }} {{ $ing->unit }}">{{ $ing->name }}</option>@endforeach</select>
            <p class="help">Stok di sistem: <strong id="op-sys" class="text-cocoa">{{ isset($ingredients[0]) ? $fmt($ingredients[0]->stock_qty).' '.$ingredients[0]->unit : '-' }}</strong></p></div>
        <div><label class="label" for="op-qty">Jumlah fisik sebenarnya</label><input id="op-qty" name="qty" type="number" step="0.01" min="0" class="input num" required></div>
        <div><label class="label" for="op-notes">Alasan selisih</label><input id="op-notes" name="notes" class="input" maxlength="255" placeholder="mis. Tumpah, kedaluwarsa, salah hitung"></div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-line" data-modal-close>Batal</button><button class="btn btn-primary">Sesuaikan stok</button></div>
    </form>
</x-modal>
<script>
    document.getElementById('mv-filter').addEventListener('click', (e) => {
        const b = e.target.closest('.chip'); if (!b) return;
        document.querySelectorAll('#mv-filter .chip').forEach((c) => c.setAttribute('aria-pressed', String(c === b)));
        document.querySelectorAll('#mv-rows tr').forEach((r) => r.hidden = b.dataset.t !== 'all' && r.dataset.t !== b.dataset.t);
    });
</script>
@endpush
@endsection

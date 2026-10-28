@extends('layouts.app')
@section('title', 'Pembayaran '.$transaction->invoice_number.' — Kafe Kaleng')
@section('page-heading', 'Pembayaran')
@section('actions')<a href="{{ route('kasir.pos') }}" class="btn btn-ghost btn-sm">Kembali ke kasir</a>@endsection

@section('content')
@if ($errors->any())<script>addEventListener('DOMContentLoaded', () => toast(@json($errors->first())));</script>@endif

<div class="mx-auto grid max-w-4xl gap-6 md:grid-cols-[1fr_22rem] md:items-start">

    {{-- ===== Ringkasan pesanan ===== --}}
    <section class="card card-pad rise">
        <p class="eyebrow">Ringkasan pesanan</p>
        <h2 class="num text-xl font-bold">{{ $transaction->invoice_number }}</h2>
        <ul class="mt-3 divide-y divide-bark/10">
            @foreach ($transaction->details as $d)
                <li class="flex items-center gap-3 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $d->menu_name }}</p>
                        <p class="num text-xs text-bark">{{ $d->qty }} × Rp {{ number_format($d->price, 0, ',', '.') }}</p>
                    </div>
                    <p class="num text-sm font-bold">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</p>
                </li>
            @endforeach
        </ul>
        <dl class="num mt-3 space-y-1.5 border-t border-bark/12 pt-3 text-sm">
            <div class="flex justify-between text-bark-dark"><dt>Subtotal</dt><dd>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</dd></div>
            @if ($transaction->discount_amount > 0)
                <div class="flex justify-between text-leaf"><dt>Diskon</dt><dd>− Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</dd></div>
            @endif
            <div class="flex items-baseline justify-between text-lg font-bold"><dt>Total</dt><dd class="display text-2xl">Rp {{ number_format($transaction->total, 0, ',', '.') }}</dd></div>
        </dl>
    </section>

    {{-- ===== Pembayaran ===== --}}
    <section class="card card-pad rise space-y-4" style="--i:1">
        <form id="pay-form" method="POST" action="{{ route('kasir.orders.pay.store', $transaction) }}" class="space-y-4">
            @csrf
            <fieldset>
                <legend class="sr-only">Metode pembayaran</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ([['cash', 'Tunai', 'cash'], ['qris', 'QRIS', 'wallet'], ['debit', 'Debit', 'card']] as [$v, $l, $ic])
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $v }}" class="peer sr-only" @checked(old('payment_method', 'cash') === $v)>
                            <span class="flex flex-col items-center gap-1 rounded-2xl border border-bark/25 bg-paper py-2.5 text-xs font-semibold text-bark-dark transition active:scale-95 peer-checked:border-bean peer-checked:bg-bean peer-checked:text-cream peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-amber-deep"><x-icon :name="$ic" size="size-5" />{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div id="cash-box" class="space-y-2">
                <input id="paid" name="paid" type="number" min="0" inputmode="numeric" value="{{ old('paid') }}" class="input num !min-h-12 text-lg font-bold" placeholder="Uang diterima" aria-label="Uang diterima">
                <div class="no-scrollbar flex gap-2 overflow-x-auto" id="quick-cash"></div>
            </div>

            <div id="change-row" class="num flex justify-between font-semibold"><span id="change-label">Kembalian</span><span id="t-change">Rp 0</span></div>

            <button id="pay-btn" class="btn btn-primary btn-lg w-full" disabled>Bayar <x-icon name="check" size="size-5" /></button>
        </form>

        {{-- Batal hanya bisa saat belum dibayar --}}
        <form method="POST" action="{{ route('kasir.orders.void', $transaction) }}" data-confirm="Pesanan {{ $transaction->invoice_number }} akan dibatalkan dan stok bahan dikembalikan. Pesanan yang sudah dibatalkan tidak bisa dibuka lagi.">
            @csrf
            <button class="btn btn-ghost w-full">Batalkan pesanan</button>
        </form>
    </section>
</div>

@push('scripts')
<script>
(() => {
    const $ = (s) => document.querySelector(s);
    const total = {{ (int) $transaction->total }};
    const f = $('#pay-form');

    const render = () => {
        const cash = f.payment_method.value === 'cash';
        const paid = Number($('#paid').value) || 0;
        const short = cash && paid < total;
        $('#cash-box').classList.toggle('hidden', !cash);
        $('#change-row').classList.toggle('hidden', !cash);
        $('#change-label').textContent = short && paid ? 'Kurang' : 'Kembalian';
        $('#t-change').textContent = rupiah(paid ? Math.abs(paid - total) : 0);
        $('#t-change').className = short && paid ? 'text-wine' : 'text-leaf';
        $('#pay-btn').disabled = short;

        const q = $('#quick-cash'); q.innerHTML = '';
        [total, ...[20000, 50000, 100000].filter((n) => n > total)].slice(0, 4).forEach((n, k) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'chip !py-1.5 text-xs';
            b.textContent = k === 0 ? 'Uang pas' : rupiah(n);
            b.onclick = () => { $('#paid').value = n; render(); };
            q.appendChild(b);
        });
    };

    ['input', 'change'].forEach((ev) => f.addEventListener(ev, render));
    render();
})();
</script>
@endpush
@endsection

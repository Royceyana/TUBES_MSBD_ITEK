@extends('layouts.app')
@section('title', 'Pembayaran '.$transaction->number.' | Kafe Kaleng')

@section('content')
<x-page-head title="Pembayaran" :script="$transaction->number">
    <a href="{{ route('kasir.pos') }}" class="btn btn-line btn-sm">Kembali ke kasir</a>
</x-page-head>

<div class="mt-10 grid gap-12 md:grid-cols-[1.1fr_1fr] md:gap-0">
    {{-- Ringkasan pesanan --}}
    <section class="md:pr-12" aria-labelledby="sum-h">
        <h2 id="sum-h" class="script text-4xl">Ringkasan pesanan</h2>
        <p class="muted mt-1 text-sm">@if ($transaction->table_no)Meja {{ $transaction->table_no }}@else Bawa pulang @endif</p>
        <table class="tbl mt-4">
            <thead><tr><th>Menu</th><th class="r">Jumlah</th><th class="r">Subtotal</th></tr></thead>
            <tbody>
                @foreach ($transaction->details as $d)
                    <tr><td class="font-semibold">{{ $d->menu->name ?? 'Menu dihapus' }}</td><td class="num r">{{ $d->qty }}</td><td class="num r">{{ number_format($d->subtotal, 0, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="2">Subtotal</td><td class="num r display text-2xl">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td></tr></tfoot>
        </table>
    </section>

    {{-- Pembayaran --}}
    <section class="md:border-l md:border-sand-2 md:pl-12" aria-labelledby="pay-h">
        <h2 id="pay-h" class="script text-4xl">Cara bayar</h2>
        <form id="pay-form" method="POST" action="{{ route('kasir.orders.pay.store', $transaction) }}" class="mt-4 space-y-5">
            @csrf

            <div class="grid grid-cols-[1fr_1fr] gap-3">
                <div>
                    <label for="discount_type" class="label">Diskon</label>
                    <select id="discount_type" name="discount_type" class="select">
                        <option value="none" @selected(old('discount_type', 'none') === 'none')>Tanpa diskon</option>
                        <option value="percent" @selected(old('discount_type') === 'percent')>Persen (%)</option>
                        <option value="amount" @selected(old('discount_type') === 'amount')>Nominal (Rp)</option>
                    </select>
                </div>
                <div>
                    <label for="discount_value" class="label">Besar diskon</label>
                    <input id="discount_value" name="discount_value" type="number" min="0" step="any" inputmode="decimal" value="{{ old('discount_value') }}" class="input num" disabled @error('discount_value') aria-invalid="true" @enderror>
                    @error('discount_value')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <fieldset>
                <legend class="label">Metode pembayaran</legend>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['cash' => 'Tunai', 'qris' => 'QRIS'] as $v => $l)
                        <label class="block">
                            <input type="radio" name="payment_method" value="{{ $v }}" class="peer sr-only" @checked(old('payment_method', 'cash') === $v)>
                            <span class="block rounded-md border-2 border-sand-2 py-3.5 text-center font-bold transition peer-checked:border-clay peer-checked:bg-clay peer-checked:text-white peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-clay">{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <p class="num flex items-baseline justify-between border-t-2 border-cocoa pt-4 text-sm font-bold text-muted" id="disc-row" hidden><span>Diskon</span><span id="t-disc">− Rp 0</span></p>
            <p class="num flex items-baseline justify-between text-lg font-bold"><span>Total bayar</span><span id="t-total" class="display text-4xl">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span></p>

            <div id="cash-box" class="space-y-3">
                <div>
                    <label for="paid" class="label">Uang diterima</label>
                    <input id="paid" name="paid" type="number" min="0" inputmode="numeric" value="{{ old('paid') }}" class="input num !min-h-14 text-xl font-extrabold" @error('paid') aria-invalid="true" @enderror>
                    @error('paid')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap gap-2" id="quick-cash" aria-label="Nominal cepat"></div>
                <p id="change-row" class="num flex items-baseline justify-between text-lg font-bold"><span id="change-label">Kembalian</span><span id="t-change" class="display text-3xl">Rp 0</span></p>
            </div>

            <button id="pay-btn" class="btn btn-primary btn-lg w-full" disabled>Terima pembayaran</button>
        </form>

        <form method="POST" action="{{ route('kasir.orders.void', $transaction) }}" class="mt-4"
              data-confirm="Pesanan {{ $transaction->number }} akan dibatalkan. Pesanan yang sudah dibatalkan tidak bisa dibuka lagi." data-confirm-yes="Ya, batalkan">
            @csrf
            <button class="btn btn-danger w-full">Batalkan pesanan</button>
        </form>
    </section>
</div>

@push('scripts')
<script>
addEventListener('DOMContentLoaded', () => {
    const $ = (s) => document.querySelector(s);
    const subtotal = {{ (int) $transaction->subtotal }};
    const f = $('#pay-form');
    // Sama dengan fn_calc_discount() di database
    const discount = () => {
        const type = f.discount_type.value, v = Number(f.discount_value.value) || 0;
        const d = type === 'percent' ? Math.round(subtotal * Math.min(v, 100) / 100) : type === 'amount' ? v : 0;
        return Math.min(subtotal, Math.max(0, d));
    };
    const render = () => {
        const disc = discount(), total = subtotal - disc;
        const cash = f.payment_method.value === 'cash';
        const paid = Number($('#paid').value) || 0;
        const short = cash && paid < total;
        f.discount_value.disabled = f.discount_type.value === 'none';
        $('#disc-row').hidden = disc === 0;
        $('#t-disc').textContent = '− ' + rupiah(disc);
        $('#t-total').textContent = rupiah(total);
        $('#cash-box').hidden = !cash;
        $('#change-label').textContent = short && paid ? 'Kurang' : 'Kembalian';
        $('#t-change').textContent = rupiah(paid ? Math.abs(paid - total) : 0);
        $('#t-change').style.color = short && paid ? 'var(--color-bad)' : '';
        $('#pay-btn').disabled = short;
        const q = $('#quick-cash'); q.innerHTML = '';
        [total, ...[20000, 50000, 100000].filter((n) => n > total)].slice(0, 4).forEach((n, k) => {
            const b = document.createElement('button');
            b.type = 'button'; b.className = 'chip'; b.textContent = k === 0 ? 'Uang pas' : rupiah(n);
            b.onclick = () => { $('#paid').value = n; render(); };
            q.appendChild(b);
        });
    };
    ['input', 'change'].forEach((ev) => f.addEventListener(ev, render));
    render();
});
</script>
@endpush
@endsection

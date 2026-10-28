@extends('layouts.app')
@section('title', 'Kasir — Kafe Kaleng')
@section('page-heading', 'Kasir')
@section('main-class', 'p-0')

@section('content')
@if ($errors->any())<script>addEventListener('DOMContentLoaded', () => toast(@json($errors->first())));</script>@endif
<div class="lg:grid lg:h-[calc(100dvh-4.6rem)] lg:grid-cols-[1fr_24rem] xl:grid-cols-[1fr_26rem]">

    {{-- ===== Katalog menu ===== --}}
    <section class="scrollbar-thin min-w-0 space-y-4 p-4 pb-28 md:p-6 md:pb-28 lg:overflow-y-auto lg:pb-6">
        <div class="relative">
            <x-icon name="search" size="size-[1.15rem]" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-bark" />
            <input type="search" class="input !min-h-12 !rounded-2xl !pl-12" placeholder="Cari menu…" data-search="#pos-grid > button" aria-label="Cari menu">
        </div>
        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1" id="pos-cats" role="group" aria-label="Kategori">
            <button class="chip" aria-pressed="true" data-cat="all">Semua</button>
            @foreach ($categories as $c)<button class="chip" aria-pressed="false" data-cat="{{ $c->id }}">{{ $c->name }}</button>@endforeach
        </div>

        <div id="pos-grid" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($menus as $m)
                <button type="button" {{ $m->available ? '' : 'disabled' }}
                    class="card group relative overflow-hidden text-left transition duration-150 enabled:active:scale-[.96] enabled:hover:-translate-y-0.5 enabled:hover:shadow-lg disabled:opacity-50"
                    data-id="{{ $m->id }}" data-name="{{ $m->name }}" data-price="{{ (int) $m->price }}" data-cat="{{ $m->category_id }}">
                    <div class="relative grid aspect-[4/3] place-items-center overflow-hidden bg-parchment">
                        @if ($m->image)<img src="{{ asset('storage/'.$m->image) }}" alt="" class="size-full object-cover" loading="lazy">
                        @else<div class="sunburst absolute inset-0 opacity-70"></div><x-icon name="coffee" size="size-10" class="relative text-bean/55" />@endif
                        <span data-badge class="absolute right-2 top-2 hidden min-w-7 rounded-full bg-bean px-2 py-1 text-center text-xs font-bold text-amber"></span>
                        @unless ($m->available)<span class="absolute inset-0 grid place-items-center bg-bean/60 text-sm font-bold text-cream">Stok habis</span>@endunless
                    </div>
                    <div class="p-3">
                        <p class="line-clamp-2 min-h-10 text-sm font-semibold leading-snug">{{ $m->name }}</p>
                        <p class="num mt-1 font-bold text-amber-deep">Rp {{ number_format($m->price, 0, ',', '.') }}</p>
                    </div>
                </button>
            @endforeach
        </div>
        <p id="pos-empty" class="hidden py-16 text-center text-bark">Menu tidak ditemukan.</p>
    </section>

    {{-- ===== Keranjang ===== --}}
    <button type="button" id="cart-fab" data-toggle="cart" class="btn btn-dark btn-lg fixed inset-x-4 bottom-[max(1rem,env(safe-area-inset-bottom))] z-30 justify-between shadow-2xl lg:hidden">
        <span class="inline-flex items-center gap-2"><x-icon name="cart" /> <span id="fab-count">0</span> item</span>
        <span class="num" id="fab-total">Rp 0</span>
    </button>

    <aside id="cart" data-open="false" aria-label="Keranjang"
        class="fixed inset-x-0 bottom-0 z-40 flex max-h-[88dvh] translate-y-full flex-col rounded-t-[1.75rem] border-t border-bark/15 bg-paper shadow-[0_-30px_80px_-20px_rgba(12,8,4,.5)] transition-transform duration-500 ease-[cubic-bezier(.22,1,.36,1)]
               data-[open=true]:translate-y-0 lg:static lg:max-h-none lg:translate-y-0 lg:rounded-none lg:border-l lg:border-t-0 lg:shadow-none">
        <div class="flex items-center justify-between px-5 pb-3 pt-5">
            <div><p class="eyebrow">Pesanan</p><h2 class="text-xl font-bold">Keranjang</h2></div>
            <div class="flex gap-1.5">
                <button type="button" id="cart-clear" class="btn btn-ghost btn-sm">Kosongkan</button>
                <button type="button" class="btn btn-ghost btn-icon btn-sm lg:hidden" data-toggle="cart" aria-label="Tutup keranjang"><x-icon name="x" size="size-4" /></button>
            </div>
        </div>

        <ul id="cart-list" class="scrollbar-thin min-h-24 flex-1 divide-y divide-bark/10 overflow-y-auto px-5" aria-live="polite"></ul>
        <div id="cart-empty" class="grid flex-1 place-items-center px-5 py-10 text-center text-bark">
            <div><x-icon name="cart" size="size-10" class="mx-auto mb-3 opacity-50" /><p class="font-semibold text-bark-dark">Keranjang masih kosong</p><p class="text-sm">Ketuk menu untuk menambahkan.</p></div>
        </div>

        <form id="checkout" method="POST" action="{{ route('kasir.orders.store') }}" class="space-y-4 border-t border-bark/12 bg-cream/60 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
            @csrf
            {{-- Diskon --}}
            <div class="grid grid-cols-[7.5rem_1fr] gap-2">
                <select id="disc-type" name="discount_type" class="select !min-h-10" aria-label="Jenis diskon"><option value="none">Tanpa diskon</option><option value="percent">Diskon %</option><option value="amount">Diskon Rp</option></select>
                <input id="disc-val" name="discount_value" type="number" min="0" class="input num !min-h-10" placeholder="0" disabled aria-label="Nilai diskon">
            </div>
            {{-- Pembayaran dipindah ke langkah "Bayar" (bagian berikutnya), sementara disembunyikan --}}
            <div hidden>
            {{-- Metode bayar --}}
            <fieldset>
                <legend class="sr-only">Metode pembayaran</legend>
                <div class="grid grid-cols-3 gap-2" id="pay-methods">
                    @foreach ([['cash', 'Tunai', 'cash'], ['qris', 'QRIS', 'wallet'], ['debit', 'Debit', 'card']] as [$v, $l, $ic])
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $v }}" class="peer sr-only" @checked($v === 'cash')>
                            <span class="flex flex-col items-center gap-1 rounded-2xl border border-bark/25 bg-paper py-2.5 text-xs font-semibold text-bark-dark transition active:scale-95 peer-checked:border-bean peer-checked:bg-bean peer-checked:text-cream peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-amber-deep"><x-icon :name="$ic" size="size-5" />{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            {{-- Tunai diterima --}}
            <div id="cash-box" class="space-y-2">
                <input id="paid" name="paid" type="number" min="0" inputmode="numeric" class="input num !min-h-12 text-lg font-bold" placeholder="Uang diterima" aria-label="Uang diterima">
                <div class="no-scrollbar flex gap-2 overflow-x-auto" id="quick-cash"></div>
            </div>
            </div>

            <dl class="num space-y-1.5 text-sm">
                <div class="flex justify-between text-bark-dark"><dt>Subtotal</dt><dd id="t-sub">Rp 0</dd></div>
                <div class="flex justify-between text-leaf"><dt>Diskon</dt><dd id="t-disc">− Rp 0</dd></div>
                <div class="flex items-baseline justify-between border-t border-bark/12 pt-2 text-lg font-bold"><dt>Total</dt><dd id="t-total" class="display text-2xl">Rp 0</dd></div>
                <div id="change-row" hidden class="flex justify-between font-semibold"><dt id="change-label">Kembalian</dt><dd id="t-change">Rp 0</dd></div>
            </dl>
            <button id="pay-btn" class="btn btn-primary btn-lg w-full" disabled>Simpan pesanan <x-icon name="arrow" size="size-5" /></button>
        </form>
    </aside>
</div>

@push('scripts')
<script>
(() => {
    const $ = (s, r = document) => r.querySelector(s);
    const cart = new Map(); // id -> {id, name, price, qty}
    const f = $('#checkout');

    const total = () => {
        const sub = [...cart.values()].reduce((a, i) => a + i.price * i.qty, 0);
        const type = $('#disc-type').value, v = Number($('#disc-val').value) || 0;
        let disc = type === 'percent' ? sub * Math.min(v, 100) / 100 : type === 'amount' ? Math.min(v, sub) : 0;
        return { sub, disc: Math.round(disc), total: sub - Math.round(disc), count: [...cart.values()].reduce((a, i) => a + i.qty, 0) };
    };

    const render = () => {
        const list = $('#cart-list'); list.innerHTML = '';
        cart.forEach((i) => {
            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 py-3';
            li.innerHTML = `<div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold"></p><p class="num text-xs text-bark">${rupiah(i.price)}</p></div>
                <div class="flex items-center gap-1.5">
                    <button type="button" class="btn btn-ghost btn-icon btn-sm" data-act="dec" aria-label="Kurangi"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                    <span class="num w-6 text-center text-sm font-bold">${i.qty}</span>
                    <button type="button" class="btn btn-ghost btn-icon btn-sm" data-act="inc" aria-label="Tambah"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button>
                </div><p class="num w-24 text-right text-sm font-bold">${rupiah(i.price * i.qty)}</p>`;
            li.firstElementChild.firstElementChild.textContent = i.name;
            li.dataset.id = i.id;
            list.appendChild(li);
        });
        document.querySelectorAll('#pos-grid [data-badge]').forEach((b) => {
            const q = cart.get(b.closest('button').dataset.id)?.qty || 0;
            b.textContent = q; b.classList.toggle('hidden', !q);
        });
        const t = total(), pm = f.payment_method.value, paid = Number($('#paid').value) || 0;
        $('#cart-empty').classList.toggle('hidden', cart.size > 0);
        list.classList.toggle('hidden', cart.size === 0);
        $('#t-sub').textContent = rupiah(t.sub); $('#t-disc').textContent = '− ' + rupiah(t.disc); $('#t-total').textContent = rupiah(t.total);
        $('#fab-count').textContent = t.count; $('#fab-total').textContent = rupiah(t.total);
        $('#cash-box').classList.toggle('hidden', pm !== 'cash');
        $('#change-row').classList.toggle('hidden', pm !== 'cash');
        const short = pm === 'cash' && paid < t.total;
        $('#change-label').textContent = short && paid ? 'Kurang' : 'Kembalian';
        $('#t-change').textContent = rupiah(Math.abs(paid - t.total) * (paid ? 1 : 0));
        $('#t-change').className = short && paid ? 'text-wine' : 'text-leaf';
        $('#pay-btn').disabled = cart.size === 0; // pembayaran belum divalidasi di langkah simpan
        // tombol uang pas + pecahan terdekat
        const q = $('#quick-cash'); q.innerHTML = '';
        if (t.total > 0) [t.total, ...[20000, 50000, 100000].filter((n) => n >= t.total)].slice(0, 4).forEach((n, k) => {
            const b = document.createElement('button'); b.type = 'button'; b.className = 'chip !py-1.5 text-xs';
            b.textContent = k === 0 ? 'Uang pas' : rupiah(n); b.onclick = () => { $('#paid').value = n; render(); };
            q.appendChild(b);
        });
    };

    $('#pos-grid').addEventListener('click', (e) => {
        const b = e.target.closest('button[data-id]'); if (!b || b.disabled) return;
        const it = cart.get(b.dataset.id) || { id: b.dataset.id, name: b.dataset.name, price: Number(b.dataset.price), qty: 0 };
        it.qty++; cart.set(it.id, it); render();
    });
    $('#cart-list').addEventListener('click', (e) => {
        const a = e.target.closest('[data-act]'); if (!a) return;
        const id = a.closest('li').dataset.id, it = cart.get(id);
        it.qty += a.dataset.act === 'inc' ? 1 : -1;
        if (it.qty <= 0) cart.delete(id); render();
    });
    $('#cart-clear').onclick = () => { cart.clear(); $('#paid').value = ''; render(); };
    $('#disc-type').onchange = (e) => { $('#disc-val').disabled = e.target.value === 'none'; if (e.target.value === 'none') $('#disc-val').value = ''; render(); };
    ['input', 'change'].forEach((ev) => f.addEventListener(ev, render));

    // Filter kategori
    $('#pos-cats').addEventListener('click', (e) => {
        const b = e.target.closest('.chip'); if (!b) return;
        document.querySelectorAll('#pos-cats .chip').forEach((c) => c.setAttribute('aria-pressed', c === b));
        let n = 0;
        document.querySelectorAll('#pos-grid > button').forEach((m) => { m.hidden = b.dataset.cat !== 'all' && m.dataset.cat !== b.dataset.cat; if (!m.hidden) n++; });
        $('#pos-empty').classList.toggle('hidden', n > 0);
    });

    // Sebelum submit: serialisasi item keranjang ke input tersembunyi (untuk PosController@store)
    f.addEventListener('submit', () => {
        f.querySelectorAll('input[data-item]').forEach((i) => i.remove());
        [...cart.values()].forEach((it, k) => {
            [['menu_id', it.id], ['qty', it.qty]].forEach(([n, v]) => {
                const h = document.createElement('input'); h.type = 'hidden'; h.dataset.item = '1'; h.name = `items[${k}][${n}]`; h.value = v; f.appendChild(h);
            });
        });
    });
    render();
})();
</script>
@endpush
@endsection

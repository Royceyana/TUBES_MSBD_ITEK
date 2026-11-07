@extends('layouts.app')
@section('title', 'Kasir | Kafe Kaleng')
@section('main-class', 'mx-auto max-w-[1500px]')

@section('content')
<div class="lg:grid lg:grid-cols-[1fr_26rem]">

    {{-- ===== Daftar menu ===== --}}
    <section class="min-w-0 px-5 py-8 pb-32 md:px-8 lg:min-h-[calc(100dvh-4.5rem)] lg:pb-10">
        <x-page-head title="Kasir" script="pilih pesanan" />

        <div class="mt-8">
            <label for="pos-search" class="sr-only">Cari menu</label>
            <input id="pos-search" type="search" class="input !min-h-12" placeholder="Cari menu" autocomplete="off">
        </div>
        <div class="no-scrollbar -mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1" id="pos-cats" role="group" aria-label="Kategori">
            <button type="button" class="chip" aria-pressed="true" data-cat="all">Semua</button>
            @foreach ($categories as $c)<button type="button" class="chip" aria-pressed="false" data-cat="{{ $c->id }}">{{ $c->name }}</button>@endforeach
        </div>

        <ul id="pos-list" class="mt-6 grid gap-x-10 md:grid-cols-2">
            @foreach ($menus as $m)
                <li data-cat="{{ $m->category_id }}" data-name="{{ strtolower($m->name) }}">
                    <button type="button" {{ $m->available ? '' : 'disabled' }}
                        class="group flex w-full items-baseline gap-3 border-b border-sand py-3.5 text-left transition-colors enabled:hover:bg-[#faf5ef] enabled:active:bg-sand disabled:opacity-50"
                        data-id="{{ $m->id }}" data-name="{{ $m->name }}" data-price="{{ (int) $m->price }}" data-portions="{{ $m->portions ?? '' }}">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold leading-snug {{ $m->available ? '' : 'line-through' }}">{{ $m->name }}</span>
                            @if (! $m->available)
                                <span class="status status-bad mt-1">Stok habis</span>
                            @elseif ($m->portions !== null && $m->portions <= 5)
                                <span class="status status-warn mt-1">Sisa {{ $m->portions }} porsi</span>
                            @endif
                        </span>
                        <span data-badge class="num hidden font-extrabold text-clay"></span>
                        <span class="num font-extrabold">{{ number_format($m->price, 0, ',', '.') }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <p id="pos-empty" class="muted py-16 text-lg" @if ($menus->count()) hidden @endif>{{ $menus->count() ? 'Menu tidak ditemukan.' : 'Belum ada menu aktif. Minta admin menambahkannya.' }}</p>
    </section>

    {{-- ===== Kolom pesanan ===== --}}
    <button type="button" id="cart-fab" data-toggle="cart" aria-controls="cart" aria-expanded="false" class="btn btn-dark btn-lg fixed inset-x-4 bottom-[max(1rem,env(safe-area-inset-bottom))] z-30 justify-between lg:hidden">
        <span><span id="fab-count">0</span> item</span><span class="num" id="fab-total">Rp 0</span>
    </button>

    <aside id="cart" data-open="false" aria-label="Pesanan"
        class="fixed inset-x-0 bottom-0 z-40 flex max-h-[90dvh] translate-y-full flex-col rounded-t-2xl bg-sand transition-transform duration-500 ease-[cubic-bezier(.22,1,.36,1)]
               data-[open=true]:translate-y-0 lg:sticky lg:top-[4.5rem] lg:z-auto lg:h-[calc(100dvh-4.5rem)] lg:max-h-none lg:translate-y-0 lg:rounded-none">
        <div class="flex items-start justify-between gap-3 px-6 pb-3 pt-6">
            <div><h2 class="display text-3xl">Pesanan</h2><span class="script ml-3 text-2xl text-clay-dark">meja &amp; menu</span></div>
            <div class="flex gap-1.5">
                <button type="button" id="cart-clear" class="btn btn-line btn-sm">Kosongkan</button>
                <button type="button" class="btn btn-line btn-icon btn-sm lg:hidden" data-toggle="cart" aria-label="Tutup pesanan"><x-icon name="x" size="size-4" /></button>
            </div>
        </div>

        <ul id="cart-list" class="scrollbar-thin min-h-24 flex-1 overflow-y-auto px-6" aria-live="polite"></ul>
        <p id="cart-empty" class="flex-1 px-6 py-8 text-muted">Belum ada pesanan. Ketuk menu di sebelah kiri untuk menambahkan.</p>

        <form id="checkout" method="POST" action="{{ route('kasir.orders.store') }}" class="border-t-2 border-cocoa px-6 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-5">
            @csrf
            <div class="flex items-center justify-between gap-4">
                <label for="table_no" class="label !mb-0">Nomor meja</label>
                <input id="table_no" name="table_no" maxlength="10" class="input !min-h-10 !w-36 text-center" placeholder="Bawa pulang" value="{{ old('table_no') }}">
            </div>
            <p class="mt-5 flex items-baseline justify-between"><span class="font-bold">Total</span><span id="t-total" class="display num text-3xl">Rp 0</span></p>
            <button id="save-btn" class="btn btn-primary btn-lg mt-4 w-full" disabled>Simpan &amp; bayar</button>
        </form>
    </aside>
</div>

@push('scripts')
<script>
addEventListener('DOMContentLoaded', () => {
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const cart = new Map(); // id -> {id, name, price, qty, max}
    let cat = 'all';

    const render = () => {
        const list = $('#cart-list'); list.innerHTML = '';
        let sum = 0, count = 0;
        cart.forEach((i) => {
            sum += i.price * i.qty; count += i.qty;
            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 border-b border-sand-2 py-3';
            li.dataset.id = i.id;
            li.innerHTML = `<div class="min-w-0 flex-1"><p class="font-semibold leading-snug"></p><p class="num text-sm text-muted">${rupiah(i.price)}</p></div>
                <div class="flex items-center gap-1">
                    <button type="button" class="btn btn-line btn-icon btn-sm" data-act="dec" aria-label="Kurangi">&minus;</button>
                    <span class="num w-8 text-center font-extrabold">${i.qty}</span>
                    <button type="button" class="btn btn-line btn-icon btn-sm" data-act="inc" aria-label="Tambah">+</button>
                </div><p class="num w-24 text-right font-extrabold">${rupiah(i.price * i.qty)}</p>`;
            li.querySelector('p').textContent = i.name;
            list.appendChild(li);
        });
        $$('#pos-list [data-badge]').forEach((b) => {
            const q = cart.get(b.closest('button').dataset.id)?.qty || 0;
            b.textContent = q ? '×' + q : ''; b.classList.toggle('hidden', !q);
        });
        $('#cart-empty').hidden = cart.size > 0;
        list.hidden = cart.size === 0;
        $('#t-total').textContent = rupiah(sum);
        $('#fab-count').textContent = count; $('#fab-total').textContent = rupiah(sum);
        $('#save-btn').disabled = cart.size === 0;
    };

    const filter = () => {
        const q = $('#pos-search').value.trim().toLowerCase();
        let shown = 0;
        $$('#pos-list > li').forEach((li) => {
            li.hidden = (cat !== 'all' && li.dataset.cat !== cat) || (q && !li.dataset.name.includes(q));
            if (!li.hidden) shown++;
        });
        const e = $('#pos-empty'); e.hidden = shown > 0 || $$('#pos-list > li').length === 0; if (!e.hidden) e.textContent = 'Menu tidak ditemukan.';
    };

    $('#pos-list').addEventListener('click', (e) => {
        const b = e.target.closest('button[data-id]'); if (!b || b.disabled) return;
        const max = b.dataset.portions === '' ? Infinity : Number(b.dataset.portions);
        const it = cart.get(b.dataset.id) || { id: b.dataset.id, name: b.dataset.name, price: Number(b.dataset.price), qty: 0, max };
        if (it.qty >= max) { toast(`Stok "${it.name}" hanya cukup untuk ${max} porsi.`, true); return; }
        it.qty++; cart.set(it.id, it); render();
    });
    $('#cart-list').addEventListener('click', (e) => {
        const a = e.target.closest('[data-act]'); if (!a) return;
        const id = a.closest('li').dataset.id, it = cart.get(id);
        if (a.dataset.act === 'inc') { if (it.qty >= it.max) { toast(`Stok "${it.name}" hanya cukup untuk ${it.max} porsi.`, true); return; } it.qty++; } else it.qty--;
        if (it.qty <= 0) cart.delete(id); render();
    });
    $('#cart-clear').onclick = () => { cart.clear(); render(); };
    $('#pos-search').addEventListener('input', filter);
    $('#pos-cats').addEventListener('click', (e) => {
        const b = e.target.closest('.chip'); if (!b) return;
        $$('#pos-cats .chip').forEach((c) => c.setAttribute('aria-pressed', String(c === b)));
        cat = b.dataset.cat; filter();
    });

    // Serialisasi keranjang ke input tersembunyi (PosController@store)
    $('#checkout').addEventListener('submit', (e) => {
        if (!cart.size) { e.preventDefault(); return; }
        const f = e.target;
        $$('input[data-item]', f).forEach((i) => i.remove());
        [...cart.values()].forEach((it, k) => {
            [['menu_id', it.id], ['qty', it.qty]].forEach(([n, v]) => {
                const h = document.createElement('input'); h.type = 'hidden'; h.dataset.item = '1'; h.name = `items[${k}][${n}]`; h.value = v; f.appendChild(h);
            });
        });
    });
    render();
});
</script>
@endpush
@endsection

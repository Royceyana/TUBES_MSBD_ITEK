@extends('layouts.app')
@section('title', 'Menu & Produk — Kafe Kaleng')
@section('page-heading', 'Menu & Resep')
@section('actions')
    <button class="btn btn-primary btn-sm" data-modal-open="menu-form" data-title="Tambah menu" data-fill-name="" data-fill-price=""><x-icon name="plus" size="size-4" /> Tambah menu</button>
@endsection

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-52 flex-1 md:max-w-xs">
            <x-icon name="search" size="size-[1.1rem]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-bark" />
            <input type="search" class="input !pl-11" placeholder="Cari menu…" data-search="#menu-grid > article" aria-label="Cari menu">
        </div>
        <div class="no-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1" role="group" aria-label="Filter kategori" id="cat-filter">
            <button class="chip" aria-pressed="true" data-cat="all">Semua</button>
            @foreach ($categories as $c)<button class="chip" aria-pressed="false" data-cat="{{ $c->id }}">{{ $c->name }}</button>@endforeach
        </div>
    </div>

    <div id="menu-grid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @foreach ($menus as $i => $m)
            <article class="card group overflow-hidden rise" style="--i:{{ min($i, 12) }}" data-cat="{{ $m->category_id }}">
                <div class="relative grid aspect-[16/10] place-items-center overflow-hidden bg-parchment">
                    @if ($m->image)
                        <img src="{{ asset('storage/'.$m->image) }}" alt="{{ $m->name }}" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                    @else
                        <div class="sunburst absolute inset-0 opacity-70"></div>
                        <x-icon name="coffee" size="size-14" class="relative text-bean/60" />
                    @endif
                    <span class="badge {{ $m->status === 'active' ? 'badge-ok' : 'badge-mute' }} absolute left-3 top-3">{{ $m->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="p-4">
                    <p class="eyebrow">{{ $m->category }}</p>
                    <h2 class="mt-1 truncate text-lg font-bold">{{ $m->name }}</h2>
                    <p class="num mt-1 text-xl font-bold text-amber-deep">Rp {{ number_format($m->price, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs {{ $m->recipe_count ? 'text-bark' : 'font-semibold text-wine' }}">{{ $m->recipe_count ? $m->recipe_count.' bahan dalam resep' : 'Belum ada resep — stok tak akan berkurang' }}</p>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ url('/admin/menus/'.$m->id.'/recipe') }}" class="btn btn-dark btn-sm flex-1"><x-icon name="flask" size="size-4" /> Resep</a>
                        <button class="btn btn-ghost btn-icon btn-sm" aria-label="Ubah {{ $m->name }}" data-modal-open="menu-form" data-title="Ubah menu" data-fill-name="{{ $m->name }}" data-fill-price="{{ (int) $m->price }}" data-fill-category_id="{{ $m->category_id }}" data-fill-status="{{ $m->status }}"><x-icon name="pencil" size="size-4" /></button>
                        <form method="POST" action="#" data-demo="Menu dihapus (demo)" data-confirm="Menu “{{ $m->name }}” akan dihapus beserta resepnya.">@csrf @method('DELETE')
                            <button class="btn btn-danger btn-icon btn-sm" aria-label="Hapus {{ $m->name }}"><x-icon name="trash" size="size-4" /></button></form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>

@push('modals')
<x-modal id="menu-form" title="Tambah menu" :large="true">
    <form method="POST" action="#" enctype="multipart/form-data" data-demo class="space-y-5">@csrf
        <div class="grid gap-5 sm:grid-cols-[11rem_1fr]">
            {{-- Upload gambar dengan pratinjau --}}
            <div>
                <span class="label">Foto menu</span>
                <label class="group relative grid aspect-square cursor-pointer place-items-center overflow-hidden rounded-2xl border-2 border-dashed border-bark/35 bg-parchment text-center text-bark transition hover:border-amber-deep">
                    <img id="menu-preview" alt="" class="absolute inset-0 hidden size-full object-cover">
                    <span id="menu-preview-hint" class="px-3 text-xs"><x-icon name="image" size="size-8" class="mx-auto mb-2" />Klik untuk unggah<br>JPG/PNG, maks 2 MB</span>
                    <input type="file" name="image" accept="image/*" class="sr-only" onchange="const f=this.files[0]; if(f){const p=document.getElementById('menu-preview'); p.src=URL.createObjectURL(f); p.classList.remove('hidden'); document.getElementById('menu-preview-hint').classList.add('hidden');}">
                </label>
            </div>
            <div class="space-y-4">
                <div><label class="label" for="m-name">Nama menu</label><input id="m-name" name="name" class="input" required maxlength="100" placeholder="mis. Kopi Susu Kaleng"></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label" for="m-cat">Kategori</label>
                        <select id="m-cat" name="category_id" class="select" required>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div><label class="label" for="m-price">Harga (Rp)</label><input id="m-price" name="price" type="number" min="0" step="500" class="input num" required placeholder="15000"></div>
                </div>
                <div><label class="label" for="m-status">Status</label>
                    <select id="m-status" name="status" class="select"><option value="active">Aktif — tampil di kasir</option><option value="inactive">Nonaktif — disembunyikan</option></select></div>
            </div>
        </div>
        <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" data-modal-close>Batal</button><button class="btn btn-primary">Simpan menu</button></div>
    </form>
</x-modal>
<script>
    // Filter kategori sisi-klien
    document.getElementById('cat-filter').addEventListener('click', (e) => {
        const b = e.target.closest('.chip'); if (!b) return;
        document.querySelectorAll('#cat-filter .chip').forEach(c => c.setAttribute('aria-pressed', c === b));
        document.querySelectorAll('#menu-grid > article').forEach(a => a.hidden = b.dataset.cat !== 'all' && a.dataset.cat !== b.dataset.cat);
    });
</script>
@endpush
@endsection

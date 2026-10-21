@extends('layouts.app')
@section('title', 'Resep '.$menu->name.' — Kafe Kaleng')
@section('page-heading', 'Resep / BOM')
@section('actions')
    <a href="{{ url('/admin/menus') }}" class="btn btn-ghost btn-sm">← Kembali</a>
@endsection

@section('content')
<div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_18rem]">
    <form method="POST" action="#" data-demo="Resep tersimpan (demo)" class="card card-pad rise">@csrf @method('PUT')
        <p class="eyebrow">{{ $menu->category }}</p>
        <h2 class="display mt-1 text-3xl">{{ $menu->name }}</h2>
        <p class="mt-1 text-sm text-bark-dark">Tentukan bahan dan takaran untuk <strong>1 porsi</strong>. Saat menu terjual, stok berkurang sesuai takaran ini.</p>

        <div id="rows" class="mt-6 space-y-3">
            @foreach ($recipe as $r)
                <div class="row grid grid-cols-[1fr_7rem_auto] items-end gap-3">
                    <div><label class="label">Bahan</label>
                        <select name="ingredient_id[]" class="select" onchange="syncUnit(this)">
                            @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}" @selected($ing->id === $r->ingredient_id)>{{ $ing->name }}</option>@endforeach
                        </select></div>
                    <div><label class="label">Takaran</label>
                        <div class="relative"><input name="qty_needed[]" type="number" step="0.01" min="0.01" class="input num !pr-12" value="{{ $r->qty_needed }}" required>
                        <span class="unit pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-bark">{{ $r->unit }}</span></div></div>
                    <button type="button" class="btn btn-danger btn-icon" aria-label="Hapus bahan" onclick="this.closest('.row').remove()"><x-icon name="trash" size="size-4" /></button>
                </div>
            @endforeach
        </div>

        <button type="button" id="add-row" class="btn btn-ghost mt-4 w-full border-dashed"><x-icon name="plus" size="size-4" /> Tambah bahan</button>
        <div class="mt-6 flex justify-end gap-2 border-t border-bark/10 pt-5"><a href="{{ url('/admin/menus') }}" class="btn btn-ghost">Batal</a><button class="btn btn-primary">Simpan resep</button></div>

        <template id="row-tpl">
            <div class="row grid grid-cols-[1fr_7rem_auto] items-end gap-3">
                <div><label class="label">Bahan</label>
                    <select name="ingredient_id[]" class="select" onchange="syncUnit(this)">
                        @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}">{{ $ing->name }}</option>@endforeach
                    </select></div>
                <div><label class="label">Takaran</label>
                    <div class="relative"><input name="qty_needed[]" type="number" step="0.01" min="0.01" class="input num !pr-12" required>
                    <span class="unit pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-bark">{{ $ingredients[0]->unit ?? '' }}</span></div></div>
                <button type="button" class="btn btn-danger btn-icon" aria-label="Hapus bahan" onclick="this.closest('.row').remove()"><x-icon name="trash" size="size-4" /></button>
            </div>
        </template>
    </form>

    <aside class="card-dark rise relative h-fit overflow-hidden p-6" style="--i:2">
        <div class="sunburst absolute inset-0 opacity-70"></div>
        <div class="relative">
            <p class="eyebrow !text-sand">Harga jual</p>
            <p class="num display mt-2 text-4xl text-amber">Rp {{ number_format($menu->price, 0, ',', '.') }}</p>
            <p class="mt-4 text-sm text-cream/70">Pastikan setiap menu punya resep supaya stok bahan baku selalu akurat dan laporan laba-rugi benar.</p>
        </div>
    </aside>
</div>

@push('scripts')
<script>
    function syncUnit(sel) { sel.closest('.row').querySelector('.unit').textContent = sel.selectedOptions[0].dataset.unit; }
    document.getElementById('add-row').addEventListener('click', () => {
        const n = document.getElementById('row-tpl').content.cloneNode(true);
        document.getElementById('rows').appendChild(n);
        document.querySelector('#rows .row:last-child select').focus();
    });
</script>
@endpush
@endsection

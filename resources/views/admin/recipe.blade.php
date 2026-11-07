@extends('layouts.app')
@section('title', 'Resep '.$menu->name.' | Kafe Kaleng')

@section('content')
<x-page-head title="Resep" :script="$menu->name">
    <a href="{{ route('admin.menus') }}" class="btn btn-line btn-sm">Kembali ke menu</a>
</x-page-head>

<div class="mt-10 grid gap-12 lg:grid-cols-[1fr_20rem] lg:gap-0">
    <form method="POST" action="{{ route('admin.menus.recipe.save', $menu) }}" class="lg:pr-12">@csrf @method('PUT')
        <p class="text-muted">Takaran bahan untuk <strong class="text-cocoa">1 porsi</strong> {{ $menu->name }}. Stok berkurang sesuai takaran ini saat dapur menandai pesanan siap.</p>

        <div id="rows" class="mt-6 space-y-3">
            @foreach ($recipe as $r)
                <div class="row grid grid-cols-[1fr_8rem_auto] items-end gap-3">
                    <div><label class="label">Bahan</label>
                        <select name="ingredient_id[]" class="select" onchange="syncUnit(this)">
                            @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}" @selected($ing->id == $r->ingredient_id)>{{ $ing->name }}</option>@endforeach
                        </select></div>
                    <div><label class="label">Takaran</label>
                        <div class="relative"><input name="qty_needed[]" type="number" step="0.01" min="0.01" class="input num !pr-12" value="{{ $r->qty_needed + 0 }}" required>
                        <span class="unit pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted">{{ $ingredients->firstWhere('id', $r->ingredient_id)->unit ?? '' }}</span></div></div>
                    <button type="button" class="btn btn-danger btn-icon" aria-label="Hapus bahan" onclick="this.closest('.row').remove()"><x-icon name="trash" size="size-4" /></button>
                </div>
            @endforeach
        </div>
        <p id="no-rows" class="muted mt-6" @if ($recipe->count()) hidden @endif>Belum ada bahan. Tanpa resep, menu ini tidak mengurangi stok.</p>

        <button type="button" id="add-row" class="btn btn-line mt-5">Tambah bahan</button>
        <div class="mt-8 flex justify-end gap-2 border-t border-sand-2 pt-6"><a href="{{ route('admin.menus') }}" class="btn btn-line">Batal</a><button class="btn btn-primary">Simpan resep</button></div>

        <template id="row-tpl">
            <div class="row grid grid-cols-[1fr_8rem_auto] items-end gap-3">
                <div><label class="label">Bahan</label>
                    <select name="ingredient_id[]" class="select" onchange="syncUnit(this)">
                        @foreach ($ingredients as $ing)<option value="{{ $ing->id }}" data-unit="{{ $ing->unit }}">{{ $ing->name }}</option>@endforeach
                    </select></div>
                <div><label class="label">Takaran</label>
                    <div class="relative"><input name="qty_needed[]" type="number" step="0.01" min="0.01" class="input num !pr-12" required>
                    <span class="unit pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted">{{ $ingredients[0]->unit ?? '' }}</span></div></div>
                <button type="button" class="btn btn-danger btn-icon" aria-label="Hapus bahan" onclick="this.closest('.row').remove()"><x-icon name="trash" size="size-4" /></button>
            </div>
        </template>
    </form>

    <aside class="lg:border-l lg:border-sand-2 lg:pl-12" aria-label="Ringkasan harga">
        <h2 class="script text-4xl">Harga jual</h2>
        <p class="display num mt-2 text-4xl">Rp {{ number_format($menu->price, 0, ',', '.') }}</p>
        @if ($hpp && $hpp->jumlah_bahan > 0)
            <dl class="mt-6 space-y-3 border-t-2 border-cocoa pt-4">
                <div class="flex justify-between"><dt class="text-muted">HPP (biaya bahan)</dt><dd class="num font-bold">Rp {{ number_format($hpp->hpp, 0, ',', '.') }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Margin</dt><dd class="num font-bold">Rp {{ number_format($hpp->margin, 0, ',', '.') }} ({{ $hpp->margin_persen + 0 }}%)</dd></div>
            </dl>
            <p class="help mt-3">Dihitung dari resep yang tersimpan dan harga per satuan bahan.</p>
        @else
            <p class="mt-6 text-muted">HPP muncul setelah resep tersimpan.</p>
        @endif
    </aside>
</div>

@push('scripts')
<script>
    function syncUnit(sel) { sel.closest('.row').querySelector('.unit').textContent = sel.selectedOptions[0].dataset.unit; }
    const noRows = () => { document.getElementById('no-rows').hidden = document.querySelectorAll('#rows .row').length > 0; };
    document.getElementById('add-row').addEventListener('click', () => {
        document.getElementById('rows').appendChild(document.getElementById('row-tpl').content.cloneNode(true));
        document.querySelector('#rows .row:last-child select').focus(); noRows();
    });
    document.getElementById('rows').addEventListener('click', () => setTimeout(noRows));
</script>
@endpush
@endsection

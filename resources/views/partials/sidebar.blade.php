@php
    $role = $role ?? 'admin';
    $menus = [
        'admin' => [
            ['Ringkasan', [
                ['/admin/dashboard', 'dashboard', 'Dashboard', 'admin/dashboard'],
            ]],
            ['Master Data', [
                ['/admin/categories', 'tags', 'Kategori Menu', 'admin/categories*'],
                ['/admin/menus', 'coffee', 'Menu & Resep', 'admin/menus*'],
                ['/admin/ingredients', 'flask', 'Bahan Baku', 'admin/ingredients*'],
            ]],
            ['Operasional', [
                ['/admin/stock', 'swap', 'Manajemen Stok', 'admin/stock*'],
            ]],
        ],
        'kasir' => [
            ['Ringkasan', [
                ['/kasir/dashboard', 'dashboard', 'Ringkasan Shift', 'kasir/dashboard'],
            ]],
            ['Transaksi', [
                ['/kasir/pos', 'cart', 'Kasir (POS)', 'kasir/pos'],
                ['/kasir/history', 'receipt', 'Riwayat Transaksi', 'kasir/history*'],
            ]],
        ],
        'owner' => [
            ['Ringkasan', [
                ['/owner/dashboard', 'dashboard', 'Dashboard', 'owner/dashboard'],
            ]],
            ['Laporan', [
                ['/owner/reports/sales', 'chart', 'Penjualan', 'owner/reports/sales'],
                ['/owner/reports/profit', 'trend', 'Laba-Rugi', 'owner/reports/profit'],
                ['/owner/reports/stock', 'package', 'Stok & Bahan', 'owner/reports/stock'],
            ]],
        ],
    ];
    $roleLabel = ['admin' => 'Admin', 'kasir' => 'Kasir', 'owner' => 'Owner'][$role] ?? 'Admin';
    $userName = auth()->user()->name ?? ['admin' => 'Rina Admin', 'kasir' => 'Dimas Kasir', 'owner' => 'Pak Hendra'][$role];
@endphp

<div class="flex h-full flex-col">
    <a href="{{ url("/$role/dashboard") }}" class="flex items-center gap-3 px-5 pb-4 pt-6">
        <x-logo size="size-11" />
        <div class="leading-none">
            <p class="display text-[1.35rem] text-cream">KAFE KALENG</p>
            <p class="mt-1.5 text-[.68rem] font-semibold uppercase tracking-[.2em] text-sand/80">Medan · Sejak 2015</p>
        </div>
    </a>
    <div class="stripes mx-5 h-2 opacity-60" aria-hidden="true"></div>

    <nav class="scrollbar-thin flex-1 overflow-y-auto px-3 pb-4" aria-label="Navigasi utama">
        @foreach ($menus[$role] as [$group, $items])
            <p class="nav-group">{{ $group }}</p>
            <div class="space-y-1">
                @foreach ($items as [$href, $icon, $label, $pattern])
                    <a href="{{ url($href) }}" class="nav-link" @if (request()->is($pattern)) aria-current="page" @endif>
                        <x-icon :name="$icon" size="size-[1.15rem]" /> {{ $label }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="sunburst relative mx-3 mb-3 overflow-hidden rounded-2xl border border-white/10 p-4">
        <div class="relative flex items-center gap-3">
            <div class="grid size-10 place-items-center rounded-full bg-amber font-bold text-ink">{{ strtoupper(substr($userName, 0, 1)) }}</div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-cream">{{ $userName }}</p>
                <p class="text-xs text-sand/80">{{ $roleLabel }}</p>
            </div>
            <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/login') }}">
                @csrf
                <button class="grid size-9 place-items-center rounded-xl text-cream/80 transition hover:bg-white/10 active:scale-90" title="Keluar" aria-label="Keluar">
                    <x-icon name="logout" size="size-[1.1rem]" />
                </button>
            </form>
        </div>
    </div>
</div>

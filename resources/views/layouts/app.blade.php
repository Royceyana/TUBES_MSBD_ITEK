@php
    $user = auth()->user();
    $role = $user->role ?? 'admin';
    $nav = [
        'admin' => [['admin.dashboard', 'Dashboard', 'admin/dashboard'], ['admin.categories', 'Kategori', 'admin/categories*'], ['admin.menus', 'Menu & Resep', 'admin/menus*'], ['admin.ingredients', 'Bahan baku', 'admin/ingredients*'], ['admin.stock', 'Stok', 'admin/stock*'], ['admin.users', 'Kelola Pengguna', 'admin/users*']],
        'kasir' => [['kasir.dashboard', 'Ringkasan', 'kasir/dashboard'], ['kasir.pos', 'Kasir', 'kasir/pos'], ['kasir.history', 'Riwayat', 'kasir/history*']],
        'dapur' => [['dapur.board', 'Antrian dapur', 'dapur/board'], ['dapur.stock', 'Stok bahan', 'dapur/stock*'], ['dapur.recipes', 'Resep', 'dapur/recipes*']],
        'waitress' => [['waitress.board', 'Siap diantar', 'pelayan/*']],
        'owner' => [['owner.dashboard', 'Dashboard', 'owner/dashboard'], ['owner.sales', 'Penjualan', 'owner/reports/sales'], ['owner.profit', 'Laba kotor', 'owner/reports/profit'], ['owner.stock', 'Stok', 'owner/reports/stock']],
    ][$role] ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    @stack('head')
    <title>@yield('title', 'Kafe Kaleng')</title>
</head>
<body class="min-h-dvh bg-white text-cocoa lg:pl-64">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[200] focus:rounded-md focus:bg-cocoa focus:px-4 focus:py-2 focus:font-bold focus:text-white">Lompat ke konten</a>

{{-- ===== Sidebar (desktop) ===== --}}
<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-sand-2 bg-white lg:flex" aria-label="Menu samping">
    <a href="{{ route($user?->homeRoute() ?? 'home') }}" class="flex items-center gap-3 px-6 pb-6 pt-7" aria-label="Kafe Kaleng, beranda">
        <x-logo size="h-12 w-auto" />
        <span class="display text-xl leading-none">Kafe<br>Kaleng</span>
    </a>

    <p class="script px-6 text-2xl text-clay">{{ $user?->roleLabel() }}</p>
    <nav class="scrollbar-thin mt-3 flex-1 overflow-y-auto" aria-label="Navigasi utama">
        @foreach ($nav as [$r, $label, $pattern])
            <a href="{{ route($r) }}" @if (request()->is($pattern)) aria-current="page" @endif
               class="block border-l-[3px] border-transparent px-6 py-3 text-[.95rem] font-semibold transition-colors hover:bg-[#faf5ef] aria-[current=page]:border-clay aria-[current=page]:bg-sand aria-[current=page]:font-bold">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="border-t border-sand-2 px-6 py-5">
        <p class="truncate text-sm font-bold">{{ $user->name ?? '' }}</p>
        <p class="muted truncate text-xs">{{ $user->email ?? '' }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="btn btn-line btn-sm w-full">Keluar</button></form>
    </div>
</aside>

{{-- ===== Bilah atas + menu (seluler) ===== --}}
<header class="sticky top-0 z-40 border-b border-sand-2 bg-white pt-[env(safe-area-inset-top)] lg:hidden">
    <div class="flex items-center justify-between gap-4 px-5 py-3">
        <a href="{{ route($user?->homeRoute() ?? 'home') }}" class="flex items-center gap-2.5" aria-label="Kafe Kaleng, beranda">
            <x-logo size="h-10 w-auto" /><span class="display text-lg">Kafe Kaleng</span>
        </a>
        <button type="button" class="btn btn-line btn-icon btn-sm" data-toggle="nav" aria-controls="nav" aria-expanded="false" aria-label="Buka menu"><x-icon name="menu" size="size-5" /></button>
    </div>
    <nav id="nav" data-open="false" class="hidden border-t border-sand-2 bg-white px-5 pb-4 pt-2 data-[open=true]:block" aria-label="Navigasi seluler">
        <p class="script pt-2 text-2xl text-clay">{{ $user?->roleLabel() }}</p>
        @foreach ($nav as [$r, $label, $pattern])
            <a href="{{ route($r) }}" class="block border-b border-sand py-3.5 text-base font-semibold" @if (request()->is($pattern)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        <p class="muted mt-4 text-sm">Masuk sebagai {{ $user->name ?? '' }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="btn btn-line w-full">Keluar</button></form>
    </nav>
</header>

<main id="main" class="@yield('main-class', 'mx-auto max-w-7xl px-5 py-10 md:px-10 md:py-14') pb-[max(2.5rem,env(safe-area-inset-bottom))]">
    @yield('content')
</main>

{{-- Dialog konfirmasi untuk semua form [data-confirm] --}}
<dialog id="confirm-dialog" class="modal" aria-labelledby="confirm-title">
    <div class="sheet p-7">
        <h2 id="confirm-title" class="display text-2xl">Yakin ingin melanjutkan?</h2>
        <p data-confirm-text class="mt-3 text-muted"></p>
        <div class="mt-7 flex justify-end gap-2">
            <button type="button" class="btn btn-line" data-modal-close>Batal</button>
            <button type="button" class="btn btn-danger" data-confirm-yes>Ya, lanjutkan</button>
        </div>
    </div>
</dialog>

@include('partials.toasts')
@stack('modals')
@stack('scripts')
</body>
</html>

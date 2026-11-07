@php
    $user = auth()->user();
    $role = $user->role ?? 'admin';
    $nav = [
        'admin' => [['admin.dashboard', 'Dashboard', 'admin/dashboard'], ['admin.categories', 'Kategori', 'admin/categories*'], ['admin.menus', 'Menu & Resep', 'admin/menus*'], ['admin.ingredients', 'Bahan baku', 'admin/ingredients*'], ['admin.stock', 'Stok', 'admin/stock*']],
        'kasir' => [['kasir.dashboard', 'Ringkasan', 'kasir/dashboard'], ['kasir.pos', 'Kasir', 'kasir/pos'], ['kasir.history', 'Riwayat', 'kasir/history*']],
        'dapur' => [['dapur.board', 'Antrian dapur', 'dapur/*']],
        'waitress' => [['waitress.board', 'Antar pesanan', 'pelayan/*']],
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
<body class="min-h-dvh bg-white text-cocoa">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[200] focus:rounded-md focus:bg-cocoa focus:px-4 focus:py-2 focus:font-bold focus:text-white">Lompat ke konten</a>

<header class="sticky top-0 z-40 border-b border-sand-2 bg-white pt-[env(safe-area-inset-top)]">
    <div class="mx-auto flex max-w-7xl items-center gap-6 px-5 py-3 md:px-8">
        <a href="{{ route($user?->homeRoute() ?? 'home') }}" class="flex items-center gap-2.5" aria-label="Kafe Kaleng, beranda">
            <x-logo size="h-10 w-auto" />
            <span class="display hidden text-lg sm:inline">Kafe Kaleng</span>
        </a>
        <nav class="ml-4 hidden items-center gap-7 md:flex" aria-label="Navigasi utama">
            @foreach ($nav as [$r, $label, $pattern])
                <a href="{{ route($r) }}" class="nav-link" @if (request()->is($pattern)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-4">
            <p class="hidden text-right text-sm leading-tight lg:block"><span class="block font-bold">{{ $user->name ?? '' }}</span><span class="muted">{{ $user?->roleLabel() }}</span></p>
            <form method="POST" action="{{ route('logout') }}" class="hidden md:block">@csrf<button class="btn btn-line btn-sm">Keluar</button></form>
            <button type="button" class="btn btn-line btn-icon btn-sm md:hidden" data-toggle="nav" aria-controls="nav" aria-expanded="false" aria-label="Buka menu">
                <x-icon name="menu" size="size-5" />
            </button>
        </div>
    </div>
    <nav id="nav" data-open="false" class="hidden border-t border-sand-2 bg-white px-5 pb-4 pt-2 data-[open=true]:block md:hidden" aria-label="Navigasi seluler">
        @foreach ($nav as [$r, $label, $pattern])
            <a href="{{ route($r) }}" class="block border-b border-sand py-3.5 text-base font-semibold" @if (request()->is($pattern)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        <p class="muted mt-4 text-sm">Masuk sebagai {{ $user->name ?? '' }} ({{ $user?->roleLabel() }})</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="btn btn-line w-full">Keluar</button></form>
    </nav>
</header>

<main id="main" class="@yield('main-class', 'mx-auto max-w-7xl px-5 py-10 md:px-8 md:py-14') pb-[max(2.5rem,env(safe-area-inset-bottom))]">
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

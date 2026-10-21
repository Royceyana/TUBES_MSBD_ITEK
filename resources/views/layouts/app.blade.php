@php
    // Role diambil dari user yang login; saat pratinjau UI (tanpa auth) diambil dari segmen URL: /admin, /kasir, /owner
    $role = auth()->user()->role ?? request()->segment(1);
    $role = in_array($role, ['admin', 'kasir', 'owner']) ? $role : 'admin';
    $roleLabel = ['admin' => 'Admin', 'kasir' => 'Kasir', 'owner' => 'Owner'][$role];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b130c">
    <title>@yield('title', 'Kafe Kaleng')</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh bg-cream text-bean">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[200] focus:rounded-xl focus:bg-amber focus:px-4 focus:py-2 focus:font-semibold">Lompat ke konten</a>

<div class="lg:flex">
    {{-- Overlay mobile --}}
    <div id="nav-scrim" data-toggle="nav" class="fixed inset-0 z-40 bg-bean/50 opacity-0 backdrop-blur-sm transition-opacity duration-300 pointer-events-none lg:hidden"></div>

    {{-- Sidebar: material gelap = wilayah struktural --}}
    <aside id="nav" data-open="false" aria-label="Sidebar"
           class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full bg-bean transition-transform duration-500 ease-[cubic-bezier(.22,1,.36,1)]
                  data-[open=true]:translate-x-0 lg:sticky lg:top-0 lg:h-dvh lg:w-72 lg:translate-x-0 lg:shrink-0">
        @include('partials.sidebar', ['role' => $role])
    </aside>

    <div class="min-w-0 flex-1">
        {{-- Top bar translusen; konten bergulir di bawahnya --}}
        <header class="glass sticky top-0 z-30 flex items-center gap-3 px-4 py-3 pt-[max(.75rem,env(safe-area-inset-top))] md:px-8">
            <button class="btn btn-ghost btn-icon lg:hidden" data-toggle="nav" aria-label="Buka menu"><x-icon name="menu" /></button>
            <div class="min-w-0 flex-1">
                <p class="eyebrow truncate">Kafe Kaleng · {{ $roleLabel }}</p>
                <h1 class="display truncate text-xl md:text-2xl">@yield('page-heading', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2">@yield('actions')</div>
            <span class="hidden items-center gap-2 rounded-full bg-parchment px-3.5 py-2 text-xs font-semibold text-bark-dark md:inline-flex">
                <x-icon name="calendar" size="size-4" /> {{ now()->translatedFormat('l, d M Y') }}
            </span>
        </header>

        <main id="main" class="@yield('main-class', 'p-4 md:p-8') pb-[max(1rem,env(safe-area-inset-bottom))]">
            @yield('content')
        </main>
    </div>
</div>

{{-- Dialog konfirmasi (dipakai semua form [data-confirm]) --}}
<dialog id="confirm-dialog" class="modal" aria-labelledby="confirm-title">
    <div class="sheet p-6">
        <div class="grid size-12 place-items-center rounded-2xl bg-wine-soft text-wine"><x-icon name="alert" /></div>
        <h2 id="confirm-title" class="mt-4 text-xl font-bold">Yakin ingin melanjutkan?</h2>
        <p data-confirm-text class="mt-1.5 text-sm text-bark-dark"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
            <button type="button" class="btn btn-danger" data-confirm-yes>Ya, hapus</button>
        </div>
    </div>
</dialog>

{{-- Pesan flash dari controller --}}
@if (session('success'))<script>addEventListener('DOMContentLoaded', () => toast(@json(session('success'))));</script>@endif

@stack('modals')
@stack('scripts')
<style>
    #nav[data-open=true] ~ * {}
    body:has(#nav[data-open=true]) #nav-scrim { opacity: 1; pointer-events: auto; }
    @media (min-width: 1024px) { #nav-scrim { display: none; } }
</style>
</body>
</html>

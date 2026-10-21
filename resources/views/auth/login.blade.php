@extends('layouts.guest')
@section('title', 'Masuk — Kafe Kaleng')

@section('content')
<div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
    {{-- Panel brand --}}
    <aside class="grain relative isolate hidden overflow-hidden bg-bean p-12 text-cream lg:flex lg:flex-col lg:justify-between">
        <div class="sunburst absolute inset-0 -z-10"></div>
        <a href="{{ url('/') }}" class="flex w-fit items-center gap-3"><x-logo size="size-12" /><span class="display text-2xl">KAFE KALENG</span></a>
        <div>
            <p class="eyebrow !text-sand">Sistem manajemen kafe</p>
            <h2 class="display mt-4 text-6xl text-cream">Buka kedai,<br><span class="text-amber">tenang hatinya.</span></h2>
            <p class="mt-5 max-w-md text-cream/70">Kasir, stok, dan laporan dalam satu tempat — supaya fokus kamu tetap di secangkir kopi.</p>
        </div>
        <div class="stripes h-3 w-40 opacity-80"></div>
    </aside>

    {{-- Form --}}
    <main class="flex items-center justify-center p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-10">
        <div class="w-full max-w-sm">
            <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 lg:hidden"><x-logo size="size-11" /><span class="display text-xl">KAFE KALENG</span></a>
            <h1 class="display rise text-4xl">Selamat datang.</h1>
            <p class="rise mt-2 text-bark-dark" style="--i:1">Masuk dengan akun yang diberikan admin.</p>

            @if ($errors->any())
                <div class="mt-6 flex gap-3 rounded-2xl bg-wine-soft p-4 text-sm text-wine" role="alert">
                    <x-icon name="alert" size="size-5" /> <p>{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ Route::has('login.store') ? route('login.store') : (Route::has('login') ? route('login') : '#') }}" class="mt-8 space-y-5" @unless(Route::has('login.store') || Route::has('login')) data-demo="Mode demo" data-redirect="{{ url('/admin/dashboard') }}" @endunless>
                @csrf
                <div class="rise" style="--i:2">
                    <label for="email" class="label">Email</label>
                    <div class="relative">
                        <x-icon name="mail" size="size-[1.1rem]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-bark" />
                        <input id="email" name="email" type="email" autocomplete="username" required autofocus value="{{ old('email') }}" placeholder="nama@kafekaleng.id" class="input !pl-11" @error('email') aria-invalid="true" @enderror>
                    </div>
                </div>
                <div class="rise" style="--i:3">
                    <label for="password" class="label">Kata sandi</label>
                    <div class="relative">
                        <x-icon name="lock" size="size-[1.1rem]" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-bark" />
                        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••" class="input !pl-11 !pr-12">
                        <button type="button" data-pw-toggle="password" aria-label="Tampilkan kata sandi" class="absolute right-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-bark transition hover:bg-parchment active:scale-90"><x-icon name="eye" size="size-[1.1rem]" /></button>
                    </div>
                </div>
                <label class="rise flex items-center gap-2.5 text-sm text-bark-dark" style="--i:4">
                    <input type="checkbox" name="remember" class="size-4 rounded accent-amber-deep"> Ingat saya di perangkat ini
                </label>
                <button class="btn btn-dark btn-lg rise w-full" style="--i:5">Masuk <x-icon name="arrow" size="size-5" /></button>
            </form>

            {{-- Hanya muncul saat APP_DEBUG=true: pintasan pratinjau tiap role --}}
            @if (config('app.debug'))
                <div class="mt-10 rounded-2xl border border-dashed border-bark/40 p-4">
                    <p class="eyebrow">Pratinjau UI (mode debug)</p>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <a class="btn btn-ghost btn-sm" href="{{ url('/admin/dashboard') }}">Admin</a>
                        <a class="btn btn-ghost btn-sm" href="{{ url('/kasir/pos') }}">Kasir</a>
                        <a class="btn btn-ghost btn-sm" href="{{ url('/owner/dashboard') }}">Owner</a>
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>
@endsection

@extends('layouts.guest')
@section('title', 'Masuk | Kafe Kaleng')

@section('content')
<div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
    <aside class="relative hidden bg-espresso lg:block" aria-hidden="true">
        <img src="{{ asset('images/tampak-depan.webp') }}" width="1024" height="468" alt="" class="absolute inset-0 size-full object-cover object-[45%_50%]">
        <div class="absolute inset-0" style="background: linear-gradient(to top, rgb(20 8 3 / .85), rgb(20 8 3 / 0) 55%)"></div>
        <p class="hero-title absolute bottom-10 left-10 text-[5.5vw] leading-[.92]">Kafe<br>Kaleng</p>
    </aside>

    <main class="flex items-center justify-center p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:p-10">
        <div class="w-full max-w-sm">
            <a href="{{ url('/') }}" class="mb-10 flex w-fit items-center gap-3" aria-label="Kembali ke beranda">
                <x-logo size="h-12 w-auto" /><span class="display text-xl">Kafe Kaleng</span>
            </a>
            <h1 class="display text-5xl">Masuk</h1>
            <p class="script mt-1 ml-5 text-3xl text-clay">untuk staf kafe</p>

            @if ($errors->any())
                <p class="mt-8 border-l-4 border-bad bg-blush px-4 py-3 text-sm font-semibold" role="alert">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" autocomplete="username" required autofocus value="{{ old('email') }}" class="input" @error('email') aria-invalid="true" @enderror>
                </div>
                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="input !pr-28">
                        <button type="button" data-pw-toggle="password" aria-pressed="false" class="absolute right-1 top-1/2 -translate-y-1/2 rounded px-3 py-2 text-sm font-bold text-clay hover:text-cocoa">Tampilkan</button>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-full">Masuk</button>
            </form>
            <p class="muted mt-8 text-sm">Akun staf diberikan oleh pemilik kafe.</p>
        </div>
    </main>
</div>
@endsection

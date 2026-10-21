@extends('layouts.guest')
@section('title', 'Akses ditolak')
@section('content')
<main class="grid min-h-dvh place-items-center bg-bean p-6 text-center text-cream">
    <div class="max-w-md">
        <x-logo size="size-20" class="mx-auto" />
        <p class="display mt-8 text-8xl text-amber">403</p>
        <h1 class="mt-3 text-2xl font-bold">Area ini bukan untuk perananmu.</h1>
        <p class="mt-2 text-cream/65">Akunmu tidak punya izin membuka halaman ini. Kembali ke beranda atau hubungi admin bila ini keliru.</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-ghost border-cream/25 text-cream hover:bg-white/10">Kembali</a>
            <a href="{{ url('/') }}" class="btn btn-primary">Ke beranda</a>
        </div>
    </div>
</main>
@endsection

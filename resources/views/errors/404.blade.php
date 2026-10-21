@extends('layouts.guest')
@section('title', 'Halaman tidak ditemukan')
@section('content')
<main class="grid min-h-dvh place-items-center bg-bean p-6 text-center text-cream">
    <div class="max-w-md">
        <x-logo size="size-20" class="mx-auto" />
        <p class="display mt-8 text-8xl text-amber">404</p>
        <h1 class="mt-3 text-2xl font-bold">Halaman ini tidak ada di menu.</h1>
        <p class="mt-2 text-cream/65">Mungkin tautannya salah atau halamannya sudah dipindah.</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-ghost border-cream/25 text-cream hover:bg-white/10">Kembali</a>
            <a href="{{ url('/') }}" class="btn btn-primary">Ke beranda</a>
        </div>
    </div>
</main>
@endsection

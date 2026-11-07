@extends('layouts.guest')
@section('title', 'Akses ditolak')
@section('content')
<main class="grid min-h-dvh place-items-center bg-blush p-6 text-center">
    <div class="max-w-lg">
        <p class="display text-[7rem] leading-none sm:text-[10rem]">403</p>
        <h1 class="display mt-2 text-3xl">Akses ditolak.</h1>
        <p class="mt-3 text-muted">{{ $exception->getMessage() ?: 'Akun Anda tidak punya izin untuk membuka halaman ini.' }}</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-line">Ke beranda</a>
            @auth<a href="{{ route(auth()->user()->homeRoute()) }}" class="btn btn-dark">Ke halaman saya</a>@endauth
        </div>
    </div>
</main>
@endsection

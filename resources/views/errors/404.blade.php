@extends('layouts.guest')
@section('title', 'Halaman tidak ditemukan')
@section('content')
<main class="grid min-h-dvh place-items-center bg-blush p-6 text-center">
    <div class="max-w-lg">
        <p class="display text-[7rem] leading-none sm:text-[10rem]">404</p>
        <h1 class="display mt-2 text-3xl">Halaman ini tidak ada di menu.</h1>
        <p class="script mt-2 text-3xl text-clay-dark">mungkin tautannya salah</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-line">Kembali</a>
            <a href="{{ url('/') }}" class="btn btn-dark">Ke beranda</a>
        </div>
    </div>
</main>
@endsection

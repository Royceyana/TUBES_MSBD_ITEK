
@extends('layouts.app')

@section('title', 'Dashboard | Kafe Kaleng')
@section('page-heading', 'Dashboard Overview')

@section('content')

    <!-- Welcome -->
    <section class="mb-8">
        <p class="text-sm font-medium text-amber-800">SELAMAT DATANG 👋</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-stone-900 md:text-3xl">
            Halo, Admin Kafe!
        </h2>
        <p class="mt-2 text-sm text-stone-500">
            Pantau dan kelola operasional Kafe Kaleng dari satu tempat.
        </p>
    </section>

    <!-- Summary Cards -->
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-stone-500">Total Menu</p>
            <p class="mt-3 text-3xl font-bold text-stone-900">24</p>
            <p class="mt-2 text-xs text-stone-400">Menu terdaftar</p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-stone-500">Kategori</p>
            <p class="mt-3 text-3xl font-bold text-stone-900">5</p>
            <p class="mt-2 text-xs text-stone-400">Kategori menu</p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-stone-500">Transaksi Hari Ini</p>
            <p class="mt-3 text-3xl font-bold text-stone-900">0</p>
            <p class="mt-2 text-xs text-stone-400">Data contoh</p>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-stone-500">Stok Perlu Dicek</p>
            <p class="mt-3 text-3xl font-bold text-amber-700">0</p>
            <p class="mt-2 text-xs text-stone-400">Data contoh</p>
        </div>

    </section>

    <!-- Main Panels -->
    <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="rounded-2xl border border-stone-200 bg-white p-6 xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-stone-900">Aktivitas Penjualan</h3>
                    <p class="mt-1 text-sm text-stone-500">
                        Ringkasan aktivitas transaksi.
                    </p>
                </div>
                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs text-stone-600">
                    Hari ini
                </span>
            </div>

            <div class="mt-6 flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-stone-300 bg-stone-50 p-6 text-center">
                <span class="text-3xl">📊</span>
                <p class="mt-3 font-medium text-stone-700">Belum ada data penjualan</p>
                <p class="mt-1 text-sm text-stone-500">
                    Grafik akan ditampilkan setelah data transaksi terintegrasi.
                </p>
            </div>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-6">
            <h3 class="font-semibold text-stone-900">Akses Cepat</h3>
            <p class="mt-1 text-sm text-stone-500">Kelola data kafe.</p>

            <div class="mt-5 space-y-3">
                <a href="#menu"
                   class="flex items-center justify-between rounded-xl border border-stone-200 p-4 hover:border-amber-600 hover:bg-amber-50">
                    <span class="text-sm font-medium">Menu Produk</span>
                    <span class="text-amber-800">→</span>
                </a>

                <a href="#kategori"
                   class="flex items-center justify-between rounded-xl border border-stone-200 p-4 hover:border-amber-600 hover:bg-amber-50">
                    <span class="text-sm font-medium">Kategori Menu</span>
                    <span class="text-amber-800">→</span>
                </a>

                <a href="#bahan-baku"
                   class="flex items-center justify-between rounded-xl border border-stone-200 p-4 hover:border-amber-600 hover:bg-amber-50">
                    <span class="text-sm font-medium">Bahan Baku</span>
                    <span class="text-amber-800">→</span>
                </a>
            </div>
        </div>

    </section>

    <p class="mt-8 text-center text-xs text-stone-400">
        Kafe Kaleng Management System · Prototype UI
    </p>

@endsection

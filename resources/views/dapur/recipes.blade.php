@extends('layouts.app')
@section('title', 'Resep | Kafe Kaleng')
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ','); @endphp

@section('content')
<x-page-head title="Resep" script="per porsi">
    <span class="muted text-sm">Hanya lihat. Perubahan resep dilakukan admin.</span>
</x-page-head>

@if ($menus->isEmpty())
    <p class="muted mt-10 text-lg">Belum ada menu aktif.</p>
@endif

<div class="mt-10 grid gap-x-12 gap-y-10 md:grid-cols-2">
    @foreach ($menus as $m)
        <article aria-labelledby="r-{{ $m->id }}" class="border-b border-sand-2 pb-6">
            <h2 id="r-{{ $m->id }}" class="display text-2xl">{{ $m->name }}</h2>
            @if ($m->category)<p class="muted text-sm font-bold">{{ $m->category }}</p>@endif
            @forelse ($lines[$m->id] ?? [] as $l)
                @if ($loop->first)<ul class="mt-3 space-y-1.5 text-lg">@endif
                <li class="flex gap-3"><span class="num w-24 font-extrabold text-clay">{{ $fmt($l->qty_needed) }} {{ $l->unit }}</span><span>{{ $l->name }}</span></li>
                @if ($loop->last)</ul>@endif
            @empty
                <p class="muted mt-3">Belum ada resep.</p>
            @endforelse
        </article>
    @endforeach
</div>
@endsection

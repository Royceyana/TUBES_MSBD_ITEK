@extends('layouts.guest')
@section('title', 'Kafe Kaleng | Kafe vintage ramah kantong di Medan')

@php
    $maps = 'https://www.google.com/maps/search/?api=1&query='.urlencode('Kafe Kaleng Jl. Jamin Ginting Gg. Senina No.11 Medan');
    $byCat = $menus->groupBy('category_id');
@endphp

@section('content')
{{-- ===== HERO: mengikuti acuan "the bake": nav di atas, panel foto membulat, judul dua bobot di kiri atas,
     foto kecil + keterangan di kiri bawah, tile kaca berisi satu menu di kanan bawah ===== --}}
@php $featured = $menus->first(fn ($m) => str_contains(strtolower($m->name), 'ayam geprek')); @endphp
<section class="bg-white p-3 md:p-5">
    <header class="flex items-center justify-between gap-6 px-2 py-2 md:px-6 md:py-3">
        <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Kafe Kaleng, beranda">
            <x-logo size="h-11 w-auto md:h-12" />
            <span class="display hidden whitespace-nowrap text-xl min-[440px]:inline md:text-2xl">Kafe Kaleng</span>
        </a>
        <nav class="flex items-center gap-5 text-sm font-semibold md:gap-9 md:text-[.95rem]" aria-label="Halaman">
            <a href="#menu" class="hover:underline hover:underline-offset-8">Menu</a>
            <a href="#lokasi" class="hover:underline hover:underline-offset-8">Lokasi</a>
            <a href="{{ route('login') }}" class="btn btn-dark btn-sm !rounded-full !px-5 md:!min-h-11 md:!px-7">Masuk</a>
        </nav>
    </header>

    <div class="relative isolate mt-2 min-h-[37rem] overflow-hidden rounded-[1.5rem] bg-espresso text-white md:h-[calc(100svh-6.5rem)] md:min-h-[40rem] md:rounded-[2rem]">
        <img src="{{ asset('images/hero-kafe-kaleng.webp') }}" width="1920" height="1080" fetchpriority="high"
             alt="Papan kayu bertuliskan Kafe Kaleng dengan huruf kuningan di dinding batu berhias tanaman rambat, diterangi lampu gantung"
             class="absolute inset-0 -z-20 size-full object-cover object-[49%_45%]">
        <div class="absolute inset-0 -z-10" aria-hidden="true"
             style="background: linear-gradient(100deg, rgb(20 8 3 / .72) 0%, rgb(20 8 3 / .25) 42%, rgb(20 8 3 / 0) 62%), linear-gradient(to top, rgb(20 8 3 / .55), rgb(20 8 3 / 0) 38%)"></div>

        <h1 class="absolute left-5 top-6 max-w-[17rem] text-[1.9rem] leading-[1.08] tracking-[-.02em] sm:max-w-md sm:text-4xl md:left-12 md:top-12 md:max-w-xl md:text-[3.4rem]">
            <span class="block font-normal">Makan enak dan<br>ngopi santai.</span>
            <span class="block font-bold">Ramah di kantong.</span>
        </h1>

        {{-- foto kecil + keterangan --}}
        <div class="absolute bottom-4 left-4 flex items-end md:bottom-8 md:left-8">
            <img src="{{ asset('images/tampak-depan.webp') }}" width="1024" height="468" loading="lazy"
                 alt="Pintu masuk Kafe Kaleng dari dinding batu alam"
                 class="h-36 w-24 rounded-2xl border-[3px] border-white/90 object-cover object-[50%_60%] md:h-48 md:w-32">
            <p class="-ml-3 hidden max-w-60 rounded-2xl bg-white px-4 py-3 text-sm leading-snug text-cocoa sm:block">Tampak depan Kafe Kaleng, Jl. Jamin Ginting, Medan.</p>
        </div>

        {{-- tile kaca: satu menu dari database --}}
        @if ($featured)
            <a href="#menu" class="absolute bottom-4 right-4 w-36 rounded-2xl border border-white/30 bg-black/25 p-2.5 backdrop-blur-md transition-transform duration-300 hover:-translate-y-1 md:bottom-8 md:right-8 md:w-48 md:p-3" aria-label="{{ $featured->name }}, Rp {{ number_format($featured->price, 0, ',', '.') }}, lihat menu">
                <p class="flex items-center justify-between gap-2 text-[.8rem] font-bold leading-tight md:text-base"><span>{{ $featured->name }}</span><span class="shrink-0" aria-hidden="true">&rarr;</span></p>
                <img src="{{ asset('images/nasi-ayam-geprek.webp') }}" width="560" height="560" loading="lazy" alt="Sepiring nasi putih dengan ayam goreng sambal geprek, timun, tomat, dan selada"
                     class="mt-2 aspect-square w-full rounded-xl object-cover">
                <p class="num mt-2 text-sm font-bold md:text-base">Rp {{ number_format($featured->price, 0, ',', '.') }}</p>
            </a>
        @endif
    </div>
</section>

{{-- ===== Pengantar ===== --}}
<section class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-8 px-5 py-14 md:px-10 md:py-20">
    <p class="max-w-xl text-xl leading-relaxed md:text-2xl">Kafe vintage ramah kantong di Jl. Jamin Ginting, Medan. Menu rumahan yang enak, kopi, dan barang antik yang boleh dibawa pulang.</p>
    <div class="flex flex-wrap items-center gap-3">
        <a href="#menu" class="btn btn-primary btn-lg">Lihat menu</a>
        <a href="#lokasi" class="btn btn-line btn-lg">Cari lokasi</a>
    </div>
</section>

{{-- ===== MENU: data asli dari tabel menus & categories ===== --}}
<section id="menu" class="mx-auto max-w-6xl scroll-mt-6 px-5 pb-20 md:px-10 md:pb-28">
    <div class="page-head">
        <div><h2 class="display text-5xl md:text-6xl">Menu</h2><span class="script ml-6 block text-3xl text-clay">makan &amp; minum</span></div>
    </div>

    @if ($menus->isEmpty())
        <p class="muted mt-12 text-lg">Menu belum diisi. Silakan cek lagi nanti.</p>
    @else
        <div class="mt-10 flex flex-wrap gap-2" role="group" aria-label="Filter kategori" data-filter="#menu-groups > section">
            <button type="button" class="chip" aria-pressed="true" data-cat="all">Semua</button>
            @foreach ($categories as $c)
                @if ($byCat->has($c->id))<button type="button" class="chip" aria-pressed="false" data-cat="{{ $c->id }}">{{ $c->name }}</button>@endif
            @endforeach
        </div>

        <div id="menu-groups" class="mt-10 gap-x-20 md:columns-2">
            @foreach ($categories as $c)
                @continue(! $byCat->has($c->id))
                <section data-cat="{{ $c->id }}" class="mb-10 break-inside-avoid">
                    <h3 class="script border-b-2 border-cocoa pb-2 text-4xl">{{ $c->name }}</h3>
                    <ul class="mt-3">
                        @foreach ($byCat[$c->id] as $m)
                            <li class="menu-line"><span class="font-semibold">{{ $m->name }}</span><span class="dots" aria-hidden="true"></span><span class="price">{{ number_format($m->price, 0, ',', '.') }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
        <p class="muted mt-2 text-sm">Harga dalam rupiah.</p>
    @endif
</section>

{{-- ===== LOKASI: pita blush, penutup seperti bagian "New drink" di acuan ===== --}}
<footer id="lokasi" class="band scroll-mt-0">
    <div class="mx-auto grid max-w-6xl items-center gap-10 px-5 py-16 md:grid-cols-[1fr_1.15fr] md:gap-16 md:px-10 md:py-24">
        <div>
            <h2 class="display text-6xl md:text-7xl">Mampir</h2>
            <p class="script -mt-1 ml-8 text-4xl text-clay-dark">ke Kafe Kaleng</p>
            <address class="mt-8 text-xl not-italic leading-relaxed">Jl. Jamin Ginting Gg. Senina No.11<br>Medan, Indonesia 20155</address>
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ $maps }}" target="_blank" rel="noopener" class="btn btn-dark">Buka di Google Maps</a>
                <a href="https://instagram.com/kafekaleng_medan" target="_blank" rel="noopener" class="btn btn-line">@kafekaleng_medan</a>
            </div>
        </div>
        <img src="{{ asset('images/tampak-depan.webp') }}" width="1024" height="468" loading="lazy"
             alt="Tampak depan Kafe Kaleng: dinding batu alam, papan kayu bertuliskan Kafe Kaleng, dan pintu masuk yang terbuka"
             class="h-auto w-full rounded-md">
    </div>
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 border-t border-cocoa/25 px-5 py-5 text-sm md:px-10">
        <span>© {{ date('Y') }} Kafe Kaleng, Medan</span>
        <a href="{{ route('login') }}" class="link">Masuk untuk staf</a>
    </div>
</footer>
@endsection

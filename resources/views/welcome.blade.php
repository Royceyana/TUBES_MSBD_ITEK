@extends('layouts.guest')
@section('title', 'Kafe Kaleng | Kafe vintage ramah kantong di Medan')

@php
    $maps = 'https://www.google.com/maps/search/?api=1&query='.urlencode('Kafe Kaleng Jl. Jamin Ginting Gg. Senina No.11 Medan');
    $byCat = $menus->groupBy('category_id');
@endphp

@section('content')
{{-- ===== HERO: foto tampak depan kafe; judul besar di dua sisi, kata miring di kanan bawah (acuan: Landing_Page.jpg) ===== --}}
<section class="relative isolate bg-espresso text-white md:h-[100svh] md:min-h-[600px] md:overflow-hidden">
    <div class="aspect-[4/3] md:absolute md:inset-0 md:-z-20 md:aspect-auto">
        <img src="{{ asset('images/hero-kafe-kaleng.webp') }}" width="1920" height="1080" fetchpriority="high"
             alt="Papan kayu bertuliskan Kafe Kaleng dengan huruf kuningan di dinding batu berhias tanaman rambat, diterangi lampu gantung"
             class="size-full object-cover object-[50%_40%]">
    </div>
    <div class="absolute inset-0 -z-10 hidden md:block" aria-hidden="true"
         style="background: linear-gradient(to bottom, rgb(20 8 3 / .62), rgb(20 8 3 / 0) 28%), linear-gradient(to top, rgb(20 8 3 / .82), rgb(20 8 3 / 0) 48%)"></div>

    <header class="absolute inset-x-0 top-0 z-10">
        <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-6 px-5 py-4 md:px-10">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Kafe Kaleng, beranda">
                <x-logo size="h-12 w-auto md:h-14" />
                <span class="display hidden text-xl sm:inline">Kafe Kaleng</span>
            </a>
            <nav class="flex items-center gap-5 text-sm font-semibold md:gap-8 md:text-base" aria-label="Halaman">
                <a href="#menu" class="hover:underline hover:underline-offset-8">Menu</a>
                <a href="#lokasi" class="hover:underline hover:underline-offset-8">Lokasi</a>
                <a href="{{ route('login') }}" class="btn btn-light btn-sm">Masuk</a>
            </nav>
        </div>
    </header>

    <div class="px-5 pb-10 pt-8 md:absolute md:inset-x-0 md:bottom-0 md:px-10 md:pb-14 md:pt-0">
        <div class="mx-auto max-w-[1600px]">
            <h1 class="hero-title flex flex-col text-[22vw] sm:text-[19vw] md:flex-row md:justify-between md:text-[11vw]">
                <span>Makan</span><span class="md:text-right">Ngopi</span>
            </h1>
            <p class="hero-script mt-2 text-right text-[2rem] md:-mt-1 md:text-[clamp(2rem,3.8vw,4rem)]">Selamat datang</p>
        </div>
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
            <p class="script -mt-1 ml-8 text-4xl text-clay-dark">ke Jl. Jamin Ginting</p>
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

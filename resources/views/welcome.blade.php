@extends('layouts.guest')
@section('title', 'Kafe Kaleng — Kafe Vintage Ramah Kantong, Medan')

@section('content')
{{-- Navbar translusen --}}
<header class="glass fixed inset-x-0 top-0 z-50 border-b border-bark/10">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-3">
        <a href="{{ url('/') }}" class="flex items-center gap-3"><x-logo size="size-10" /><span class="display text-xl">KAFE KALENG</span></a>
        <nav class="hidden items-center gap-7 text-sm font-semibold text-bark-dark md:flex" aria-label="Halaman">
            <a href="#tentang" class="hover:text-bean">Tentang</a>
            <a href="#menu" class="hover:text-bean">Menu</a>
            <a href="#sistem" class="hover:text-bean">Sistem</a>
            <a href="#lokasi" class="hover:text-bean">Lokasi</a>
        </nav>
        <a href="{{ url('/login') }}" class="btn btn-dark btn-sm">Masuk <x-icon name="arrow" size="size-4" /></a>
    </div>
</header>

{{-- HERO --}}
<section class="relative isolate overflow-hidden bg-bean pt-28 text-cream grain">
    <div class="sunburst absolute inset-x-0 bottom-0 -z-10 h-[75%] opacity-90" aria-hidden="true"></div>
    <div class="mx-auto grid max-w-6xl items-center gap-10 px-5 pb-24 md:grid-cols-[1.15fr_.85fr] md:pb-32">
        <div>
            <p class="rise inline-flex items-center gap-2 rounded-full border border-sand/30 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[.16em] text-sand" style="--i:0">
                <x-icon name="pin" size="size-3.5" /> Jl. Jamin Ginting, Medan
            </p>
            <h1 class="display rise mt-6 text-5xl text-cream sm:text-6xl md:text-7xl" style="--i:1">
                Kafe vintage,<br><span class="text-amber">ramah kantong.</span>
            </h1>
            <p class="rise mt-6 max-w-lg text-lg text-cream/75" style="--i:2">
                Nongkrong di antara pelat besi antik dan aroma kopi sejak 2015. Menu rumahan enak, harga mahasiswa — dekat kampus USU.
            </p>
            <div class="rise mt-9 flex flex-wrap gap-3" style="--i:3">
                <a href="#menu" class="btn btn-primary btn-lg">Lihat menu</a>
                <a href="{{ url('/login') }}" class="btn btn-lg border-cream/25 text-cream hover:bg-white/10">Masuk staf</a>
            </div>
        </div>
        <div class="rise relative mx-auto aspect-square w-full max-w-sm" style="--i:2">
            <div class="absolute inset-0 rounded-full bg-amber/20 blur-3xl"></div>
            <x-logo size="size-full" class="relative drop-shadow-[0_30px_60px_rgba(237,162,74,.35)]" />
        </div>
    </div>
    <div class="stripes h-3 opacity-80" aria-hidden="true"></div>
</section>

{{-- TENTANG --}}
<section id="tentang" class="mx-auto max-w-6xl scroll-mt-20 px-5 py-20">
    <div class="grid gap-10 md:grid-cols-3">
        <div class="md:col-span-1">
            <p class="eyebrow">Tentang kami</p>
            <h2 class="display mt-3 text-4xl">Tempat singgah yang bikin betah.</h2>
        </div>
        <div class="grid gap-5 sm:grid-cols-3 md:col-span-2">
            @foreach ([['Sejak 2015', 'Hampir sepuluh tahun menemani mahasiswa dan warga Medan ngopi santai.', 'clock'], ['Menu rumahan', 'Makanan dan minuman sederhana yang enak, dengan harga yang tetap ramah.', 'coffee'], ['Barang antik', 'Dekorasi pelat vintage, motor klasik, dan barang antik yang juga bisa dibawa pulang.', 'star']] as $i => [$t, $d, $ic])
                <article class="card card-pad rise" style="--i:{{ $i }}">
                    <div class="grid size-11 place-items-center rounded-2xl bg-amber-soft text-amber-deep"><x-icon :name="$ic" /></div>
                    <h3 class="mt-4 text-lg font-bold">{{ $t }}</h3>
                    <p class="mt-1.5 text-sm text-bark-dark">{{ $d }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- MENU (contoh statis; sambungkan ke tabel menus bila ingin dinamis) --}}
<section id="menu" class="scroll-mt-20 bg-parchment py-20">
    <div class="mx-auto max-w-6xl px-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="eyebrow">Favorit pelanggan</p><h2 class="display mt-3 text-4xl">Mulai dari 10 ribuan.</h2></div>
            <p class="max-w-sm text-sm text-bark-dark">Daftar harga lengkap tersedia di kasir. Menu di bawah hanya contoh tampilan.</p>
        </div>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Kopi Susu Kaleng', 'Kopi', '#eda24a'], ['Es Teh Manis', 'Non-kopi', '#d8b476'], ['Mie Goreng Rumahan', 'Makanan', '#e0662b'], ['Roti Bakar', 'Camilan', '#7b654d']] as $i => [$n, $k, $c])
                <article class="card group overflow-hidden rise" style="--i:{{ $i }}">
                    <div class="relative grid aspect-[4/3] place-items-center overflow-hidden" style="background:{{ $c }}22">
                        <div class="sunburst absolute inset-0 opacity-60"></div>
                        <x-icon name="coffee" size="size-16" class="relative text-bean/80 transition duration-500 group-hover:scale-110 group-hover:-rotate-6" />
                    </div>
                    <div class="p-4"><p class="eyebrow">{{ $k }}</p><h3 class="mt-1 font-bold">{{ $n }}</h3></div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- SISTEM --}}
<section id="sistem" class="mx-auto max-w-6xl scroll-mt-20 px-5 py-20">
    <p class="eyebrow">Sistem manajemen</p>
    <h2 class="display mt-3 max-w-2xl text-4xl">Satu aplikasi, tiga peran.</h2>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ([
            ['Admin', 'Kelola menu, kategori, bahan baku, resep, dan stok harian.', 'package', '/admin/dashboard'],
            ['Kasir', 'Catat pesanan cepat, hitung kembalian, cetak struk — stok berkurang otomatis.', 'cart', '/kasir/pos'],
            ['Owner', 'Pantau penjualan, laba-rugi, dan stok kritis lewat grafik yang jelas.', 'chart', '/owner/dashboard'],
        ] as $i => [$r, $d, $ic, $u])
            <a href="{{ url('/login') }}" class="card card-pad group rise block transition duration-300 hover:-translate-y-1 hover:shadow-xl active:scale-[.98]" style="--i:{{ $i }}">
                <div class="grid size-12 place-items-center rounded-2xl bg-bean text-amber"><x-icon :name="$ic" /></div>
                <h3 class="mt-5 text-xl font-bold">{{ $r }}</h3>
                <p class="mt-2 text-sm text-bark-dark">{{ $d }}</p>
                <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-deep">Masuk sebagai {{ strtolower($r) }} <x-icon name="arrow" size="size-4" class="transition group-hover:translate-x-1" /></span>
            </a>
        @endforeach
    </div>
</section>

{{-- LOKASI + footer --}}
<footer id="lokasi" class="scroll-mt-20 bg-bean text-cream">
    <div class="stripes h-2 opacity-70"></div>
    <div class="mx-auto grid max-w-6xl gap-8 px-5 py-14 md:grid-cols-[1.2fr_1fr_1fr]">
        <div>
            <div class="flex items-center gap-3"><x-logo size="size-12" /><span class="display text-2xl">KAFE KALENG</span></div>
            <p class="mt-4 max-w-xs text-sm text-cream/65">Kafe vintage ramah kantong. Menu rumahan enak, jual barang antik.</p>
        </div>
        <div>
            <p class="eyebrow !text-sand">Alamat</p>
            <p class="mt-3 text-sm text-cream/80">Jl. Jamin Ginting Gg. Senina No.11<br>Medan, Indonesia 20155</p>
        </div>
        <div>
            <p class="eyebrow !text-sand">Sosial media</p>
            <a href="https://instagram.com/kafekaleng_medan" class="mt-3 inline-block text-sm text-cream/80 underline decoration-amber/60 underline-offset-4 hover:text-amber" target="_blank" rel="noopener">@kafekaleng_medan</a>
        </div>
    </div>
    <p class="border-t border-white/10 px-5 py-5 text-center text-xs text-cream/45">© {{ date('Y') }} Kafe Kaleng · Proyek Tubes MSBD</p>
</footer>
@endsection

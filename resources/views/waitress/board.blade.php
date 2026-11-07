@extends('layouts.app')
@section('title', 'Antar Pesanan | Kafe Kaleng')
@push('head')<meta http-equiv="refresh" content="30">@endpush

@section('content')
<x-page-head title="Antar" script="pesanan siap">
    <span class="muted text-sm">Halaman menyegarkan diri tiap 30 detik</span>
</x-page-head>

<div class="mt-10 grid gap-12 lg:grid-cols-[1.4fr_1fr] lg:gap-0">
    <section class="lg:pr-12" aria-labelledby="g-ready">
        <h2 id="g-ready" class="script border-b-2 border-cocoa pb-2 text-4xl">Siap diantar <span class="num text-2xl text-muted">({{ $ready->count() }})</span></h2>
        @forelse ($ready as $t)
            <article class="border-b border-sand-2 py-6">
                <div class="flex items-baseline justify-between gap-3">
                    <h3 class="display text-2xl">{{ $t->table_no ? 'Meja '.$t->table_no : 'Bawa pulang' }}</h3>
                    <p class="text-sm font-bold text-muted">{{ $t->number }} · {{ $t->transaction_date?->format('H:i') }}</p>
                </div>
                <ul class="mt-3 space-y-1.5 text-lg">
                    @foreach ($t->details as $d)
                        <li class="flex gap-3"><span class="num w-9 font-extrabold text-clay">{{ $d->qty }}×</span><span>{{ $d->menu->name ?? 'Menu dihapus' }}</span></li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('waitress.serve', $t) }}" class="mt-5">@csrf
                    <button class="btn btn-primary btn-lg w-full sm:w-auto">Sudah diantar</button>
                </form>
            </article>
        @empty
            <p class="band mt-6 px-5 py-5 font-semibold" role="status">Belum ada pesanan yang siap diantar.</p>
        @endforelse
    </section>

    <section class="lg:border-l lg:border-sand-2 lg:pl-12" aria-labelledby="g-coming">
        <h2 id="g-coming" class="script border-b-2 border-cocoa pb-2 text-4xl">Masih di dapur <span class="num text-2xl text-muted">({{ $coming->count() }})</span></h2>
        <ul class="mt-2 divide-y divide-sand">
            @forelse ($coming as $t)
                <li class="py-3"><span class="num font-bold">{{ $t->number }}</span> <span class="muted">· {{ $t->table_no ? 'Meja '.$t->table_no : 'Bawa pulang' }}</span>
                    <span class="status {{ $t->order_status === 'preparing' ? 'status-warn' : 'status-mute' }} ml-2">{{ \App\Models\Transaction::ORDER_LABEL[$t->order_status] }}</span></li>
            @empty
                <li class="muted py-4">Kosong.</li>
            @endforelse
        </ul>

        @if ($served->isNotEmpty())
            <h2 class="script mt-12 text-4xl">Sudah diantar</h2>
            <ul class="mt-2 divide-y divide-sand">
                @foreach ($served as $t)
                    <li class="py-3"><span class="num font-bold">{{ $t->number }}</span> <span class="muted">· {{ $t->table_no ? 'Meja '.$t->table_no : 'Bawa pulang' }}</span></li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection

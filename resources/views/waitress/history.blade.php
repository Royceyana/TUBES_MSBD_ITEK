@extends('layouts.app')
@section('title', 'Riwayat Antar | Kafe Kaleng')

@section('content')
<x-page-head title="Riwayat" script="pesanan diantar">
    <a href="{{ route('waitress.board') }}" class="btn btn-line btn-sm">Kembali ke antrian</a>
</x-page-head>

<div class="mt-10 flex flex-wrap items-end gap-4">
    <div class="min-w-52 flex-1 md:max-w-sm">
        <label for="q" class="label">Cari</label>
        <input id="q" type="search" data-search="#served-rows tr" data-empty="#served-none" class="input" placeholder="Nomor, meja, atau menu">
    </div>
    <form method="GET" action="{{ route('waitress.history') }}">
        <label for="date" class="label">Tanggal diantar</label>
        <input id="date" type="date" name="date" class="input !w-auto" value="{{ $date }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()">
    </form>
    <p class="muted pb-3 text-sm"><span class="num font-bold text-cocoa">{{ $orders->count() }}</span> pesanan diantar</p>
</div>

@if ($orders->isNotEmpty())
<div class="mt-8 overflow-x-auto">
    <table class="tbl min-w-[44rem]">
        <thead><tr><th>No</th><th>Jam diantar</th><th>Tempat</th><th>Pesanan</th><th>Diantar oleh</th><th>Status</th></tr></thead>
        <tbody id="served-rows">
            @foreach ($orders as $t)
                <tr>
                    <td class="num font-bold">{{ $t->number }}</td>
                    <td class="num">{{ $t->served_time }}</td>
                    <td>{{ $t->place_label }}</td>
                    <td class="text-muted">
                        <ul class="space-y-0.5">
                            @foreach ($t->lines as $d)
                                <li><span class="num font-bold text-clay">{{ $d->qty }}×</span> {{ $d->menu }}</li>
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $t->pelayan ?? '-' }}</td>
                    <td><span class="status status-ok">Sudah diantar</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
<p id="served-none" class="muted mt-8 text-lg" @if ($orders->isNotEmpty()) hidden @endif>Belum ada pesanan yang diantar pada tanggal ini.</p>
@endsection

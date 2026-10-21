@extends('layouts.app')
@section('title', 'Laporan Stok — Kafe Kaleng')
@section('page-heading', 'Laporan Stok')
@section('actions')<a href="#" class="btn btn-primary btn-sm" onclick="toast('Mengunduh .xlsx (demo)');return false"><x-icon name="download" size="size-4" /> Export Excel</a>@endsection

@section('content')
<div class="card rise overflow-hidden">
    <div class="overflow-x-auto p-2 md:p-4">
        <table class="tbl min-w-[40rem]">
            <thead><tr><th>Bahan</th><th class="r">Masuk</th><th class="r">Keluar</th><th class="r">Stok akhir</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($rows as $r)
                    @php $empty = $r->stock_qty <= 0; $low = !$empty && $r->stock_qty <= $r->min_stock; @endphp
                    <tr>
                        <td class="font-semibold">{{ $r->name }}</td>
                        <td class="num r text-leaf">+{{ $r->in + 0 }} {{ $r->unit }}</td>
                        <td class="num r text-amber-deep">−{{ $r->out + 0 }} {{ $r->unit }}</td>
                        <td class="num r font-bold">{{ $r->stock_qty + 0 }} {{ $r->unit }}</td>
                        <td><span class="badge {{ $empty ? 'badge-bad' : ($low ? 'badge-warn' : 'badge-ok') }}">{{ $empty ? 'Habis' : ($low ? 'Menipis' : 'Aman') }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

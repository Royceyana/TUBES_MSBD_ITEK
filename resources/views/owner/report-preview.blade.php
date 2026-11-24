@extends('layouts.app')
@section('title', 'Pratinjau '.$title.' | Kafe Kaleng')

@push('head')
<style>
    /* Gaya pratinjau spreadsheet (berdiri sendiri, tidak bergantung build Tailwind). */
    .xl-wrap { border: 1px solid #bfa894; background: #fff; overflow: auto; max-height: 70vh; }
    .xl { border-collapse: collapse; table-layout: fixed; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #2b1a12; }
    .xl th { background: #efe3d5; color: #6b5b4f; font-weight: 600; font-size: 12px; text-align: center; border: 1px solid #d9c8b6; padding: 3px 6px; position: sticky; top: 0; z-index: 2; }
    .xl th.rowh { position: sticky; left: 0; top: auto; z-index: 1; width: 42px; min-width: 42px; }
    .xl th.corner { left: 0; z-index: 3; }
    .xl td { height: 26px; padding: 4px 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: 1px solid #f1e9e0; }
    .xl td.b { border-color: #bfa894; }
    .xl td.title { font-size: 16px; font-weight: 700; color: #5a2410; }
    .xl td.note { font-style: italic; font-size: 12px; color: #6b5b4f; }
    .xl td.label { font-weight: 700; }
    .xl td.head { background: #5a2410; color: #fff; font-weight: 700; text-align: center; white-space: normal; }
    .xl td.total { background: #efe3d5; font-weight: 700; }
    .xl td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .xl td.center { text-align: center; }
    .xl-tab { display: inline-block; margin-top: -1px; padding: 4px 18px; border: 1px solid #bfa894; border-top: 3px solid #5a2410; background: #fff; font: 600 12px Arial, sans-serif; }
</style>
@endpush

@section('content')
<x-page-head title="Pratinjau" :script="$title">
    <a href="{{ $backUrl }}" class="btn btn-line btn-sm">Kembali</a>
    <a href="{{ $downloadUrl }}" class="btn btn-dark btn-sm">Unduh Excel (.xlsx)</a>
</x-page-head>

<p class="help mt-6">Ini tampilan isi file Excel yang akan diunduh. Periksa dulu, lalu klik <strong>Unduh Excel</strong>. Nama file: <strong>{{ $file }}</strong></p>

<div class="mt-6">
    <div class="xl-wrap">
        <table class="xl" aria-label="Pratinjau spreadsheet {{ $title }}">
            <colgroup>
                <col style="width:42px">
                @foreach ($sheet['widths'] as $w)<col style="width:{{ $w }}px">@endforeach
            </colgroup>
            <thead>
                <tr>
                    <th class="rowh corner"></th>
                    @foreach ($sheet['letters'] as $l)<th scope="col">{{ $l }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($sheet['rows'] as $n => $cells)
                    <tr>
                        <th class="rowh" scope="row">{{ $n }}</th>
                        @foreach ($cells as $c)
                            @if ($c === null)
                                <td></td>
                            @else
                                <td class="{{ $c['c'] }}" @if ($c['span'] > 1) colspan="{{ $c['span'] }}" @endif>{{ $c['t'] }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <span class="xl-tab">{{ $sheet['name'] }}</span>
</div>

<div class="mt-8 flex flex-wrap items-center gap-2">
    <a href="{{ $downloadUrl }}" class="btn btn-dark">Unduh Excel (.xlsx)</a>
    <a href="{{ $backUrl }}" class="btn btn-line">Kembali ke laporan</a>
</div>
@endsection

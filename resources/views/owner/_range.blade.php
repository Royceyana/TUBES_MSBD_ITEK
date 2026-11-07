{{-- Pemilih rentang tanggal untuk laporan. Variabel: $from, $to, $action --}}
<form method="GET" action="{{ $action }}" class="mt-10 flex flex-wrap items-end gap-4">
    <div><label class="label" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from }}" class="input !w-auto"></div>
    <div><label class="label" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $to }}" class="input !w-auto"></div>
    <button class="btn btn-dark">Tampilkan</button>
</form>

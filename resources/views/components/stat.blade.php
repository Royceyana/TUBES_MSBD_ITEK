@props(['label', 'value', 'money' => false, 'hint' => null])
{{-- Angka ringkasan datar; dipakai di dalam <dl class="stats"> --}}
<div>
    <dt>{{ $label }}</dt>
    <dd class="num">{{ $money ? 'Rp '.number_format($value, 0, ',', '.') : number_format($value, 0, ',', '.') }}@if ($hint)<span class="sub">{{ $hint }}</span>@endif</dd>
</div>

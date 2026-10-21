@props(['label', 'value', 'icon' => 'chart', 'money' => false, 'hint' => null, 'tone' => 'amber', 'i' => 0])
@php
    $tones = ['amber' => 'bg-amber-soft text-amber-deep', 'leaf' => 'bg-leaf-soft text-leaf', 'wine' => 'bg-wine-soft text-wine', 'bean' => 'bg-bean text-amber'];
@endphp
<div class="card card-pad rise" style="--i:{{ $i }}">
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-bark-dark">{{ $label }}</p>
        <span class="grid size-10 place-items-center rounded-2xl {{ $tones[$tone] ?? $tones['amber'] }}"><x-icon :name="$icon" size="size-5" /></span>
    </div>
    <p class="num mt-3 text-3xl font-bold tracking-tight" data-count="{{ $value }}" @if($money) data-money @endif>{{ $money ? 'Rp '.number_format($value, 0, ',', '.') : number_format($value, 0, ',', '.') }}</p>
    @if ($hint)<p class="mt-1.5 text-xs text-bark">{{ $hint }}</p>@endif
</div>

@props(['title', 'script' => null])
{{-- Judul tebal + kata miring yang menggantung (motif "Menu / drinks" dari acuan). Slot = aksi di kanan. --}}
<div class="page-head">
    <div>
        <h1>{{ $title }}</h1>
        @if ($script)<span class="script">{{ $script }}</span>@endif
    </div>
    @if (trim((string) $slot) !== '')<div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>

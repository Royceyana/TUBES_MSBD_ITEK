@props(['id', 'title', 'large' => false])
<dialog id="{{ $id }}" class="modal {{ $large ? 'modal-lg' : '' }}" aria-labelledby="{{ $id }}-t">
    <div class="sheet">
        <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-bark/10 bg-paper/90 px-6 py-4 backdrop-blur">
            <h2 id="{{ $id }}-t" data-modal-title class="text-lg font-bold">{{ $title }}</h2>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" size="size-4" /></button>
        </div>
        <div class="p-6">{{ $slot }}</div>
    </div>
</dialog>

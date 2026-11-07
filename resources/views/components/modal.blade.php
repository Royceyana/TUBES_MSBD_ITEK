@props(['id', 'title', 'large' => false])
<dialog id="{{ $id }}" class="modal {{ $large ? 'modal-lg' : '' }}" aria-labelledby="{{ $id }}-t">
    <div class="sheet">
        <div class="flex items-center justify-between gap-3 border-b border-sand-2 px-7 py-5">
            <h2 id="{{ $id }}-t" data-modal-title class="display text-2xl">{{ $title }}</h2>
            <button type="button" class="btn btn-line btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" size="size-4" /></button>
        </div>
        <div class="p-7">{{ $slot }}</div>
    </div>
</dialog>

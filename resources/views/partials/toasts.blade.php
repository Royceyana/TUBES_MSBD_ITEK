{{-- Pesan flash & galat validasi dari controller jadi toast --}}
@if (session('success') || $errors->any())
<script>
    addEventListener('DOMContentLoaded', () => {
        @if (session('success')) toast(@json(session('success'))); @endif
        @if ($errors->any()) toast(@json($errors->first()), true); @endif
    });
</script>
@endif

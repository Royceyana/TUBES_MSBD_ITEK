@props(['size' => 'size-10'])
{{-- Ganti dengan logo asli: simpan file di public/images/logo.png --}}
@if (file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="Kafe Kaleng" {{ $attributes->class([$size, 'rounded-full object-cover shrink-0']) }}>
@else
    <svg {{ $attributes->class([$size, 'shrink-0']) }} viewBox="0 0 64 64" role="img" aria-label="Kafe Kaleng">
        <circle cx="32" cy="32" r="31" fill="#0c0f12"/>
        <circle cx="32" cy="32" r="28.5" fill="none" stroke="#d8b476" stroke-width="1.2"/>
        <g stroke="#eda24a" stroke-width="2.2" stroke-linecap="round">
            <path d="M10 36 15 33 M13 28l5 2 M19 21l4 3 M27 17l2 4 M37 17l-2 4 M45 21l-4 3 M51 28l-5 2 M54 36l-5-3"/>
        </g>
        <path d="M12 42a20 20 0 0 1 40 0z" fill="#eda24a" opacity=".28"/>
        <rect x="22" y="22" width="19" height="22" rx="3.5" fill="#1b130c" stroke="#f7f3e4" stroke-width="1.8"/>
        <path d="M41 27h3.5a3 3 0 0 1 0 7H41" fill="none" stroke="#f7f3e4" stroke-width="1.8"/>
        <circle cx="31.5" cy="32" r="5" fill="#e0662b"/><circle cx="30" cy="30.5" r="1.8" fill="#fbe9cf"/>
        <path d="M22 19h19" stroke="#f7f3e4" stroke-width="2" stroke-linecap="round"/>
        <path d="M14 47h36 M17 51h30" stroke="#d8b476" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
@endif

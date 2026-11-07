<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2c1209">
<link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
{{-- Archivo Black: judul tebal ala acuan. Cormorant Garamond miring: kata tulisan tangan. Instrument Sans: isi. --}}
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=archivo-black:400|cormorant-garamond:500i|instrument-sans:400,500,600,700&display=swap">
@vite(['resources/css/app.css', 'resources/js/app.js'])

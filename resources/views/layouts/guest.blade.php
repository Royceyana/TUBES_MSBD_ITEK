<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>@yield('title', 'Kafe Kaleng')</title>
</head>
<body class="min-h-dvh bg-white text-cocoa">
    @yield('content')
    @include('partials.toasts')
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id" data-theme="dark" data-glow="on"
    x-data="{ dark: localStorage.getItem('ib-theme') !== 'light', menuOpen: false }"
    :data-theme="dark ? 'dark' : 'light'"
    x-init="$watch('dark', v => localStorage.setItem('ib-theme', v ? 'dark' : 'light'))">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Indo Blader' }} — Aggressive Inline Indonesia</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bebas-neue:400" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="grain" aria-hidden="true"></div>

    @include('components.nav')

    <main class="fade">
        {{ $slot }}
    </main>

    @include('components.footer')

    @livewireScripts
</body>
</html>

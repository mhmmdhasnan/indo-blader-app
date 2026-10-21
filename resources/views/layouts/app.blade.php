<!DOCTYPE html>
<html lang="id" data-theme="dark" data-glow="on"
    x-data="{ dark: localStorage.getItem('ib-theme') !== 'light', menuOpen: false }"
    :data-theme="dark ? 'dark' : 'light'"
    x-init="$watch('dark', v => localStorage.setItem('ib-theme', v ? 'dark' : 'light'))">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = $title ?? 'FRAMEBLADESCORE';
        // Judul halaman Home sudah memuat tagline — jangan ditumpuk dua kali.
        $fullTitle = str_contains($pageTitle, 'Aggressive Inline Indonesia') ? $pageTitle : $pageTitle . ' — Aggressive Inline Indonesia';
        $metaDescription = $description ?? 'Live score, registrasi event, bracket, dan ranking untuk komunitas aggressive inline skating Indonesia.';
        $ogImage = $ogImage ?? asset('images/og-image.png');
    @endphp
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">

    {{-- Pratinjau link (WhatsApp, Telegram, Instagram, dll.) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="FRAMEBLADESCORE">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
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

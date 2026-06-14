@php
$navLinks = [
    ['label' => 'Home',     'route' => 'home'],
    ['label' => 'Events',   'route' => 'events'],
    ['label' => 'Rankings', 'route' => 'rankings'],
    ['label' => 'Riders',   'route' => 'riders'],
    ['label' => 'Gallery',  'route' => 'gallery'],
    ['label' => 'Results',  'route' => 'bracket'],
    ['label' => 'About',    'route' => 'about'],
];
$current = Route::currentRouteName();
@endphp

<header style="position:sticky;top:0;z-index:200;" x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 24">
    <div class="between" style="
        padding: 0 clamp(16px,3vw,30px);
        height: 70px;
        border-bottom: 2px solid var(--ink);
        transition: background .2s;
        backdrop-filter: blur(10px);
    " :style="scrolled ? 'background:color-mix(in srgb,var(--bg) 86%,transparent)' : 'background:var(--bg)'">

        <x-logo :size="36" />

        {{-- Desktop Nav --}}
        <nav class="nav-links flex" style="gap:4px;align-items:center;">
            @foreach($navLinks as $link)
                <a href="{{ route($link['route']) }}" class="label" style="
                    font-size:12px;padding:9px 13px;position:relative;
                    color:{{ $current === $link['route'] ? 'var(--ink)' : 'var(--ink-dim)' }};
                    border-bottom: 2px solid {{ $current === $link['route'] ? 'var(--lime)' : 'transparent' }};
                    transition: color .15s, border-color .15s;
                " onmouseover="this.style.color='var(--ink)'" onmouseout="this.style.color='{{ $current === $link['route'] ? 'var(--ink)' : 'var(--ink-dim)' }}'">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex" style="gap:10px;align-items:center;">
            {{-- Live button --}}
            <a href="{{ route('live') }}" class="flex label" style="align-items:center;gap:7px;font-size:12px;color:var(--red);padding:8px 12px;border:2px solid var(--red);border-radius:3px;">
                <span class="live-dot"></span>LIVE
            </a>

            {{-- Theme toggle --}}
            <button @click="dark = !dark" class="center" style="width:40px;height:40px;border:2px solid var(--ink);border-radius:3px;" aria-label="Toggle theme">
                <span x-show="dark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4.5"/><path d="M12 1v3M12 20v3M4 12H1M23 12h-3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/></svg>
                </span>
                <span x-show="!dark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
                </span>
            </button>

            <a href="{{ route('register') }}" class="btn btn-lime btn-sm nav-reg">REGISTER</a>

            {{-- Mobile burger --}}
            <button class="burger center" @click="$dispatch('toggle-menu')" style="width:40px;height:40px;border:2px solid var(--ink);border-radius:3px;" aria-label="Menu">
                <div class="col" style="gap:4px;">
                    <span style="width:16px;height:2px;background:var(--ink);display:block;"></span>
                    <span style="width:16px;height:2px;background:var(--ink);display:block;"></span>
                    <span style="width:16px;height:2px;background:var(--ink);display:block;"></span>
                </div>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-data="{ open: false }" @toggle-menu.window="open = !open" x-show="open" x-transition
        style="background:var(--bg);border-bottom:2px solid var(--ink);padding:10px 20px 20px;">
        @foreach(array_merge($navLinks, [['label' => 'Live Scoring', 'route' => 'live'], ['label' => 'Register', 'route' => 'register'], ['label' => 'Admin', 'route' => 'admin']]) as $link)
            <a href="{{ route($link['route']) }}" class="display" style="display:block;font-size:28px;padding:10px 0;border-bottom:1px solid var(--line);">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</header>

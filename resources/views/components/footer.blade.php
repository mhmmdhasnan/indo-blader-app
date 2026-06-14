@php
$navLinks = [
    ['label' => 'Home', 'route' => 'home'],
    ['label' => 'Events', 'route' => 'events'],
    ['label' => 'Rankings', 'route' => 'rankings'],
    ['label' => 'Riders', 'route' => 'riders'],
    ['label' => 'Gallery', 'route' => 'gallery'],
    ['label' => 'Results', 'route' => 'bracket'],
    ['label' => 'About', 'route' => 'about'],
];
@endphp
<footer style="border-top:2px solid var(--ink);background:var(--bg-2);margin-top:0;">
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap section" style="padding:64px 0;">
            <div class="footer-grid" style="display:grid;grid-template-columns:1.6fr 1fr 1fr 1.4fr;gap:40px 24px;">
                <div>
                    <x-logo :size="46" />
                    <p class="dim" style="margin-top:18px;max-width:280px;font-size:14px;line-height:1.5;">
                        One wheel, one family. The national home of aggressive inline skating in Indonesia.
                    </p>
                    <div class="flex" style="gap:8px;margin-top:18px;">
                        @foreach(['IG','YT','TT','X'] as $s)
                            <a href="#" class="center label" style="width:38px;height:38px;border:2px solid var(--ink);border-radius:3px;font-size:11px;">{{ $s }}</a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="kicker" style="margin-bottom:16px;">EXPLORE</div>
                    <div class="col" style="gap:11px;">
                        @foreach($navLinks as $link)
                            <a href="{{ route($link['route']) }}" class="label dim" style="font-size:13px;">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="kicker" style="margin-bottom:16px;">COMPETE</div>
                    <div class="col" style="gap:11px;">
                        <a href="{{ route('register') }}" class="label dim" style="font-size:13px;">Register</a>
                        <a href="{{ route('live') }}" class="label dim" style="font-size:13px;">Live Scoring</a>
                        <a href="{{ route('bracket') }}" class="label dim" style="font-size:13px;">Brackets</a>
                        <a href="{{ route('events') }}" class="label dim" style="font-size:13px;">Rules</a>
                    </div>
                </div>

                <div>
                    <div class="kicker" style="margin-bottom:16px;">JOIN THE FAMILY</div>
                    <p class="dim" style="font-size:13px;margin-bottom:12px;">Event drops, results, and rider news.</p>
                    <div class="flex" style="border:2px solid var(--ink);border-radius:3px;">
                        <input placeholder="your@email.com" style="flex:1;border:none;background:transparent;color:var(--ink);padding:12px 14px;outline:none;font-size:13px;" />
                        <button class="center" style="background:var(--lime);color:#0a0a0b;padding:0 16px;font-weight:900;font-size:16px;">→</button>
                    </div>
                    <div class="flex gap-s" style="margin-top:18px;flex-wrap:wrap;">
                        <a href="{{ route('admin') }}" class="badge badge-out">ADMIN PANEL</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wrap between" style="padding:20px 0;flex-wrap:wrap;gap:10px;">
        <span class="mono dim" style="font-size:11px;">© 2026 INDO BLADER — ONE WHEEL ONE FAMILY</span>
        <span class="mono dim" style="font-size:11px;">BUILT WITH LARAVEL 12 + LIVEWIRE</span>
    </div>
</footer>

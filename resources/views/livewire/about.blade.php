<div>
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap" style="padding:48px 0 40px;">
            <div class="eyebrow-row" style="margin-bottom:12px;">
                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">ABOUT /</span>
                <span class="kicker">INDO BLADER</span>
            </div>
            <h1 class="display" style="font-size:clamp(40px,7vw,84px);">One Wheel,<br>One Family.</h1>
        </div>
    </div>

    <div class="wrap section" style="display:grid;grid-template-columns:1.4fr 1fr;gap:60px;" class="prof-grid">
        <div>
            <h2 class="display" style="font-size:clamp(34px,5vw,64px);margin-bottom:24px;">About Indo Blader</h2>
            <div class="col" style="gap:20px;font-size:16px;line-height:1.7;">
                <p>Indo Blader is the national organizing body for competitive aggressive inline skating in Indonesia. Founded in 2020, we run the national circuit, maintain the official ranking, and publish competition results.</p>
                <p>Our mission: build a legitimate, professional competition structure that elevates Indonesian aggressive inline to an international standard — while keeping the core community spirit alive.</p>
                <p>Since Vol. 01, the circuit has grown from a single Jakarta stop to five national events, with over 500 registered competitors and a combined prize pool exceeding Rp 500 juta per season.</p>
            </div>
        </div>
        <div class="col" style="gap:20px;">
            @foreach([
                ['2020','Founded in Jakarta — Vol. 01 with 32 riders.'],
                ['2021','Expanded to Bandung and Bali.'],
                ['2022','Official ranking system launched.'],
                ['2023','First mega ramp event — Vert Attack Vol. 01.'],
                ['2024','500+ registered competitors.'],
                ['2025','National TV broadcast of Nationals final.'],
                ['2026','Season 6 — biggest circuit yet.'],
            ] as [$yr,$txt])
                <div class="flex" style="gap:18px;align-items:flex-start;">
                    <span class="display" style="font-size:28px;color:var(--lime);flex-shrink:0;width:52px;">{{ $yr }}</span>
                    <p class="dim" style="font-size:14px;line-height:1.5;padding-top:4px;">{{ $txt }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <section style="border-top:2px solid var(--ink);border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap section">
            <div class="between" style="margin-bottom:34px;align-items:flex-end;gap:20px;">
                <div>
                    <div class="eyebrow-row" style="margin-bottom:10px;"><span class="kicker">THE TEAM</span></div>
                    <h2 class="display" style="font-size:clamp(34px,5vw,64px);">Staff & Judges</h2>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
                @foreach([
                    ['A. Hidayat','HEAD JUDGE','Jakarta'],
                    ['M. Santoso','JUDGE','Surabaya'],
                    ['L. Pratiwi','JUDGE','Bandung'],
                    ['K. Wijaya','JUDGE','Semarang'],
                    ['R. Gunawan','EVENT DIRECTOR','Jakarta'],
                    ['S. Dewi','MEDIA COORDINATOR','Bali'],
                ] as [$name,$role,$city])
                    <div class="panel" style="padding:18px;">
                        <div class="flex" style="align-items:center;gap:14px;margin-bottom:12px;">
                            <x-avatar :initials="collect(explode(' ',$name))->map(fn($w)=>$w[0])->take(2)->implode('')" :size="48" />
                            <div class="col">
                                <span class="label" style="font-size:15px;">{{ $name }}</span>
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.1em;">{{ $city }}</span>
                            </div>
                        </div>
                        <span class="badge badge-out" style="font-size:9px;">{{ $role }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section style="border-top:2px solid var(--ink);background:var(--lime);color:#0a0a0b;position:relative;overflow:hidden;">
        <div style="position:absolute;inset:0;background-image:radial-gradient(circle,rgba(0,0,0,0.12) 1px,transparent 1px);background-size:16px 16px;pointer-events:none;"></div>
        <div class="wrap center col" style="position:relative;padding:70px 0;text-align:center;gap:16px;">
            <span class="mono" style="font-size:12px;letter-spacing:0.3em;">GET INVOLVED</span>
            <h2 class="display" style="font-size:clamp(44px,8vw,110px);margin:0;">Join The Circuit</h2>
            <div class="flex center gap-m" style="flex-wrap:wrap;margin-top:10px;">
                <a href="{{ route('register') }}" class="btn" style="background:#0a0a0b;border-color:#0a0a0b;color:var(--lime);">Register a Rider →</a>
                <a href="{{ route('events') }}" class="btn" style="border-color:#0a0a0b;color:#0a0a0b;">Browse Events</a>
            </div>
        </div>
    </section>
</div>

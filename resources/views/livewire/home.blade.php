<div>
    {{-- ── HERO ── --}}
    @if($featured)
    <section style="position:relative;border-bottom:2px solid var(--ink);overflow:hidden;">
        <div class="ph no-label scanlines" style="position:absolute;inset:0;"></div>
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,color-mix(in srgb,var(--bg) 30%,transparent) 0%,color-mix(in srgb,var(--bg) 55%,transparent) 55%,var(--bg) 100%);"></div>
        <div style="position:absolute;inset:0;" class="halftone"></div>

        <div class="wrap" style="position:relative;padding-top:36px;padding-bottom:40px;min-height:min(78vh,660px);display:flex;flex-direction:column;">
            <div class="rise">
                <div class="flex gap-s" style="margin-bottom:20px;flex-wrap:wrap;align-items:center;">
                    <x-status-badge :status="$featured->status" />
                    <span class="badge badge-out">{{ $featured->edition }}</span>
                    <span class="mono" style="font-size:12px;color:var(--ink-dim);letter-spacing:0.12em;">● {{ strtoupper($featured->venue) }}, {{ strtoupper($featured->city) }}</span>
                    <span class="flex" style="margin-left:auto;gap:6px;">
                        @foreach($featured->categories as $cat)
                            <x-cat-badge :cat="$cat" />
                        @endforeach
                    </span>
                </div>
            </div>

            <div class="rise" style="animation-delay:60ms;">
                <h1 class="display" style="font-size:clamp(54px,11.5vw,168px);letter-spacing:0.005em;margin:0 0 4px;">
                    Indo Blader<br>
                    <span style="-webkit-text-stroke:2px var(--ink);color:transparent;">Nationals</span>
                    <span style="color:var(--lime);" class="text-glow-lime">'26</span>
                </h1>
            </div>

            <div class="rise" style="animation-delay:120ms;">
                <div class="flex" style="align-items:flex-start;gap:24px;flex-wrap:wrap;margin-top:14px;">
                    <p style="font-size:clamp(15px,1.5vw,18px);line-height:1.5;max-width:460px;">{{ $featured->blurb }}</p>
                    <div class="flex gap-m" style="flex-wrap:wrap;">
                        <a href="{{ route('register') }}" class="btn btn-lime btn-lg">Register Now →</a>
                        <a href="{{ route('live') }}" class="btn btn-ghost btn-lg">
                            <span class="live-dot" style="margin-right:6px;"></span>Watch Live
                        </a>
                    </div>
                </div>
            </div>

            <div style="flex:1;min-height:28px;"></div>

            <div class="rise" style="animation-delay:160ms;">
                <div class="between" style="align-items:flex-end;gap:24px;flex-wrap:wrap;border-top:2px solid var(--line-strong);padding-top:22px;">
                    <div class="col" style="align-items:flex-start;gap:10px;">
                        <span class="kicker">EVENT STARTS IN</span>
                        <div class="flex" style="gap:18px;align-items:flex-start;" wire:poll.10s>
                            @php
                                $target = \Carbon\Carbon::parse($featured->date->format('Y-m-d') . ' 09:00:00');
                                $diff = max(0, $target->diffInSeconds(now(), false));
                                $d = floor(max(0, $target->diffInSeconds(now())) / 86400);
                                $h = floor((max(0, $target->diffInSeconds(now())) % 86400) / 3600);
                                $m = floor((max(0, $target->diffInSeconds(now())) % 3600) / 60);
                                $s = max(0, $target->diffInSeconds(now())) % 60;
                                if ($target->isPast()) { $d=$h=$m=$s=0; }
                            @endphp
                            @foreach([[$d,'DAYS'],[$h,'HRS'],[$m,'MIN'],[$s,'SEC']] as [$val,$lbl])
                                <div class="col center countdown-item" style="min-width:76px;">
                                    <span class="display tnum" style="font-size:clamp(36px,5vw,60px);line-height:1;">{{ str_pad($val,2,'0',STR_PAD_LEFT) }}</span>
                                    <span class="mono" style="font-size:10px;letter-spacing:0.2em;color:var(--ink-dim);margin-top:4px;">{{ $lbl }}</span>
                                </div>
                                @if($lbl !== 'SEC')<span class="display dim countdown-sep" style="font-size:44px;">:</span>@endif
                            @endforeach
                        </div>
                    </div>
                    <div class="col hero-edition" style="align-items:flex-end;position:relative;">
                        <span class="display" style="font-size:clamp(54px,8vw,104px);color:transparent;-webkit-text-stroke:2px var(--line-strong);line-height:0.78;">06</span>
                        <span class="mono" style="font-size:10px;letter-spacing:0.24em;color:var(--ink-dim);">SIXTH EDITION</span>
                        <div style="position:absolute;top:-14px;right:calc(100% + 16px);">
                            <span class="sticker" style="--rot:-8deg;font-size:13px;">EST. 2020</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sponsor strip --}}
        <div style="position:relative;border-top:2px solid var(--ink);background:var(--bg);">
            <div class="wrap flex" style="align-items:center;gap:28px;padding:16px 0;flex-wrap:wrap;">
                <span class="mono" style="font-size:10px;letter-spacing:0.2em;color:var(--ink-faint);white-space:nowrap;">PRESENTED BY</span>
                @foreach($sponsors as $sp)
                    <span class="display dim" style="font-size:18px;letter-spacing:0.02em;opacity:0.55;">{{ $sp }}</span>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ── MARQUEE ── --}}
    <div class="marquee" style="background:var(--ink);color:var(--bg);padding:11px 0;--mq:30s;border-top:2px solid var(--ink);border-bottom:2px solid var(--ink);">
        <div class="marquee-inner">
            @foreach(["INDO BLADER NATIONALS '26", "AUG 22–24 · JAKARTA", "STREET · PARK · VERT", "Rp 250 JT PRIZE POOL", "REGISTRATION OPEN", "INDO BLADER NATIONALS '26", "AUG 22–24 · JAKARTA", "STREET · PARK · VERT", "Rp 250 JT PRIZE POOL", "REGISTRATION OPEN"] as $item)
                <span class="display flex" style="align-items:center;font-size:16px;padding:0 22px;white-space:nowrap;">
                    {{ $item }}<span style="margin:0 0 0 22px;color:var(--lime);font-size:12px;">✦</span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- ── UPCOMING EVENTS ── --}}
    <section class="section wrap" id="events">
        <div class="between" style="margin-bottom:34px;align-items:flex-end;gap:20px;flex-wrap:wrap;">
            <div>
                <div class="eyebrow-row" style="margin-bottom:10px;">
                    <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">01 /</span>
                    <span class="kicker">THE 2026 CIRCUIT</span>
                </div>
                <h2 class="display" style="font-size:clamp(34px,5vw,64px);">Upcoming Events</h2>
            </div>
            <a href="{{ route('events') }}" class="btn btn-ghost">All Events →</a>
        </div>
        <div class="events-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:22px;">
            @foreach($events as $ev)
                @php $pct = $ev->fill_pct; @endphp
                <div class="rise" style="animation-delay:{{ $loop->index * 70 }}ms;">
                    <a href="{{ route('events.show', $ev->slug) }}" class="panel" style="display:block;overflow:hidden;height:100%;transition:transform .15s;"
                        onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='none'">
                        <div class="ph scanlines" data-ph="{{ $ev->title }}" style="height:200px;border-bottom:2px solid var(--ink);position:relative;">
                            <div style="position:absolute;top:12px;left:12px;"><x-status-badge :status="$ev->status" /></div>
                            <div style="position:absolute;bottom:12px;right:12px;">
                                <span class="sticker" style="--rot:-4deg;font-size:13px;">{{ $ev->prize_formatted }}</span>
                            </div>
                        </div>
                        <div style="padding:18px 18px 20px;">
                            <div class="between" style="margin-bottom:8px;">
                                <span class="mono" style="font-size:11px;color:var(--ink-dim);letter-spacing:0.1em;">{{ $ev->date_label }}</span>
                                <div class="flex gap-s">
                                    @foreach($ev->categories as $cat)
                                        <x-cat-badge :cat="$cat" :sm="true" />
                                    @endforeach
                                </div>
                            </div>
                            <h3 class="display" style="font-size:30px;margin-bottom:4px;">{{ $ev->title }}</h3>
                            <div class="flex" style="align-items:center;gap:6px;margin-bottom:16px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--ink-dim)" stroke-width="1.5"><circle cx="12" cy="12" r="7"/><path d="M12 1v6M12 17v6M1 12h6M17 12h6"/></svg>
                                <span class="dim" style="font-size:13px;">{{ $ev->venue }}, {{ $ev->city }}</span>
                            </div>
                            <div class="between" style="margin-bottom:7px;">
                                <span class="mono" style="font-size:10px;color:var(--ink-dim);letter-spacing:0.1em;">SLOTS</span>
                                <span class="mono tnum" style="font-size:12px;font-weight:700;">{{ $ev->filled }}/{{ $ev->slots }}</span>
                            </div>
                            <div style="height:6px;background:var(--panel-2);border:1px solid var(--line);">
                                <div style="height:100%;width:{{ $pct }}%;background:{{ $pct > 85 ? 'var(--red)' : 'var(--lime)' }};"></div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── TOP RIDERS STRIP ── --}}
    <section style="border-top:2px solid var(--ink);border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap section">
            <div class="between" style="margin-bottom:34px;align-items:flex-end;gap:20px;flex-wrap:wrap;">
                <div>
                    <div class="eyebrow-row" style="margin-bottom:10px;">
                        <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">02 /</span>
                        <span class="kicker">NATIONAL POWER RANKING</span>
                    </div>
                    <h2 class="display" style="font-size:clamp(34px,5vw,64px);">Top Riders</h2>
                </div>
                <a href="{{ route('rankings') }}" class="btn btn-ghost">Full Rankings →</a>
            </div>
            <div class="panel" style="overflow:hidden;">
                @foreach($topRiders as $i => $r)
                    <a href="{{ route('riders.show', $r->slug) }}" class="between rider-list-row" style="
                        padding:14px 20px;
                        border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};
                        gap:12px;transition:background .12s;display:flex;align-items:center;
                    " onmouseover="this.style.background='var(--panel-2)'" onmouseout="this.style.background='transparent'">
                        <div class="flex" style="align-items:center;gap:14px;min-width:0;flex:1;">
                            <span class="display tnum" style="font-size:30px;width:46px;flex-shrink:0;color:{{ $i === 0 ? 'var(--lime)' : ($i < 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
                            <x-avatar :initials="$r->initials" :size="42" :ring="$i === 0" />
                            <div class="col" style="min-width:0;">
                                <span class="label" style="font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $r->name }}</span>
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.1em;">{{ strtoupper($r->city) }}</span>
                            </div>
                        </div>
                        <div class="flex rider-list-meta" style="align-items:center;gap:16px;flex-shrink:0;">
                            <x-cat-badge :cat="$r->category" />
                            <div class="col" style="align-items:flex-end;">
                                <span class="display tnum" style="font-size:24px;line-height:1;">{{ number_format($r->points) }}</span>
                                <span class="mono dim" style="font-size:9px;letter-spacing:0.16em;">POINTS</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── FEATURED RIDERS ── --}}
    <section class="section wrap">
        <div class="between" style="margin-bottom:34px;align-items:flex-end;gap:20px;flex-wrap:wrap;">
            <div>
                <div class="eyebrow-row" style="margin-bottom:10px;">
                    <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">03 /</span>
                    <span class="kicker">MEET THE PROS</span>
                </div>
                <h2 class="display" style="font-size:clamp(34px,5vw,64px);">Featured Riders</h2>
            </div>
            <a href="{{ route('riders') }}" class="btn btn-ghost">All Riders →</a>
        </div>
        <div class="riders-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
            @foreach($featRiders as $i => $r)
                <div class="rise" style="animation-delay:{{ $i * 70 }}ms;">
                    <a href="{{ route('riders.show', $r->slug) }}" class="panel" style="display:block;overflow:hidden;height:100%;"
                        onmouseover="this.querySelector('.rc-photo').style.transform='scale(1.04)'"
                        onmouseout="this.querySelector('.rc-photo').style.transform='none'">
                        <div style="position:relative;overflow:hidden;border-bottom:2px solid var(--ink);">
                            <div class="ph rc-photo scanlines" data-ph="Rider portrait" style="height:250px;transition:transform .35s;"></div>
                            <div style="position:absolute;top:10px;left:10px;"><x-cat-badge :cat="$r->category" /></div>
                            <span class="display" style="position:absolute;bottom:8px;right:10px;font-size:64px;color:var(--lime);line-height:0.8;opacity:0.92;">#{{ $i+1 }}</span>
                        </div>
                        <div style="padding:14px 16px 16px;">
                            <span class="mono" style="font-size:10px;color:var(--red);letter-spacing:0.16em;">{{ $r->nick }}</span>
                            <h3 class="display" style="font-size:26px;margin:2px 0 12px;">{{ $r->name }}</h3>
                            <div class="flex" style="gap:0;border-top:1px solid var(--line);">
                                @foreach([['WINS',$r->wins],['PODIUMS',$r->podiums],['BEST',$r->best_score]] as $j => [$lbl,$val])
                                    <div class="col" style="flex:1;padding:10px 0 0;border-right:{{ $j < 2 ? '1px solid var(--line)' : 'none' }};">
                                        <span class="display tnum" style="font-size:22px;">{{ $val }}</span>
                                        <span class="mono dim" style="font-size:8px;letter-spacing:0.14em;">{{ $lbl }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── FEATURES BLOCK ── --}}
    <section style="border-top:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap section">
            <div class="between" style="margin-bottom:34px;align-items:flex-end;gap:20px;flex-wrap:wrap;">
                <div>
                    <div class="eyebrow-row" style="margin-bottom:10px;">
                        <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">04 /</span>
                        <span class="kicker">TOURNAMENT TECH</span>
                    </div>
                    <h2 class="display" style="font-size:clamp(34px,5vw,64px);">Built For Competition</h2>
                </div>
            </div>
            <div class="feat-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
                @foreach([
                    ['Live Scoring','Real-time judge cards, execution / style / creativity breakdowns and an animated leaderboard.','live','LIVE','var(--red)'],
                    ['Bracket','Single-elimination brackets from quarter finals to the championship run.','bracket','QF→F','var(--lime)'],
                    ['Rider Profiles','Career stats, trophy cabinets, competition history and social links.','riders','PROS','var(--ink)'],
                ] as $i => [$t,$d,$r,$tag,$accent])
                    <div class="rise" style="animation-delay:{{ $i * 70 }}ms;">
                        <a href="{{ route($r) }}" class="panel halftone" style="display:block;padding:26px;height:100%;position:relative;overflow:hidden;transition:transform .15s,box-shadow .15s;"
                            onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='6px 6px 0 {{ $accent }}';"
                            onmouseout="this.style.transform='none';this.style.boxShadow='var(--paper-shadow)';">
                            <div class="between" style="margin-bottom:70px;">
                                <span class="badge" style="border-color:{{ $accent }};color:{{ $accent }};">{{ $tag }}</span>
                                <span style="font-size:24px;">→</span>
                            </div>
                            <h3 class="display" style="font-size:34px;margin-bottom:10px;">{{ $t }}</h3>
                            <p class="dim" style="font-size:14px;line-height:1.5;">{{ $d }}</p>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── CTA BANNER ── --}}
    <section style="border-top:2px solid var(--ink);background:var(--lime);color:#0a0a0b;position:relative;overflow:hidden;">
        <div style="position:absolute;inset:0;background-image:radial-gradient(circle,rgba(0,0,0,0.12) 1px,transparent 1px);background-size:16px 16px;pointer-events:none;"></div>
        <div class="wrap center col" style="position:relative;padding:70px 0;text-align:center;gap:10px;">
            <span class="mono" style="font-size:12px;letter-spacing:0.3em;">ONE WHEEL · ONE FAMILY</span>
            <h2 class="display" style="font-size:clamp(44px,8vw,110px);margin:14px 0 26px;">Drop In With Us</h2>
            <div class="flex center gap-m" style="flex-wrap:wrap;">
                <a href="{{ route('register') }}" class="btn" style="background:#0a0a0b;border-color:#0a0a0b;color:var(--lime);">Register a Rider →</a>
                <a href="{{ route('events') }}" class="btn" style="border-color:#0a0a0b;color:#0a0a0b;">Browse Events</a>
            </div>
        </div>
    </section>
</div>

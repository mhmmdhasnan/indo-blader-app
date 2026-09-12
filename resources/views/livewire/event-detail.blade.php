<div>
    {{-- Hero --}}
    <div style="border-bottom:2px solid var(--ink);position:relative;overflow:hidden;">
        @if($event->banner)
            <div style="position:absolute;inset:0;background:url('{{ Storage::url($event->banner) }}') center/cover no-repeat;"></div>
        @else
            <div class="ph no-label scanlines" style="position:absolute;inset:0;"></div>
        @endif
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,color-mix(in srgb,var(--bg) 40%,transparent),var(--bg));"></div>
        <div class="wrap" style="position:relative;padding-top:40px;padding-bottom:34px;">
            <a href="{{ route('events') }}" class="mono dim" style="font-size:11px;letter-spacing:0.14em;">← ALL EVENTS</a>
            <div style="margin-top:22px;">
                <div class="flex gap-s" style="margin-bottom:14px;flex-wrap:wrap;align-items:center;">
                    <x-status-badge :status="$event->status" />
                    <span class="badge badge-out">{{ $event->edition }}</span>
                    @foreach($event->categories as $cat)
                        <x-cat-badge :cat="$cat" />
                    @endforeach
                </div>
                <h1 class="display" style="font-size:clamp(44px,8vw,96px);margin:0 0 8px;">{{ $event->title }}</h1>
                <div class="flex gap-m" style="flex-wrap:wrap;align-items:center;">
                    <span class="dim">📍 {{ $event->venue }}, {{ $event->city }}</span>
                    <span class="mono dim" style="font-size:13px;letter-spacing:0.12em;">{{ $event->date_label }}</span>
                    @unless($event->prize_hidden)
                        <span class="display tnum text-glow-lime" style="font-size:22px;color:var(--lime);">{{ $event->prize_long }}</span>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    {{-- CTA strip --}}
    <div style="border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap between" style="padding-block:20px;gap:16px;flex-wrap:wrap;">
            <div class="flex gap-s" style="flex-wrap:wrap;align-items:center;">
                <div style="height:6px;width:160px;background:var(--panel-2);border:1px solid var(--line);">
                    <div style="height:100%;width:{{ $event->fill_pct }}%;background:{{ $event->fill_pct > 85 ? 'var(--red)' : 'var(--lime)' }};"></div>
                </div>
                <span class="mono tnum" style="font-size:13px;font-weight:700;">{{ $event->filled }}{{ $event->slots !== null ? '/'.$event->slots : '' }} spots filled</span>
            </div>
            <div class="flex gap-s">
                @auth
                    @if(auth()->user()->isRider())
                        <a href="{{ route('register') }}" class="btn btn-lime">Register Now →</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-lime">Login untuk Daftar →</a>
                @endauth
                <a href="{{ route('live') }}" class="btn btn-ghost btn-sm"><span class="live-dot" style="margin-right:6px;"></span>Watch Live</a>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="border-bottom:2px solid var(--ink);background:var(--bg);">
        <div class="wrap flex" style="gap:0;overflow-x:auto;">
            @foreach(['OVERVIEW','SCHEDULE','CATEGORIES','RULES','PRIZE','LOCATION'] as $t)
                <button wire:click="$set('tab','{{ $t }}')" class="label" style="
                    font-size:12px;padding:18px 20px;border-bottom:3px solid {{ $tab === $t ? 'var(--lime)' : 'transparent' }};
                    color:{{ $tab === $t ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;transition:color .15s;
                ">{{ $t }}</button>
            @endforeach
        </div>
    </div>

    <div class="wrap section" style="padding-top:40px;">

        @if($tab === 'OVERVIEW')
            <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:34px;" class="prof-grid">
                <div>
                    <p style="font-size:17px;line-height:1.6;margin-bottom:30px;">{{ $event->blurb }}</p>
                    <div class="panel" style="overflow:hidden;">
                        @foreach([['DATE',$event->date_label],['VENUE',$event->venue],['CITY',$event->city],['PRIZE',$event->prize_hidden ? 'TBA' : $event->prize_long],['SLOTS',($event->slots !== null ? $event->slots.' competitors' : 'Unlimited')],['FORMAT','Single Elimination']] as [$lbl,$val])
                            <div class="between" style="padding:14px 18px;border-bottom:1px solid var(--line);">
                                <span class="mono dim" style="font-size:11px;letter-spacing:0.12em;">{{ $lbl }}</span>
                                <span class="label" style="font-size:14px;">{{ $val }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col" style="gap:20px;">
                    <div class="panel halftone center col" style="padding:30px;gap:10px;text-align:center;">
                        <span class="kicker">PRIZE POOL</span>
                        @if($event->prize_hidden)
                            <span class="display" style="font-size:clamp(28px,5vw,44px);color:var(--ink-dim);">TO BE ANNOUNCED</span>
                        @else
                            <span class="display tnum text-glow-lime" style="font-size:clamp(44px,8vw,80px);color:var(--lime);">{{ $event->prize_formatted }}</span>
                        @endif
                        @auth
                            @if(auth()->user()->isRider())
                                <a href="{{ route('register') }}" class="btn btn-lime btn-sm" style="margin-top:10px;">Register →</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-ghost btn-sm" style="margin-top:10px;">Login untuk Daftar</a>
                        @endauth
                    </div>
                    @foreach($event->categories as $cat)
                        @php
                            $descs = ['STREET'=>'Rails, ledges, stairs and gaps.','PARK'=>'Bowls, transfers and flow lines.','VERT'=>'Big air on the mega ramp.','FLAT'=>'Footwork and balance combos.','MINIRAMP'=>'Quick transitions and lines on a compact ramp.'];
                        @endphp
                        <div class="panel" style="padding:18px;">
                            <x-cat-badge :cat="$cat" />
                            <p class="dim" style="font-size:13px;margin-top:10px;line-height:1.5;">{{ $descs[$cat] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($tab === 'SCHEDULE')
            <div class="col" style="gap:30px;">
                @foreach($schedule as $day)
                    <div>
                        <div class="between" style="margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--ink);">
                            <span class="display" style="font-size:26px;">{{ $day['day'] }}</span>
                            <span class="badge badge-out">{{ $day['title'] }}</span>
                        </div>
                        <div class="col" style="gap:0;">
                            @foreach($day['items'] as $item)
                                <div class="between" style="padding:14px 0;border-bottom:1px solid var(--line);">
                                    <div class="flex" style="gap:20px;align-items:center;">
                                        <span class="mono" style="font-size:13px;color:var(--lime);font-weight:700;min-width:50px;">{{ $item['t'] }}</span>
                                        <span class="label" style="font-size:15px;">{{ $item['name'] }}</span>
                                    </div>
                                    <x-cat-badge :cat="$item['tag']" :sm="true" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($tab === 'CATEGORIES')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                @foreach($event->categories as $cat)
                    @php
                        $info = ['STREET'=>'Rails, ledges, stairs and gaps. Real urban obstacles.','PARK'=>'Bowls, transfers and flow lines in a built skatepark.','VERT'=>'Big air on the mega ramp. Rotations and grabs.','FLAT'=>'Footwork and balance combos on flat ground.','MINIRAMP'=>'Quick transitions and technical lines on a compact ramp.'];
                    @endphp
                    <div class="panel halftone" style="padding:22px;">
                        <x-cat-badge :cat="$cat" />
                        <h3 class="display" style="font-size:30px;margin:14px 0 8px;">{{ ucfirst(strtolower($cat)) === 'Flat' ? 'Flatland' : ucfirst(strtolower($cat)) }}</h3>
                        <p class="dim" style="font-size:14px;line-height:1.5;">{{ $info[$cat] ?? '' }}</p>
                        <div class="flex" style="gap:16px;margin-top:16px;border-top:1px solid var(--line);padding-top:14px;">
                            @foreach([['2','RUNS'],['60s','PER RUN'],['4','JUDGES']] as [$v,$l])
                                <div class="col"><span class="display tnum" style="font-size:22px;">{{ $v }}</span><span class="mono dim" style="font-size:9px;">{{ $l }}</span></div>
                            @endforeach
                        </div>
                        @if(($divisionsByCategory[$cat] ?? collect())->isNotEmpty())
                            <div class="col" style="gap:8px;margin-top:16px;border-top:1px solid var(--line);padding-top:14px;">
                                <span class="mono dim" style="font-size:9px;letter-spacing:0.12em;">DIVISIONS</span>
                                @foreach($divisionsByCategory[$cat] as $div)
                                    <div class="between" style="align-items:center;gap:10px;">
                                        <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;">
                                            <span class="label" style="font-size:13px;">{{ $div->name }}</span>
                                            @if($div->level)
                                                <span class="mono" style="font-size:9px;padding:1px 5px;background:var(--bg-2);border:1px solid var(--line);">{{ $div->level }}</span>
                                            @endif
                                        </div>
                                        <span class="mono dim" style="font-size:10px;white-space:nowrap;">{{ $div->slots === null ? $div->filled.' terdaftar' : $div->filled.'/'.$div->slots }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($tab === 'RULES')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="prof-grid">
                @foreach($rules as $i => $rule)
                    <div class="flex" style="gap:16px;padding:18px 20px;border:2px solid var(--ink);border-radius:3px;align-items:flex-start;">
                        <span class="display" style="font-size:28px;color:var(--lime);line-height:0.9;flex-shrink:0;">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
                        <p style="font-size:15px;line-height:1.5;">{{ $rule }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if($tab === 'PRIZE')
            @if($event->prize_hidden)
                <div class="panel center col" style="padding:40px;gap:8px;text-align:center;max-width:620px;">
                    <span class="kicker">PRIZE POOL</span>
                    <span class="display" style="font-size:clamp(28px,5vw,40px);color:var(--ink-dim);">TO BE ANNOUNCED</span>
                    <p class="dim" style="font-size:13px;">Nominal hadiah akan diumumkan lebih lanjut oleh penyelenggara.</p>
                </div>
            @else
                <div style="max-width:620px;">
                    <div class="panel" style="overflow:hidden;">
                        @foreach($prizeSplit as [$place,$frac])
                            <div class="between" style="padding:18px 20px;border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};background:{{ $place === 1 ? 'color-mix(in srgb,var(--lime) 10%,transparent)' : 'transparent' }};">
                                <div class="flex" style="align-items:center;gap:16px;">
                                    <span class="display tnum" style="font-size:30px;color:{{ $place === 1 ? 'var(--lime)' : 'var(--ink-faint)' }};width:42px;">{{ $place === 1 ? '★' : str_pad($place,2,'0',STR_PAD_LEFT) }}</span>
                                    <span class="label" style="font-size:16px;">{{ $place === 1 ? 'CHAMPION' : $place.($place===2?'ND':($place===3?'RD':'TH')).' PLACE' }}</span>
                                </div>
                                <span class="display tnum" style="font-size:26px;color:{{ $place === 1 ? 'var(--lime)' : 'var(--ink)' }};">Rp {{ number_format(round($event->prize * $frac), 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mono dim" style="font-size:11px;margin-top:14px;letter-spacing:0.1em;">TOTAL POOL · {{ $event->prize_long }} · SPLIT PER CATEGORY</p>
                </div>
            @endif
        @endif

        @if($tab === 'LOCATION')
            <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:stretch;" class="prof-grid">
                @if($event->latitude && $event->longitude)
                    <div
                        x-data="leafletView({{ $event->latitude }}, {{ $event->longitude }}, @js($event->venue))"
                        x-init="mount()"
                        style="min-height:360px;border:2px solid var(--ink);border-radius:3px;"></div>
                @else
                    <div class="ph halftone" data-ph="Venue map" style="min-height:360px;border:2px solid var(--ink);border-radius:3px;position:relative;">
                        <div style="position:absolute;top:46%;left:52%;transform:translate(-50%,-50%);">
                            <div class="center" style="width:44px;height:44px;background:var(--red);border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:2px solid var(--ink);">
                                <span style="transform:rotate(45deg);color:#fff;font-size:18px;">★</span>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="panel" style="padding:22px;">
                    <span class="kicker">VENUE</span>
                    <h3 class="display" style="font-size:30px;margin:8px 0 12px;">{{ $event->venue }}</h3>
                    <p class="dim" style="font-size:14px;line-height:1.6;margin-bottom:18px;">{{ $event->city }}, Indonesia.</p>
                    <div class="col" style="gap:12px;">
                        @foreach([['DATE',$event->date_label],['STATUS',$event->status_label],['SLOTS',($event->slots !== null ? $event->slots.' competitors' : 'Unlimited')],['TYPE',$event->type === 'LIVE_SCORE' ? 'Live Score (Offline)' : 'KO (Online — Upload Video)']] as [$l,$v])
                            <div class="between" style="border-bottom:1px solid var(--line);padding-bottom:10px;">
                                <span class="mono dim" style="font-size:11px;letter-spacing:0.1em;">{{ $l }}</span>
                                <span class="label" style="font-size:14px;">{{ $v }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

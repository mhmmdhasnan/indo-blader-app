<div>
    {{-- Hero --}}
    <div style="border-bottom:2px solid var(--ink);position:relative;overflow:hidden;">
        <div class="ph no-label scanlines" style="position:absolute;inset:0;"></div>
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,color-mix(in srgb,var(--bg) 40%,transparent),var(--bg));"></div>
        <div class="wrap" style="position:relative;padding:40px 0 34px;">
            <a href="{{ route('riders') }}" class="mono dim" style="font-size:11px;letter-spacing:0.14em;">← ALL RIDERS</a>
            <div class="flex" style="gap:28px;margin-top:22px;align-items:flex-end;flex-wrap:wrap;">
                <div class="ph" data-ph="Portrait" style="width:210px;height:250px;flex-shrink:0;border:2px solid var(--ink);"></div>
                <div style="flex:1;min-width:260px;">
                    <div class="flex gap-s" style="margin-bottom:12px;flex-wrap:wrap;">
                        <x-cat-badge :cat="$rider->category" />
                        <span class="badge badge-lime">#{{ $rank }} NATIONAL</span>
                        <span class="badge badge-out">{{ $rider->sponsor }}</span>
                    </div>
                    <span class="mono" style="font-size:13px;color:var(--red);letter-spacing:0.18em;">"{{ $rider->nick }}"</span>
                    <h1 class="display" style="font-size:clamp(44px,8vw,96px);margin:2px 0 8px;">{{ $rider->name }}</h1>
                    <div class="flex gap-m" style="flex-wrap:wrap;align-items:center;">
                        <span class="dim">📍 {{ $rider->city }}, Indonesia</span>
                        <span class="dim">· Age {{ $rider->age }}</span>
                        <span class="dim">· {{ $rider->stance }} stance</span>
                    </div>
                </div>
                <div class="flex gap-s" style="flex-wrap:wrap;">
                    @if($rider->ig)<a href="#" class="badge badge-out" style="padding:8px 12px;">IG {{ $rider->ig }}</a>@endif
                    @if($rider->yt)<a href="#" class="badge badge-out" style="padding:8px 12px;">YT {{ $rider->yt }}</a>@endif
                    @if($rider->tt)<a href="#" class="badge badge-out" style="padding:8px 12px;">TT {{ $rider->tt }}</a>@endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stat strip --}}
    <div style="border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap stat-strip">
            @foreach([['#'.$rank,'NATIONAL RANK'],[$rider->wins,'WINS'],[$rider->podiums,'PODIUMS'],[$rider->comps,'COMPS'],[$rider->best_score,'BEST SCORE']] as $i => [$val,$lbl])
                <div class="col" style="padding:22px 16px;border-right:{{ $i < 4 ? '1px solid var(--line)' : 'none' }};align-items:flex-start;">
                    <span class="display tnum" style="font-size:clamp(28px,4vw,52px);color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};line-height:0.9;">{{ $val }}</span>
                    <span class="mono dim" style="font-size:9px;letter-spacing:0.14em;margin-top:6px;">{{ $lbl }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="wrap section" style="padding-top:50px;">
        <div class="prof-grid" style="display:grid;grid-template-columns:1.4fr 1fr;gap:34px;">
            {{-- History --}}
            <div>
                <div class="between" style="margin-bottom:34px;align-items:flex-end;gap:20px;flex-wrap:wrap;">
                    <div>
                        <div class="eyebrow-row" style="margin-bottom:10px;"><span class="kicker">CAREER</span></div>
                        <h2 class="display" style="font-size:clamp(34px,5vw,64px);">Competition History</h2>
                    </div>
                </div>
                <div class="panel" style="overflow:hidden;">
                    @foreach($rider->achievements ?? [] as $i => $ach)
                        @php
                            $place = str_contains($ach,'1st') ? 1 : (str_contains($ach,'2nd') ? 2 : (str_contains($ach,'3rd') ? 3 : 0));
                        @endphp
                        <div class="between" style="padding:16px 18px;border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};">
                            <div class="flex" style="align-items:center;gap:14px;">
                                <span class="display" style="font-size:22px;width:30px;color:{{ $place === 1 ? 'var(--lime)' : 'var(--ink-faint)' }};">{{ $place ? '0'.$place : '—' }}</span>
                                <span class="label" style="font-size:15px;">{{ explode(' — ',$ach)[0] }}</span>
                            </div>
                            @if($place > 0)
                                <span class="badge {{ $place === 1 ? 'badge-lime' : 'badge-out' }}">{{ $place === 1 ? '★ WINNER' : $place.($place===2?'ND':'RD') }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div style="margin-top:28px;">
                    <div class="eyebrow-row" style="margin-bottom:10px;"><span class="kicker">ABOUT</span></div>
                    <h2 class="display" style="font-size:clamp(34px,5vw,64px);margin-bottom:16px;">Bio</h2>
                    <p style="font-size:16px;line-height:1.6;max-width:560px;">{{ $rider->bio }}</p>
                </div>
            </div>

            {{-- Trophies + stats --}}
            <div class="col" style="gap:28px;">
                <div>
                    <div class="eyebrow-row" style="margin-bottom:10px;"><span class="kicker">SILVERWARE</span></div>
                    <h2 class="display" style="font-size:clamp(34px,5vw,64px);margin-bottom:16px;">Trophies</h2>
                    @php
                        $achs = $rider->achievements ?? [];
                        $golds = collect($achs)->filter(fn($a) => str_contains($a,'1st'))->count();
                        $silvers = collect($achs)->filter(fn($a) => str_contains($a,'2nd'))->count();
                        $bronzes = collect($achs)->filter(fn($a) => str_contains($a,'3rd'))->count();
                    @endphp
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        @foreach([['🥇',$golds,'GOLD','var(--lime)'],['🥈',$silvers,'SILVER','var(--ink-dim)'],['🥉',$bronzes,'BRONZE','var(--red)'],['◆',$rider->podiums,'PODIUMS','var(--ink)']] as [$ic,$n,$lbl,$c])
                            <div class="panel center col" style="padding:20px;gap:6px;text-align:center;">
                                <span style="font-size:28px;">{{ $ic }}</span>
                                <span class="display tnum" style="font-size:44px;color:{{ $c }};line-height:1;">{{ $n }}</span>
                                <span class="mono dim" style="font-size:9px;letter-spacing:0.14em;">{{ $lbl }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="eyebrow-row" style="margin-bottom:10px;"><span class="kicker">PERFORMANCE</span></div>
                    <h2 class="display" style="font-size:clamp(34px,5vw,64px);margin-bottom:16px;">Stats</h2>
                    <div class="panel" style="overflow:hidden;">
                        @foreach([
                            ['Category', $rider->category_label],
                            ['Stance', $rider->stance],
                            ['Sponsor', $rider->sponsor],
                            ['Home City', $rider->city],
                            ['Competitions', $rider->comps],
                            ['Win Rate', round($rider->comps > 0 ? $rider->wins/$rider->comps*100 : 0).'%'],
                            ['Best Score', $rider->best_score.'/100'],
                        ] as [$lbl,$val])
                            <div class="between" style="padding:13px 18px;border-bottom:1px solid var(--line);">
                                <span class="mono dim" style="font-size:11px;letter-spacing:0.1em;">{{ strtoupper($lbl) }}</span>
                                <span class="label" style="font-size:14px;">{{ $val }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-m" style="margin-top:40px;flex-wrap:wrap;">
            <a href="{{ route('register') }}" class="btn btn-lime">Register for Next Event →</a>
            <a href="{{ route('riders') }}" class="btn btn-ghost">← All Riders</a>
        </div>
    </div>
</div>

<div>
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap" style="padding:48px 0 40px;">
            <div class="eyebrow-row" style="margin-bottom:12px;">
                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">RESULTS /</span>
                <span class="kicker">NATIONALS STREET — SINGLE ELIMINATION</span>
            </div>
            <h1 class="display" style="font-size:clamp(40px,7vw,84px);">Bracket</h1>
        </div>
    </div>

    <div class="wrap section" style="padding-top:40px;overflow-x:auto;">
        <div class="flex" style="align-items:flex-start;gap:24px;min-width:800px;">

            {{-- QF --}}
            <div style="flex:1;">
                <div class="kicker" style="margin-bottom:14px;text-align:center;">QUARTER FINALS</div>
                <div class="col" style="gap:24px;">
                    @foreach($qf as $m)
                        <x-bracket-match :match="$m" :riders="$riders" />
                    @endforeach
                </div>
            </div>

            <div style="display:flex;flex-direction:column;justify-content:space-around;align-self:stretch;padding:30px 0;">
                @for($i = 0; $i < 2; $i++)<div style="flex:1;border-right:2px solid var(--line);"></div>@endfor
            </div>

            {{-- SF --}}
            <div style="flex:1;">
                <div class="kicker" style="margin-bottom:14px;text-align:center;">SEMI FINALS</div>
                <div class="col" style="gap:24px;justify-content:space-around;height:calc(100% - 30px);">
                    @foreach($sf as $m)
                        <x-bracket-match :match="$m" :riders="$riders" />
                    @endforeach
                </div>
            </div>

            <div style="display:flex;flex-direction:column;justify-content:center;align-self:stretch;padding:30px 0;">
                <div style="flex:1;border-right:2px solid var(--line);"></div>
            </div>

            {{-- Final --}}
            <div style="flex:1;">
                <div class="kicker" style="margin-bottom:14px;text-align:center;">FINAL</div>
                <div style="margin-top:auto;">
                    @foreach($f as $m)
                        <x-bracket-match :match="$m" :riders="$riders" :isFinal="true" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="wrap" style="padding-bottom:64px;">
        <div class="flex gap-m" style="flex-wrap:wrap;">
            <a href="{{ route('live') }}" class="btn btn-lime"><span class="live-dot" style="margin-right:6px;"></span>Live Scoring</a>
            <a href="{{ route('events.show', 'nationals') }}" class="btn btn-ghost">Event Details →</a>
        </div>
    </div>
</div>

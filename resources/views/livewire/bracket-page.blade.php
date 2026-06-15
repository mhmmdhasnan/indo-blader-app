<div style="min-height:100vh;background:var(--bg);overflow-x:auto;">

    {{-- ── HEADER ── --}}
    <div class="halftone" style="border-bottom:2px solid var(--ink);padding:36px 48px 28px;">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <span class="mono" style="font-size:10px;letter-spacing:0.2em;color:var(--lime);display:block;margin-bottom:6px;">
                    {{ $event ? strtoupper($event->title) : 'TOURNAMENT' }} @if($event) · {{ $event->date_label }} @endif
                </span>
                <h1 class="display" style="font-size:clamp(52px,8vw,96px);line-height:0.85;letter-spacing:-0.02em;">PLAYOFFS</h1>
                @if($bracket)
                    <span class="mono dim" style="font-size:11px;margin-top:10px;display:block;">
                        {{ str_replace('_', ' ', $bracket->type) }}
                        @if($bracket->status === 'COMPLETED') · <span style="color:var(--lime);">COMPLETED</span> @endif
                    </span>
                @endif
            </div>
            <div class="flex gap-s">
                <a href="{{ route('live') }}" class="btn btn-ghost btn-sm"><span class="live-dot" style="margin-right:6px;"></span>Live Scoring</a>
                @if($event)
                    <a href="{{ route('events.show', $event->slug) }}" class="btn btn-ghost btn-sm">Event →</a>
                @endif
            </div>
        </div>
    </div>

    @if($bracket && $matchesByRound->count())
    @php
        $roundLabels = [
            'PRELIM' => 'PRELIMINARY',
            'QF'    => 'QUARTER FINALS',
            'SF'    => 'SEMI FINALS',
            'F'     => 'FINAL',
            'UB_R1' => 'UPPER R1',
            'UB_R2' => 'UPPER R2',
            'UB_SF' => 'UPPER SEMI',
            'UB_F'  => 'UPPER FINAL',
            'LB_R1' => 'LOWER R1',
            'LB_R2' => 'LOWER R2',
            'LB_R3' => 'LOWER R3',
            'LB_R4' => 'LOWER R4',
            'LB_SF' => 'LOWER SEMI',
            'LB_F'  => 'LOWER FINAL',
            'GF'    => 'GRAND FINAL',
        ];

        $isDoubleElim  = $bracket->type === 'DOUBLE_ELIMINATION';
        $orderedRounds = collect($roundOrder)->filter(fn($r) => $matchesByRound->has($r))->values();

        if ($isDoubleElim) {
            $upperRounds = $orderedRounds->filter(fn($r) => str_starts_with($r, 'UB_'))->values();
            $lowerRounds = $orderedRounds->filter(fn($r) => str_starts_with($r, 'LB_'))->values();
            $gfRounds    = $orderedRounds->filter(fn($r) => $r === 'GF')->values();
        } else {
            $upperRounds = $orderedRounds;
            $lowerRounds = collect();
            $gfRounds    = collect();
        }

        // Base slot height — driven by first (largest) round
        $firstRound      = $orderedRounds->first();
        $firstRoundCount = $firstRound ? $matchesByRound[$firstRound]->count() : 1;
        $baseSlotH       = (int) max(80, min(130, 800 / $firstRoundCount));

        // For lower bracket, base on LB_R1 count
        $lbFirstRound     = $lowerRounds->first();
        $lbFirstCount     = $lbFirstRound ? $matchesByRound[$lbFirstRound]->count() : 1;
        $lbBaseSlotH      = (int) max(80, min(130, 400 / $lbFirstCount));
    @endphp

    <div style="padding:32px 48px 64px;min-width:900px;">

        @if($isDoubleElim)
        {{-- ════ DOUBLE ELIMINATION LAYOUT ════ --}}

        {{-- Upper Bracket + GF column spans both --}}
        <div style="display:flex;align-items:stretch;gap:0;">

            {{-- Left: Upper + Lower stacked --}}
            <div style="flex:1;min-width:0;">

                {{-- UPPER BRACKET --}}
                <div style="display:flex;align-items:stretch;margin-bottom:40px;">
                    {{-- Section label --}}
                    <div style="writing-mode:vertical-rl;transform:rotate(180deg);font-size:9px;letter-spacing:0.22em;color:var(--lime);padding:0 10px 0 0;border-left:3px solid var(--lime);margin-right:20px;font-weight:700;white-space:nowrap;display:flex;align-items:center;justify-content:center;">UPPER BRACKET</div>

                    {{-- Round columns --}}
                    <div style="display:flex;align-items:flex-start;gap:0;">
                        @foreach($upperRounds as $ri => $round)
                            @php
                                $matches = $matchesByRound[$round];
                                $slotH   = $baseSlotH * (int) pow(2, $ri);
                            @endphp

                            {{-- Connector from previous round --}}
                            @if($ri > 0)
                                @php $pairCount = $matches->count(); @endphp
                                <div style="display:flex;flex-direction:column;width:36px;flex-shrink:0;align-self:stretch;">
                                    @for($p = 0; $p < $pairCount; $p++)
                                        <div style="height:{{ $slotH }}px;display:flex;flex-direction:column;">
                                            <div style="flex:1;border-right:2px solid var(--line);border-bottom:2px solid var(--line);"></div>
                                            <div style="flex:1;border-right:2px solid var(--line);border-top:2px solid var(--line);"></div>
                                        </div>
                                    @endfor
                                </div>
                            @endif

                            {{-- Round column --}}
                            <div style="display:flex;flex-direction:column;flex-shrink:0;width:200px;">
                                <div class="mono" style="font-size:9px;letter-spacing:0.16em;color:var(--lime);padding-bottom:10px;text-align:center;">{{ $roundLabels[$round] ?? $round }}</div>
                                @foreach($matches as $match)
                                    @php
                                        $nameA   = $match->riderA?->name ?? 'TBD';
                                        $nameB   = $match->riderB?->name ?? 'TBD';
                                        $winnerA = $match->winner_registration_id && $match->winner_registration_id === $match->rider_a_registration_id;
                                        $winnerB = $match->winner_registration_id && $match->winner_registration_id === $match->rider_b_registration_id;
                                        $matchNum = str_pad($match->match_number, 2, '0', STR_PAD_LEFT);
                                    @endphp
                                    <div style="height:{{ $slotH }}px;display:flex;flex-direction:column;justify-content:center;padding:0 4px;">
                                        <div class="mono" style="font-size:8px;letter-spacing:0.14em;color:var(--ink-dim);margin-bottom:4px;">MATCH {{ $matchNum }}@if($match->trick) · {{ strtoupper($match->trick->name) }}@endif</div>
                                        <div style="border:2px solid var(--line);overflow:hidden;border-radius:2px;background:var(--surface);">
                                            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-bottom:1px solid var(--line);background:{{ $winnerA ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
                                                <span style="font-size:13px;font-weight:{{ $winnerA ? '700' : '400' }};color:{{ $winnerA ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">{{ $nameA }}</span>
                                                <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $winnerA ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_a ?? '—' }}</span>
                                            </div>
                                            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;background:{{ $winnerB ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
                                                <span style="font-size:13px;font-weight:{{ $winnerB ? '700' : '400' }};color:{{ $winnerB ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">{{ $nameB }}</span>
                                                <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $winnerB ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_b ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- LOWER BRACKET --}}
                @if($lowerRounds->count())
                <div style="display:flex;align-items:stretch;">
                    <div style="writing-mode:vertical-rl;transform:rotate(180deg);font-size:9px;letter-spacing:0.22em;color:var(--ink-dim);padding:0 10px 0 0;border-left:3px solid var(--ink-dim);margin-right:20px;font-weight:700;white-space:nowrap;display:flex;align-items:center;justify-content:center;">LOWER BRACKET</div>

                    <div style="display:flex;align-items:flex-start;gap:0;">
                        @foreach($lowerRounds as $ri => $round)
                            @php
                                $matches = $matchesByRound[$round];
                                $slotH   = $lbBaseSlotH * (int) pow(2, $ri);
                            @endphp

                            @if($ri > 0)
                                @php $pairCount = $matches->count(); @endphp
                                <div style="display:flex;flex-direction:column;width:36px;flex-shrink:0;align-self:stretch;">
                                    @for($p = 0; $p < $pairCount; $p++)
                                        <div style="height:{{ $slotH }}px;display:flex;flex-direction:column;">
                                            <div style="flex:1;border-right:2px solid var(--line);border-bottom:2px solid var(--line);"></div>
                                            <div style="flex:1;border-right:2px solid var(--line);border-top:2px solid var(--line);"></div>
                                        </div>
                                    @endfor
                                </div>
                            @endif

                            <div style="display:flex;flex-direction:column;flex-shrink:0;width:200px;">
                                <div class="mono" style="font-size:9px;letter-spacing:0.16em;color:var(--ink-dim);padding-bottom:10px;text-align:center;">{{ $roundLabels[$round] ?? $round }}</div>
                                @foreach($matches as $match)
                                    @php
                                        $nameA   = $match->riderA?->name ?? 'TBD';
                                        $nameB   = $match->riderB?->name ?? 'TBD';
                                        $winnerA = $match->winner_registration_id && $match->winner_registration_id === $match->rider_a_registration_id;
                                        $winnerB = $match->winner_registration_id && $match->winner_registration_id === $match->rider_b_registration_id;
                                        $matchNum = str_pad($match->match_number, 2, '0', STR_PAD_LEFT);
                                    @endphp
                                    <div style="height:{{ $slotH }}px;display:flex;flex-direction:column;justify-content:center;padding:0 4px;">
                                        <div class="mono" style="font-size:8px;letter-spacing:0.14em;color:var(--ink-dim);margin-bottom:4px;">MATCH {{ $matchNum }}@if($match->trick) · {{ strtoupper($match->trick->name) }}@endif</div>
                                        <div style="border:2px solid var(--line);overflow:hidden;border-radius:2px;background:var(--surface);">
                                            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-bottom:1px solid var(--line);background:{{ $winnerA ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
                                                <span style="font-size:13px;font-weight:{{ $winnerA ? '700' : '400' }};color:{{ $winnerA ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">{{ $nameA }}</span>
                                                <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $winnerA ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_a ?? '—' }}</span>
                                            </div>
                                            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 12px;background:{{ $winnerB ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
                                                <span style="font-size:13px;font-weight:{{ $winnerB ? '700' : '400' }};color:{{ $winnerB ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;">{{ $nameB }}</span>
                                                <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $winnerB ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_b ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>{{-- end left column --}}

            {{-- Grand Final on the right, spanning both --}}
            @if($gfRounds->count())
                @php $gfMatch = $matchesByRound['GF']->first(); @endphp
                <div style="display:flex;align-items:center;padding-left:36px;flex-shrink:0;">
                    <div style="width:36px;align-self:stretch;border-top:2px solid var(--lime);border-right:2px solid var(--lime);border-bottom:2px solid var(--lime);border-radius:0 4px 4px 0;margin-right:0;"></div>
                    <div style="width:200px;padding-left:4px;">
                        <div class="mono" style="font-size:9px;letter-spacing:0.16em;color:var(--lime);padding-bottom:10px;text-align:center;">GRAND FINAL</div>
                        @if($gfMatch)
                            @php
                                $nameA   = $gfMatch->riderA?->name ?? 'TBD';
                                $nameB   = $gfMatch->riderB?->name ?? 'TBD';
                                $winnerA = $gfMatch->winner_registration_id && $gfMatch->winner_registration_id === $gfMatch->rider_a_registration_id;
                                $winnerB = $gfMatch->winner_registration_id && $gfMatch->winner_registration_id === $gfMatch->rider_b_registration_id;
                            @endphp
                            <div style="border:3px solid var(--lime);overflow:hidden;border-radius:2px;background:var(--surface);">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid var(--line);background:{{ $winnerA ? 'color-mix(in srgb,var(--lime) 18%,transparent)' : 'transparent' }};">
                                    <span style="font-size:14px;font-weight:{{ $winnerA ? '700' : '400' }};color:{{ $winnerA ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">{{ $nameA }}</span>
                                    <span class="mono tnum" style="font-size:14px;font-weight:700;color:{{ $winnerA ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $gfMatch->score_a ?? '—' }}</span>
                                </div>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:{{ $winnerB ? 'color-mix(in srgb,var(--lime) 18%,transparent)' : 'transparent' }};">
                                    <span style="font-size:14px;font-weight:{{ $winnerB ? '700' : '400' }};color:{{ $winnerB ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">{{ $nameB }}</span>
                                    <span class="mono tnum" style="font-size:14px;font-weight:700;color:{{ $winnerB ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $gfMatch->score_b ?? '—' }}</span>
                                </div>
                            </div>
                            @if($gfMatch->winner_registration_id)
                                <div style="margin-top:10px;text-align:center;">
                                    <span class="badge badge-lime" style="font-size:10px;">🏆 CHAMPION: {{ $gfMatch->winner?->name }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

        </div>{{-- end double elim wrapper --}}

        @else
        {{-- ════ SINGLE ELIMINATION LAYOUT ════ --}}
        <div style="display:flex;align-items:flex-start;gap:0;">
            @foreach($upperRounds as $ri => $round)
                @php
                    $matches = $matchesByRound[$round];
                    $slotH   = $baseSlotH * (int) pow(2, $ri);
                    $isFinal = $loop->last;
                @endphp

                {{-- Connector --}}
                @if($ri > 0)
                    @php $pairCount = $matches->count(); @endphp
                    <div style="display:flex;flex-direction:column;width:36px;flex-shrink:0;align-self:stretch;">
                        @for($p = 0; $p < $pairCount; $p++)
                            <div style="height:{{ $slotH }}px;display:flex;flex-direction:column;">
                                <div style="flex:1;border-right:2px solid {{ $isFinal ? 'var(--lime)' : 'var(--line)' }};border-bottom:2px solid {{ $isFinal ? 'var(--lime)' : 'var(--line)' }};"></div>
                                <div style="flex:1;border-right:2px solid {{ $isFinal ? 'var(--lime)' : 'var(--line)' }};border-top:2px solid {{ $isFinal ? 'var(--lime)' : 'var(--line)' }};"></div>
                            </div>
                        @endfor
                    </div>
                @endif

                <div style="display:flex;flex-direction:column;flex-shrink:0;width:{{ $isFinal ? 220 : 200 }}px;">
                    <div class="mono" style="font-size:9px;letter-spacing:0.16em;color:{{ $isFinal ? 'var(--lime)' : 'var(--ink-dim)' }};padding-bottom:10px;text-align:center;">{{ $roundLabels[$round] ?? $round }}</div>
                    @foreach($matches as $match)
                        @php
                            $nameA   = $match->riderA?->name ?? 'TBD';
                            $nameB   = $match->riderB?->name ?? 'TBD';
                            $winnerA = $match->winner_registration_id && $match->winner_registration_id === $match->rider_a_registration_id;
                            $winnerB = $match->winner_registration_id && $match->winner_registration_id === $match->rider_b_registration_id;
                            $matchNum = str_pad($match->match_number, 2, '0', STR_PAD_LEFT);
                        @endphp
                        <div style="height:{{ $slotH }}px;display:flex;flex-direction:column;justify-content:center;padding:0 4px;">
                            <div class="mono" style="font-size:8px;letter-spacing:0.14em;color:{{ $isFinal ? 'var(--lime)' : 'var(--ink-dim)' }};margin-bottom:4px;">MATCH {{ $matchNum }}@if($match->trick) · {{ strtoupper($match->trick->name) }}@endif</div>
                            <div style="border:{{ $isFinal ? '3px solid var(--lime)' : '2px solid var(--line)' }};overflow:hidden;border-radius:2px;background:var(--surface);">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:{{ $isFinal ? '11px 14px' : '9px 12px' }};border-bottom:1px solid var(--line);background:{{ $winnerA ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
                                    <span style="font-size:{{ $isFinal ? 14 : 13 }}px;font-weight:{{ $winnerA ? '700' : '400' }};color:{{ $winnerA ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:{{ $isFinal ? 150 : 130 }}px;">{{ $nameA }}</span>
                                    <span class="mono tnum" style="font-size:{{ $isFinal ? 14 : 13 }}px;font-weight:700;color:{{ $winnerA ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_a ?? '—' }}</span>
                                </div>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:{{ $isFinal ? '11px 14px' : '9px 12px' }};background:{{ $winnerB ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
                                    <span style="font-size:{{ $isFinal ? 14 : 13 }}px;font-weight:{{ $winnerB ? '700' : '400' }};color:{{ $winnerB ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:{{ $isFinal ? 150 : 130 }}px;">{{ $nameB }}</span>
                                    <span class="mono tnum" style="font-size:{{ $isFinal ? 14 : 13 }}px;font-weight:700;color:{{ $winnerB ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_b ?? '—' }}</span>
                                </div>
                            </div>
                            @if($isFinal && $match->winner_registration_id)
                                <div style="margin-top:8px;">
                                    <span class="badge badge-lime" style="font-size:10px;">🏆 CHAMPION: {{ $match->winner?->name }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        @endif

    </div>{{-- end padding wrapper --}}

    @else
    {{-- Empty state --}}
    <div class="center col" style="padding:100px 20px;gap:20px;text-align:center;">
        <span style="font-size:56px;">⊟</span>
        <h2 class="display" style="font-size:40px;">Bracket Not Available</h2>
        <p class="dim" style="max-width:360px;">
            @if(!$event) No events found.
            @elseif(!$bracket) Bracket for <strong>{{ $event->title }}</strong> hasn't been generated yet.
            @endif
        </p>
        @if($event)
            <a href="{{ route('events.show', $event->slug) }}" class="btn btn-ghost">← Back to Event</a>
        @endif
    </div>
    @endif

</div>

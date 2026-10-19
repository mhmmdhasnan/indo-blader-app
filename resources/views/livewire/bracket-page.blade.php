<div style="min-height:100vh;background:var(--bg);overflow-x:auto;">

    {{-- ── HEADER ── --}}
    <div class="halftone" style="border-bottom:2px solid var(--ink);padding:32px 48px 24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                {{-- Event + date eyebrow --}}
                <span class="mono" style="font-size:10px;letter-spacing:0.2em;color:var(--lime);display:block;margin-bottom:8px;">
                    {{ $event ? strtoupper($event->title) : 'TOURNAMENT' }}@if($event) · {{ $event->date_label }}@endif
                </span>
                {{-- Division name as main title --}}
                @if($bracket?->division)
                    <h1 class="display" style="font-size:clamp(40px,6vw,80px);line-height:0.9;letter-spacing:-0.02em;">
                        {{ strtoupper($bracket->division->name) }}
                    </h1>
                @elseif($liveDivision)
                    <h1 class="display" style="font-size:clamp(40px,6vw,80px);line-height:0.9;letter-spacing:-0.02em;">
                        {{ strtoupper($liveDivision->name) }}
                    </h1>
                @else
                    <h1 class="display" style="font-size:clamp(40px,6vw,80px);line-height:0.9;letter-spacing:-0.02em;">BRACKET</h1>
                @endif
                {{-- Type + status --}}
                @if($bracket)
                    <span class="mono dim" style="font-size:11px;margin-top:8px;display:block;">
                        {{ str_replace('_', ' ', $bracket->type) }}
                        @if($bracket->status === 'COMPLETED') · <span style="color:var(--lime);">COMPLETED</span>@endif
                    </span>
                @elseif($liveDivision)
                    <span class="mono dim" style="font-size:11px;margin-top:8px;display:block;">
                        LIVE SCORE · HASIL FINAL
                    </span>
                @endif
            </div>
            {{-- Utility controls top-right --}}
            <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;padding-top:4px;">
                @if($events->count() > 1)
                    <select wire:model.live="selectedSlug" class="mono" style="background:var(--bg);color:var(--ink);border:1.5px solid var(--line);border-radius:6px;padding:6px 12px;font-size:12px;letter-spacing:0.05em;cursor:pointer;outline:none;">
                        @foreach($events as $ev)
                            <option value="{{ $ev->slug }}">{{ strtoupper($ev->title) }}</option>
                        @endforeach
                    </select>
                @endif
                <a href="{{ route('live') }}" class="btn btn-ghost btn-sm"><span class="live-dot" style="margin-right:6px;"></span>Live</a>
                @if($event)
                    <a href="{{ route('events.show', $event->slug) }}" class="btn btn-ghost btn-sm">Event →</a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── DIVISION TABS ── --}}
    @if($divisions->count() > 1)
    <style>
        .rs-div-select { display:none; }
        @media (max-width:760px) {
            .rs-div-tabs { display:none !important; }
            .rs-div-select { display:block; padding:12px 16px; border-bottom:2px solid var(--ink); background:var(--bg-2); }
            .rs-div-select select { width:100%; background:var(--bg);color:var(--ink);border:1.5px solid var(--line-strong);border-radius:6px;padding:10px 12px;font-size:13px;font-weight:700;letter-spacing:.08em;outline:none; }
        }
    </style>
    <div class="rs-div-select">
        <select wire:model.live="selectedDivisionId" class="mono" aria-label="Divisi">
            @foreach($divisions as $div)
                <option value="{{ $div->id }}">{{ strtoupper($div->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="rs-div-tabs" style="border-bottom:2px solid var(--ink);background:var(--bg-2);padding:0 48px;display:flex;gap:0;overflow-x:auto;">
        @foreach($divisions as $div)
            @php $active = $selectedDivisionId === $div->id; @endphp
            <button wire:click="$set('selectedDivisionId', {{ $div->id }})"
                class="mono"
                style="padding:12px 20px;font-size:11px;font-weight:700;letter-spacing:0.12em;white-space:nowrap;cursor:pointer;border:none;background:transparent;
                       border-bottom:3px solid {{ $active ? 'var(--lime)' : 'transparent' }};
                       color:{{ $active ? 'var(--ink)' : 'var(--ink-dim)' }};
                       margin-bottom:-2px;">
                {{ strtoupper($div->name) }}
            </button>
        @endforeach
    </div>
    @endif

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
                                    @include('livewire.partials.bracket-match', ['slotH' => $slotH, 'isFinal' => false])
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
                                    @include('livewire.partials.bracket-match', ['slotH' => $slotH, 'isFinal' => false])
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
                            @include('livewire.partials.bracket-match', ['match' => $gfMatch, 'slotH' => 120, 'isFinal' => true])
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
                        @include('livewire.partials.bracket-match', ['slotH' => $slotH, 'isFinal' => $isFinal])
                    @endforeach
                </div>
            @endforeach
        </div>
        @endif

    </div>{{-- end padding wrapper --}}

    @elseif($liveDivision)
    {{-- Live-score result: podium + klasemen final. Nominal Best Trick tidak ditampilkan ke publik — total akhir saja. --}}
    @php
        $podium    = $liveRows->filter(fn ($r) => $r['rank'] && $r['rank'] <= 3)->values();
        $scored    = $liveRows->filter(fn ($r) => $r['rank'])->values();
        $topTotal  = (float) ($scored->first()['total'] ?? 0);
        $secondTot = (float) ($scored->first(fn ($r) => $r['rank'] > 1)['total'] ?? 0);
        $showRun2  = $liveRows->contains(fn ($r) => $r['run2'] !== null);
        $podiumOrder = [1 => 2, 2 => 1, 3 => 3];
        $medal = [1 => 'var(--lime)', 2 => '#d9d9de', 3 => '#e0a35c'];
        $avatarUrl = fn ($rider) => $rider->avatar ? '/storage/' . ltrim($rider->avatar, '/') : null;
    @endphp
    <style>
        .rs-wrap { padding:32px 48px 72px; max-width:1100px; margin:0 auto; }
        .rs-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:28px; }
        .rs-podium { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; align-items:end; margin-bottom:36px; }
        .rs-card { position:relative; background:var(--panel); border:2px solid var(--ink); border-radius:var(--radius); overflow:hidden; text-align:center; }
        .rs-photo { position:relative; width:100%; background:var(--panel-2); display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .rs-photo img { width:100%; height:100%; object-fit:cover; }
        .rs-row { display:grid; grid-template-columns:56px minmax(0,1fr) 84px 84px 130px; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--line); }
        .rs-row:last-child { border-bottom:none; }
        @media (max-width:760px) {
            .rs-wrap { padding:20px 16px 56px; }
            .rs-podium { grid-template-columns:1fr; gap:12px; }
            .rs-podium .rs-card { display:grid; grid-template-columns:96px minmax(0,1fr); text-align:left; align-items:center; order:0 !important; }
            .rs-podium .rs-photo { height:100% !important; min-height:96px; aspect-ratio:auto !important; }
            .rs-podium .rs-badge { top:auto !important; bottom:0; padding:2px 8px 0 !important; font-size:20px !important; }
            .rs-row { grid-template-columns:44px minmax(0,1fr) 84px; padding:10px 12px; }
            .rs-run { display:none !important; }
            .rs-stats { gap:8px; }
        }
    </style>
    <div class="rs-wrap">

        {{-- Ringkasan --}}
        <div class="rs-stats">
            @foreach([
                ['PESERTA', $liveRows->count()],
                ['SKOR TERTINGGI', $topTotal > 0 ? number_format($topTotal, 1) : '—'],
                ['SELISIH JUARA 1–2', ($topTotal > 0 && $secondTot > 0) ? '+' . number_format($topTotal - $secondTot, 1) : '—'],
            ] as [$lbl, $val])
                <div class="panel" style="padding:14px 16px;">
                    <span class="kicker" style="display:block;font-size:9px;margin-bottom:6px;">{{ $lbl }}</span>
                    <span class="display tnum" style="font-size:clamp(26px,4vw,40px);">{{ $val }}</span>
                </div>
            @endforeach
        </div>

        {{-- Podium --}}
        @if($podium->isNotEmpty())
            <span class="kicker" style="display:block;margin-bottom:14px;color:var(--lime);">🏆 PODIUM</span>
            <div class="rs-podium">
                @foreach($podium as $row)
                    @php
                        $r      = $row['rank'];
                        $first  = $r === 1;
                        $accent = $medal[$r];
                        $url    = $avatarUrl($row['rider']);
                    @endphp
                    <div class="rs-card" style="order:{{ $podiumOrder[$r] ?? 4 }};{{ $first ? 'border-color:var(--lime);box-shadow:0 0 0 1px var(--lime),0 18px 48px -12px rgba(198,255,58,.35);' : '' }}">
                        <div class="rs-photo" style="aspect-ratio:{{ $first ? '4/4.6' : '4/4' }};">
                            {{-- Inisial selalu dirender di bawah; foto menutupinya dan disembunyikan kalau gagal dimuat --}}
                            <span class="display" style="font-size:{{ $first ? 96 : 76 }}px;color:var(--ink-faint);">{{ $row['rider']->initials }}</span>
                            @if($url)
                                <img src="{{ $url }}" alt="" loading="lazy" style="position:absolute;inset:0;" onerror="this.style.display='none'">
                            @endif
                            <span class="display tnum rs-badge" style="position:absolute;top:0;left:0;padding:6px 14px 4px;font-size:{{ $first ? 44 : 34 }}px;background:{{ $accent }};color:#0a0a0b;">{{ str_pad($r, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div style="padding:14px 16px 18px;min-width:0;">
                            <span class="label" style="display:block;font-size:{{ $first ? 20 : 17 }}px;line-height:1.2;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $row['rider']->name }}</span>
                            <span class="mono dim" style="display:block;font-size:11px;margin:4px 0 10px;">{{ $row['rider']->city ?? '—' }}</span>
                            <span class="display tnum" style="font-size:{{ $first ? 54 : 40 }}px;color:{{ $first ? 'var(--lime)' : 'var(--ink)' }};{{ $first ? 'text-shadow:0 0 32px rgba(198,255,58,.45);' : '' }}">{{ number_format($row['total'], 1) }}</span>
                            <span class="mono dim" style="display:block;font-size:10px;letter-spacing:.12em;margin-top:2px;">
                                RUN 1 {{ $row['run1'] !== null ? number_format($row['run1'], 1) : '—' }}@if($showRun2) · RUN 2 {{ $row['run2'] !== null ? number_format($row['run2'], 1) : '—' }}@endif
                            </span>
                            @if(($row['best_trick_bonus'] ?? 0) > 0)
                                <span class="mono" style="display:block;font-size:10px;letter-spacing:.12em;margin-top:2px;color:var(--lime);">BEST {{ number_format($row['run2'] !== null ? max($row['run1'], $row['run2']) : $row['run1'], 1) }} + TRICK {{ number_format($row['best_trick_bonus'], 1) }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Klasemen lengkap --}}
        <div class="panel" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <span class="kicker">KLASEMEN FINAL</span>
                <span class="mono dim" style="font-size:11px;">{{ strtoupper($liveDivision->name) }}</span>
            </div>
            <div class="rs-row" style="padding-block:8px;background:var(--panel-2);">
                <span class="mono dim" style="font-size:10px;letter-spacing:.14em;">#</span>
                <span class="mono dim" style="font-size:10px;letter-spacing:.14em;">RIDER</span>
                <span class="mono dim rs-run" style="font-size:10px;letter-spacing:.14em;text-align:right;">RUN 1</span>
                <span class="mono dim rs-run" style="font-size:10px;letter-spacing:.14em;text-align:right;">{{ $showRun2 ? 'RUN 2' : '' }}</span>
                <span class="mono dim" style="font-size:10px;letter-spacing:.14em;text-align:right;">TOTAL</span>
            </div>
            @foreach($liveRows as $row)
                @php
                    $r   = $row['rank'];
                    $pct = ($r && $topTotal > 0) ? max(4, min(100, $row['total'] / $topTotal * 100)) : 0;
                    $url = $avatarUrl($row['rider']);
                @endphp
                <div class="rs-row" style="background:linear-gradient(90deg,color-mix(in srgb,var(--lime) {{ $r === 1 ? 16 : 8 }}%,transparent) {{ $pct }}%,transparent {{ $pct }}%);">
                    <span class="display tnum" style="font-size:30px;color:{{ $r === 1 ? 'var(--lime)' : ($r && $r <= 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ $r ? str_pad($r, 2, '0', STR_PAD_LEFT) : '—' }}</span>
                    <div class="flex" style="gap:12px;min-width:0;">
                        <div style="position:relative;width:42px;height:42px;flex-shrink:0;border-radius:50%;overflow:hidden;background:var(--panel-2);border:2px solid {{ $r === 1 ? 'var(--lime)' : 'var(--line-strong)' }};display:flex;align-items:center;justify-content:center;">
                            <span class="display" style="font-size:16px;color:var(--ink-dim);">{{ $row['rider']->initials }}</span>
                            @if($url)
                                <img src="{{ $url }}" alt="" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
                            @endif
                        </div>
                        <div class="col" style="min-width:0;">
                            <span class="label" style="font-size:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row['rider']->name }}</span>
                            <span class="mono dim" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $row['rider']->city ?? '—' }}@if(($row['best_trick_bonus'] ?? 0) > 0) · <span style="color:var(--lime);">+{{ number_format($row['best_trick_bonus'], 1) }} TRICK</span>@endif</span>
                        </div>
                    </div>
                    <span class="mono tnum dim rs-run" style="font-size:15px;text-align:right;">{{ $row['run1'] !== null ? number_format($row['run1'], 1) : '—' }}</span>
                    <span class="mono tnum dim rs-run" style="font-size:15px;text-align:right;">{{ $showRun2 ? ($row['run2'] !== null ? number_format($row['run2'], 1) : '—') : '' }}</span>
                    <span class="display tnum" style="font-size:30px;text-align:right;color:{{ $r === 1 ? 'var(--lime)' : 'var(--ink)' }};">{{ $r ? number_format($row['total'], 1) : '—' }}</span>
                </div>
            @endforeach
        </div>
    </div>

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

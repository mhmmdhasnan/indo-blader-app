<div class="col" style="gap:16px;">

    {{-- Context bar: Event + Mode --}}
    <div class="panel" style="padding:16px;">
        <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
            <div class="col" style="gap:6px;flex:1;min-width:180px;">
                <span class="mono dim" style="font-size:10px;">EVENT</span>
                <select wire:model.live="judgeEventId" class="input-field" style="width:100%;">
                    <option value="0">— pilih event —</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}">{{ $ev->title }} ({{ $ev->date_label }})</option>
                    @endforeach
                </select>
                @error('judgeEventId') <p style="color:var(--red);font-size:11px;">{{ $message }}</p> @enderror
            </div>
            <div class="col" style="gap:6px;min-width:200px;">
                <span class="mono dim" style="font-size:10px;">MODE SCORING</span>
                <div class="flex gap-s">
                    <button wire:click="$set('scoringMode','live')" class="btn btn-sm {{ $scoringMode === 'live' ? 'btn-lime' : 'btn-ghost' }}">★ Live Scoring</button>
                    <button wire:click="$set('scoringMode','knockout')" class="btn btn-sm {{ $scoringMode === 'knockout' ? 'btn-lime' : 'btn-ghost' }}">⚡ Knockout</button>
                </div>
            </div>
        </div>
    </div>

    @if($scoringMode === 'live')
        {{-- ── LIVE SCORING ── --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="prof-grid">
            <div class="panel" style="padding:22px;">
                <span class="kicker" style="display:block;margin-bottom:14px;">CONTEXT SCORING</span>
                <div class="col" style="gap:12px;margin-bottom:20px;">
                    <div>
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">RIDER</span>
                        <select wire:model.live="liveRiderId" class="input-field" style="width:100%;">
                            <option value="0">— pilih rider —</option>
                            @foreach($judgeRiders as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} {{ $r->nick ? "({$r->nick})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">RUN NUMBER</span>
                        <select wire:model.live="liveRunNumber" class="input-field" style="max-width:120px;">
                            <option value="1">Run 1</option>
                            <option value="2">Run 2</option>
                            <option value="3">Run 3</option>
                        </select>
                    </div>
                </div>

                @if($liveRiderId && $judgeRiders->find($liveRiderId))
                    @php $currentRider = $judgeRiders->find($liveRiderId); @endphp
                    <div class="flex" style="align-items:center;gap:12px;margin-bottom:20px;padding:12px;background:var(--bg-2);border-radius:3px;">
                        <x-avatar :initials="$currentRider->initials" :size="44" :ring="true" />
                        <div class="col">
                            <span class="display" style="font-size:22px;">{{ $currentRider->name }}</span>
                            <span class="mono dim" style="font-size:11px;">RUN {{ $liveRunNumber }}</span>
                        </div>
                    </div>
                @endif

                @foreach([
                    ['judgeExec',        'EXECUTION'],
                    ['judgeStyle',       'STYLE'],
                    ['judgeCreativity',  'CREATIVITY'],
                    ['judgeDiff',        'DIFFICULTY'],
                    ['judgeConsistency', 'CONSISTENCY'],
                ] as [$prop,$lbl])
                    <div style="margin-bottom:18px;">
                        <div class="between" style="margin-bottom:7px;">
                            <span class="mono" style="font-size:11px;letter-spacing:0.12em;">{{ $lbl }}</span>
                            <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ number_format($$prop, 1) }}</span>
                        </div>
                        <input type="range" min="0" max="10" step="0.1" wire:model.live="{{ $prop }}"
                            style="width:100%;accent-color:var(--lime);" />
                    </div>
                @endforeach
            </div>

            <div class="panel center col halftone" style="padding:22px;gap:8px;text-align:center;">
                <span class="kicker">FINAL SCORE</span>
                @php $total = (($judgeExec + $judgeStyle + $judgeCreativity + $judgeDiff + $judgeConsistency) / 5) * 10; @endphp
                <span class="display tnum text-glow-lime" style="font-size:clamp(70px,12vw,120px);color:var(--lime);line-height:0.8;">{{ number_format($total, 1) }}</span>
                <span class="mono dim" style="font-size:12px;">/ 100 · AVG OF 5 CRITERIA</span>
                @if($scoreSubmitted)
                    <span class="badge badge-lime" style="margin-top:14px;">✓ SCORE SUBMITTED</span>
                    <button wire:click="resetScore" class="btn btn-ghost btn-sm" style="margin-top:8px;">Score Next →</button>
                @else
                    <button wire:click="submitScore" class="btn btn-lime" style="margin-top:14px;"
                        @if(!$judgeEventId || !$liveRiderId) disabled @endif>Submit Score →</button>
                @endif
                <a href="{{ route('live') }}" class="btn btn-ghost btn-sm">View Live Board</a>
            </div>
        </div>

    @else
        {{-- ── KNOCKOUT SCORING ── --}}
        <div class="col" style="gap:14px;">
            {{-- Match selector --}}
            <div class="panel" style="padding:18px;">
                <span class="kicker" style="display:block;margin-bottom:12px;">PILIH MATCH</span>
                <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                    <div class="col" style="gap:6px;min-width:180px;">
                        <span class="mono dim" style="font-size:10px;">TIPE</span>
                        <div class="flex gap-s">
                            <button wire:click="$set('koMatchType','QUALIFICATION')" class="btn btn-sm {{ $koMatchType === 'QUALIFICATION' ? 'btn-lime' : 'btn-ghost' }}">Qualification</button>
                            <button wire:click="$set('koMatchType','BRACKET')" class="btn btn-sm {{ $koMatchType === 'BRACKET' ? 'btn-lime' : 'btn-ghost' }}">Bracket</button>
                        </div>
                    </div>
                    @if(!$judgeEventId)
                        <p class="mono dim" style="font-size:12px;">Pilih event di atas terlebih dahulu.</p>
                    @elseif(isset($koMatches) && $koMatches->count())
                        <div class="col" style="gap:6px;flex:1;min-width:220px;">
                            <span class="mono dim" style="font-size:10px;">MATCH</span>
                            <select wire:model.live="koMatchId" class="input-field" style="width:100%;">
                                <option value="0">— pilih match —</option>
                                @foreach($koMatches as $m)
                                    @if($koMatchType === 'QUALIFICATION')
                                        <option value="{{ $m->id }}">
                                            {{ $m->qualificationRound?->name }} — {{ $m->riderA?->name ?? '?' }} vs {{ $m->riderB?->name ?? '?' }}
                                        </option>
                                    @else
                                        <option value="{{ $m->id }}">
                                            {{ $m->round }} #{{ $m->match_number }} — {{ $m->riderA?->name ?? 'TBD' }} vs {{ $m->riderB?->name ?? 'TBD' }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @else
                        <p class="mono dim" style="font-size:12px;">Tidak ada pending {{ strtolower($koMatchType) }} matches untuk event ini.</p>
                    @endif
                </div>
            </div>

            {{-- Scoring panel untuk match yang dipilih --}}
            @if($koCurrentMatch)
                @php
                    $riderA = $koCurrentMatch->riderA;
                    $riderB = $koCurrentMatch->riderB;
                    $trick  = $koCurrentMatch->trick;
                    $totalA = (($judgeExec + $judgeStyle + $judgeCreativity + $judgeDiff + $judgeConsistency) / 5) * 10;
                    $totalB = (($judgeExecB + $judgeStyleB + $judgeCreativityB + $judgeDiffB + $judgeConsistencyB) / 5) * 10;
                @endphp

                {{-- Trick badge + match context --}}
                <div class="between" style="margin-bottom:12px;">
                    <span class="label">{{ $riderA?->name ?? 'TBD' }} <span class="dim">vs</span> {{ $riderB?->name ?? 'TBD' }}</span>
                    @if($trick)
                        <span class="badge badge-out" style="font-size:11px;">{{ $trick->name }} · {{ $trick->difficulty }}</span>
                    @endif
                </div>

                {{-- Two-column scoring: Rider A | Rider B --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">

                    {{-- ── Rider A ── --}}
                    <div class="panel" style="padding:18px;">
                        <div class="between" style="margin-bottom:14px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                @if($riderA)<x-avatar :initials="$riderA->initials" :size="28" />@endif
                                <div>
                                    <p class="label" style="font-size:13px;">{{ $riderA?->name ?? 'TBD' }}</p>
                                    <span class="mono dim" style="font-size:10px;">RIDER A</span>
                                </div>
                            </div>
                            <span class="display tnum text-glow-lime" style="font-size:28px;color:var(--lime);">{{ number_format($totalA, 1) }}</span>
                        </div>

                        @foreach([
                            ['judgeExec',        'EXECUTION'],
                            ['judgeStyle',       'STYLE'],
                            ['judgeCreativity',  'CREATIVITY'],
                            ['judgeDiff',        'DIFFICULTY'],
                            ['judgeConsistency', 'CONSISTENCY'],
                        ] as [$prop,$lbl])
                            <div style="margin-bottom:12px;">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;">{{ $lbl }}</span>
                                    <span class="display tnum" style="font-size:15px;color:var(--lime);">{{ number_format($$prop, 1) }}</span>
                                </div>
                                <input type="range" min="0" max="10" step="0.1" wire:model.live="{{ $prop }}"
                                    style="width:100%;accent-color:var(--lime);" />
                            </div>
                        @endforeach
                    </div>

                    {{-- ── Rider B ── --}}
                    <div class="panel" style="padding:18px;">
                        <div class="between" style="margin-bottom:14px;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                @if($riderB)<x-avatar :initials="$riderB->initials" :size="28" />@endif
                                <div>
                                    <p class="label" style="font-size:13px;">{{ $riderB?->name ?? 'TBD' }}</p>
                                    <span class="mono dim" style="font-size:10px;">RIDER B</span>
                                </div>
                            </div>
                            <span class="display tnum text-glow-lime" style="font-size:28px;color:var(--lime);">{{ number_format($totalB, 1) }}</span>
                        </div>

                        @foreach([
                            ['judgeExecB',        'EXECUTION'],
                            ['judgeStyleB',       'STYLE'],
                            ['judgeCreativityB',  'CREATIVITY'],
                            ['judgeDiffB',        'DIFFICULTY'],
                            ['judgeConsistencyB', 'CONSISTENCY'],
                        ] as [$prop,$lbl])
                            <div style="margin-bottom:12px;">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;">{{ $lbl }}</span>
                                    <span class="display tnum" style="font-size:15px;color:var(--lime);">{{ number_format($$prop, 1) }}</span>
                                </div>
                                <input type="range" min="0" max="10" step="0.1" wire:model.live="{{ $prop }}"
                                    style="width:100%;accent-color:var(--lime);" />
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Result bar + actions --}}
                <div class="panel col halftone" style="padding:18px;gap:12px;">
                    <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px;text-align:center;">
                        <div>
                            <span class="display tnum text-glow-lime" style="font-size:clamp(40px,6vw,72px);color:var(--lime);line-height:1;">{{ number_format($totalA, 1) }}</span>
                            <p class="mono dim" style="font-size:10px;margin-top:4px;">{{ $riderA?->name ?? 'RIDER A' }}</p>
                        </div>
                        <span class="mono" style="font-size:22px;color:var(--fg-muted);">VS</span>
                        <div>
                            <span class="display tnum" style="font-size:clamp(40px,6vw,72px);color:var(--fg);line-height:1;">{{ number_format($totalB, 1) }}</span>
                            <p class="mono dim" style="font-size:10px;margin-top:4px;">{{ $riderB?->name ?? 'RIDER B' }}</p>
                        </div>
                    </div>

                    @if($totalA !== $totalB)
                        <div style="text-align:center;">
                            <span class="badge badge-lime" style="font-size:11px;">
                                {{ $totalA > $totalB ? ($riderA?->name ?? 'Rider A') : ($riderB?->name ?? 'Rider B') }} unggul
                            </span>
                        </div>
                    @endif

                    <div class="flex gap-s" style="justify-content:center;flex-wrap:wrap;">
                        @if($scoreSubmitted)
                            <span class="badge badge-lime">✓ SCORE SAVED</span>
                            <button wire:click="resetScore" class="btn btn-ghost btn-sm">Score Next →</button>
                        @else
                            @if($koMatchType === 'BRACKET')
                                <button wire:click="submitKnockoutScore" class="btn btn-lime">Save Score</button>
                            @endif
                        @endif
                    </div>

                    {{-- Set winner --}}
                    <div style="padding-top:12px;border-top:1px solid var(--line);">
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:8px;">SET PEMENANG</span>
                        <div class="flex gap-s" style="flex-wrap:wrap;">
                            @if($riderA && $koMatchType === 'QUALIFICATION')
                                <button wire:click="setQualMatchWinner({{ $koMatchId }}, {{ $koCurrentMatch->rider_a_registration_id }})" class="btn btn-sm btn-lime" style="flex:1;justify-content:center;">{{ $riderA->name }} Wins</button>
                            @endif
                            @if($riderB && $koMatchType === 'QUALIFICATION')
                                <button wire:click="setQualMatchWinner({{ $koMatchId }}, {{ $koCurrentMatch->rider_b_registration_id }})" class="btn btn-sm btn-lime" style="flex:1;justify-content:center;">{{ $riderB->name }} Wins</button>
                            @endif
                            @if($riderA && $koMatchType === 'BRACKET')
                                <button wire:click="advanceBracketWinner({{ $koMatchId }}, {{ $koCurrentMatch->rider_a_registration_id }})" class="btn btn-sm btn-lime" style="flex:1;justify-content:center;">{{ $riderA->name }} Wins</button>
                            @endif
                            @if($riderB && $koMatchType === 'BRACKET')
                                <button wire:click="advanceBracketWinner({{ $koMatchId }}, {{ $koCurrentMatch->rider_b_registration_id }})" class="btn btn-sm btn-lime" style="flex:1;justify-content:center;">{{ $riderB->name }} Wins</button>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('live') }}" class="btn btn-ghost btn-sm" style="align-self:flex-start;">Live Board →</a>
                </div>
            @elseif($judgeEventId && $koMatchId === 0)
                <div class="panel center col" style="padding:40px;gap:12px;text-align:center;">
                    <span style="font-size:36px;">⚡</span>
                    <p class="dim">Pilih match di atas untuk mulai scoring.</p>
                </div>
            @endif
        </div>
    @endif
</div>

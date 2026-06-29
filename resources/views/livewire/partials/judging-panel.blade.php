<div class="col" style="gap:16px;">

    {{-- Context bar: Event label + Division + Mode --}}
    <div class="panel" style="padding:16px;">
        <div class="between" style="margin-bottom:12px;">
            <span class="kicker">JUDGE PANEL</span>
            @if($judgeEventId)
                <span class="mono" style="font-size:11px;color:var(--lime);">{{ strtoupper($events->firstWhere('id', $judgeEventId)?->title ?? '') }}</span>
            @else
                <span class="mono" style="font-size:11px;color:var(--red);">⚠ Tidak ada active event</span>
            @endif
        </div>
        <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">

            {{-- Division — muncul kalau event aktif punya divisi --}}
            @if($judgeEventId && $judgeDivisions->count())
            <div class="col" style="gap:6px;min-width:160px;">
                <span class="mono dim" style="font-size:10px;">DIVISI</span>
                <div class="flex gap-s" style="flex-wrap:wrap;">
                    <button wire:click="$set('judgeDivisionId', 0)"
                        class="btn btn-sm {{ $judgeDivisionId === 0 ? 'btn-lime' : 'btn-ghost' }}">
                        Semua
                    </button>
                    @foreach($judgeDivisions as $div)
                        <button wire:click="$set('judgeDivisionId', {{ $div->id }})"
                            class="btn btn-sm {{ $judgeDivisionId === $div->id ? 'btn-lime' : 'btn-ghost' }}">
                            {{ $div->name }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Scoring mode --}}
            <div class="col" style="gap:6px;min-width:180px;">
                <span class="mono dim" style="font-size:10px;">MODE SCORING</span>
                @php
                    $assignedMode = $judgeAssignment?->scoring_mode ?? 'BOTH';
                    $showLive     = in_array($assignedMode, ['LIVE', 'BOTH']);
                    $showKo       = in_array($assignedMode, ['KNOCKOUT', 'BOTH']);
                @endphp
                <div class="flex gap-s">
                    @if($showLive)
                        <button wire:click="$set('scoringMode','live')" class="btn btn-sm {{ $scoringMode === 'live' ? 'btn-lime' : 'btn-ghost' }}">★ Live</button>
                    @endif
                    @if($showKo)
                        <button wire:click="$set('scoringMode','knockout')" class="btn btn-sm {{ $scoringMode === 'knockout' ? 'btn-lime' : 'btn-ghost' }}">⚡ Knockout</button>
                    @endif
                </div>
            </div>

        </div>

        {{-- Active filter indicator --}}
        @if($judgeEventId && $judgeDivisionId)
            @php $activeDivName = $judgeDivisions->firstWhere('id', $judgeDivisionId)?->name; @endphp
            <div class="flex gap-s" style="margin-top:10px;align-items:center;padding-top:10px;border-top:1px solid var(--line);">
                <span class="mono dim" style="font-size:10px;">Filter aktif:</span>
                <span class="mono" style="font-size:10px;padding:2px 10px;background:var(--lime);color:#0a0a0b;font-weight:700;border-radius:2px;">{{ strtoupper($activeDivName) }}</span>
                <span class="mono dim" style="font-size:10px;">· hanya rider &amp; match divisi ini yang tampil</span>
            </div>
        @endif
    </div>

    @php
        $criteria  = $eventCriteria ?? collect();
        $critCount = $criteria->count() ?: 1;
        $totalLive = count($criteriaScores) > 0
            ? round((array_sum($criteriaScores) / $critCount) * 10, 1)
            : 0;
    @endphp

    @if($scoringMode === 'live')
        {{-- ── LIVE SCORING ── --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="prof-grid">
            <div class="panel" style="padding:22px;">
                <span class="kicker" style="display:block;margin-bottom:14px;">CONTEXT SCORING</span>
                <div class="col" style="gap:12px;margin-bottom:20px;">
                    <div>
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">
                            RIDER
                            @if($judgeDivisionId && isset($activeDivName))
                                <span style="color:var(--lime);margin-left:6px;">· {{ strtoupper($activeDivName) }}</span>
                            @endif
                        </span>
                        @if(!$judgeEventId)
                            <p class="mono dim" style="font-size:11px;">Pilih event terlebih dahulu.</p>
                        @elseif($judgeRiders->isEmpty())
                            <p class="mono" style="font-size:11px;color:var(--red);">Belum ada peserta approved untuk event{{ $judgeDivisionId ? ' / divisi' : '' }} ini.</p>
                        @else
                        <select wire:model.live="liveRiderId" class="input-field" style="width:100%;">
                            <option value="0">— pilih rider —</option>
                            @foreach($judgeRiders as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}{{ $r->division ? " · {$r->division->name}" : '' }}</option>
                            @endforeach
                        </select>
                        @endif
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
                    @php
                        $currentRider = $judgeRiders->find($liveRiderId);
                        $initials = collect(explode(' ', $currentRider->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('');
                    @endphp
                    <div class="flex" style="align-items:center;gap:12px;margin-bottom:20px;padding:12px;background:var(--bg-2);border-radius:3px;">
                        <x-avatar :initials="$initials" :size="44" :ring="true" />
                        <div class="col">
                            <span class="display" style="font-size:22px;">{{ $currentRider->name }}</span>
                            <span class="mono dim" style="font-size:11px;">
                                {{ $currentRider->division?->name ?? '' }} · RUN {{ $liveRunNumber }}
                            </span>
                        </div>
                    </div>
                @endif

                @forelse($criteria as $crit)
                    @php $val = $criteriaScores[$crit->key] ?? 9.0; @endphp
                    <div style="margin-bottom:18px;">
                        <div class="between" style="margin-bottom:7px;">
                            <span class="mono" style="font-size:11px;letter-spacing:0.12em;">{{ strtoupper($crit->name) }}</span>
                            <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                        </div>
                        <input type="range" min="0" max="10" step="0.1"
                            wire:model.live="criteriaScores.{{ $crit->key }}"
                            style="width:100%;accent-color:var(--lime);" />
                    </div>
                @empty
                    <p class="mono dim" style="font-size:12px;">Belum ada kriteria penilaian untuk event ini. Assign di Admin Panel.</p>
                @endforelse
            </div>

            <div class="col" style="gap:14px;">
                <div class="panel center col halftone" style="padding:22px;gap:8px;text-align:center;">
                    <span class="kicker">FINAL SCORE</span>
                    <span class="display tnum text-glow-lime" style="font-size:clamp(70px,12vw,120px);color:var(--lime);line-height:0.8;">{{ number_format($totalLive, 1) }}</span>
                    <span class="mono dim" style="font-size:12px;">/ 100 · AVG OF {{ $critCount }} CRITERIA</span>
                    @if($scoreSubmitted)
                        <span class="badge badge-lime" style="margin-top:14px;">✓ SCORE SUBMITTED</span>
                        <button wire:click="resetScore" class="btn btn-ghost btn-sm" style="margin-top:8px;">Score Next →</button>
                    @else
                        <button wire:click="submitScore" class="btn btn-lime" style="margin-top:14px;"
                            @if(!$judgeEventId || !$liveRiderId || $criteria->isEmpty()) disabled @endif>Submit Score →</button>
                    @endif
                    <a href="{{ route('live') }}" class="btn btn-ghost btn-sm">View Live Board</a>
                </div>

                {{-- All judges' scores + accumulated --}}
                @if(isset($otherJudgeScores) && $otherJudgeScores->count())
                    @php
                        $myJudgeId   = auth()->id();
                        $allTotals   = $otherJudgeScores->pluck('total')->map(fn($t) => (float)$t);
                        // Include current judge's live score in accumulation
                        if ($totalLive > 0) $allTotals->push($totalLive);
                        $accumulated = $allTotals->count() > 0 ? round($allTotals->avg(), 1) : 0;
                    @endphp
                    <div class="panel" style="padding:16px;">
                        <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;display:block;margin-bottom:10px;">NILAI SEMUA JUDGE</span>

                        {{-- Accumulated total --}}
                        <div style="padding:10px 12px;background:var(--surface-1,rgba(255,255,255,.04));border-radius:6px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;">
                            <span class="mono" style="font-size:11px;letter-spacing:0.1em;">AKUMULASI ({{ $allTotals->count() }} JUDGE)</span>
                            <span class="display tnum text-glow-lime" style="font-size:26px;color:var(--lime);">{{ number_format($accumulated, 1) }}</span>
                        </div>

                        {{-- Per-judge breakdown --}}
                        @foreach($otherJudgeScores as $js)
                            <div style="padding:8px 0;border-bottom:1px solid var(--line);">
                                <div class="between" style="margin-bottom:4px;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="label" style="font-size:12px;">{{ $js->judge?->name ?? 'Judge' }}</span>
                                        @if($js->judge_user_id === $myJudgeId)
                                            <span class="badge badge-lime" style="font-size:9px;padding:1px 5px;">KAMU</span>
                                        @endif
                                    </div>
                                    <span class="display tnum" style="font-size:18px;color:var(--lime);">{{ number_format($js->total, 1) }}</span>
                                </div>
                                <div class="flex gap-s" style="flex-wrap:wrap;">
                                    @foreach($js->scoreDetails as $detail)
                                        <span class="mono dim" style="font-size:10px;">{{ strtoupper($detail->criteria) }}: <span style="color:var(--ink);">{{ number_format($detail->score, 1) }}</span></span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Current judge's live entry (not yet submitted) --}}
                        @if($totalLive > 0 && !$otherJudgeScores->contains('judge_user_id', $myJudgeId))
                            <div style="padding:8px 0;">
                                <div class="between" style="margin-bottom:4px;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="label" style="font-size:12px;">{{ auth()->user()->name }}</span>
                                        <span class="badge badge-lime" style="font-size:9px;padding:1px 5px;">KAMU (live)</span>
                                    </div>
                                    <span class="display tnum" style="font-size:18px;color:var(--lime);">{{ number_format($totalLive, 1) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
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
                                            [{{ $m->bracket?->division?->name ?? '—' }}] {{ $m->round }} #{{ $m->match_number }} — {{ $m->riderA?->name ?? 'TBD' }} vs {{ $m->riderB?->name ?? 'TBD' }}
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
                    $riderA  = $koCurrentMatch->riderA;
                    $riderB  = $koCurrentMatch->riderB;
                    $trick   = $koCurrentMatch->trick;
                    $totalA  = count($criteriaScores) > 0
                        ? round((array_sum($criteriaScores) / $critCount) * 10, 1)
                        : 0;
                    $totalB  = count($criteriaScoresB) > 0
                        ? round((array_sum($criteriaScoresB) / $critCount) * 10, 1)
                        : 0;
                    $subsA   = isset($koApprovedSubmissions)
                        ? $koApprovedSubmissions->where('registration_id', $koCurrentMatch->rider_a_registration_id)->values()
                        : collect();
                    $subsB   = isset($koApprovedSubmissions)
                        ? $koApprovedSubmissions->where('registration_id', $koCurrentMatch->rider_b_registration_id)->values()
                        : collect();
                @endphp

                {{-- Trick badge --}}
                <div class="between" style="margin-bottom:4px;">
                    <span class="label">{{ $riderA?->name ?? 'TBD' }} <span class="dim">vs</span> {{ $riderB?->name ?? 'TBD' }}</span>
                    @if($trick)
                        <span class="badge badge-out" style="font-size:11px;">{{ $trick->name }} · {{ $trick->difficulty }}</span>
                    @endif
                </div>

                {{-- Video row --}}
                @if($subsA->count() || $subsB->count())
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            @foreach($subsA as $sub)
                                @php
                                    $igUrl = $sub->video_path ?? '';
                                    preg_match('#instagram\.com/(p|reel)/([A-Za-z0-9_-]+)#', $igUrl, $igm);
                                @endphp
                                @if(!empty($igm[2]))
                                    <iframe src="https://www.instagram.com/p/{{ $igm[2] }}/embed/"
                                        width="320" height="380"
                                        style="border:none;border-radius:4px;background:#000;display:block;"
                                        allowfullscreen scrolling="no" frameborder="0"></iframe>
                                    <a href="{{ $igUrl }}" target="_blank" class="btn btn-sm btn-ghost" style="margin-top:6px;font-size:11px;">Buka di Instagram ↗</a>
                                @elseif($igUrl)
                                    <a href="{{ $igUrl }}" target="_blank" class="btn btn-sm btn-ghost" style="font-size:11px;">▶ Video {{ $riderA?->name }}</a>
                                @endif
                            @endforeach
                        </div>
                        <div>
                            @foreach($subsB as $sub)
                                @php
                                    $igUrl = $sub->video_path ?? '';
                                    preg_match('#instagram\.com/(p|reel)/([A-Za-z0-9_-]+)#', $igUrl, $igm);
                                @endphp
                                @if(!empty($igm[2]))
                                    <iframe src="https://www.instagram.com/p/{{ $igm[2] }}/embed/"
                                        width="320" height="380"
                                        style="border:none;border-radius:4px;background:#000;display:block;"
                                        allowfullscreen scrolling="no" frameborder="0"></iframe>
                                    <a href="{{ $igUrl }}" target="_blank" class="btn btn-sm btn-ghost" style="margin-top:6px;font-size:11px;">Buka di Instagram ↗</a>
                                @elseif($igUrl)
                                    <a href="{{ $igUrl }}" target="_blank" class="btn btn-sm btn-ghost" style="font-size:11px;">▶ Video {{ $riderB?->name }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Two-column scoring --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">

                    {{-- Rider A --}}
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
                        @forelse($criteria as $crit)
                            @php $val = $criteriaScores[$crit->key] ?? 9.0; @endphp
                            <div style="margin-bottom:12px;">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;">{{ strtoupper($crit->name) }}</span>
                                    <span class="display tnum" style="font-size:15px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                                </div>
                                <input type="range" min="0" max="10" step="0.1"
                                    wire:model.live="criteriaScores.{{ $crit->key }}"
                                    style="width:100%;accent-color:var(--lime);" />
                            </div>
                        @empty
                            <p class="mono dim" style="font-size:11px;">Belum ada kriteria — assign di Admin.</p>
                        @endforelse
                    </div>

                    {{-- Rider B --}}
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
                        @forelse($criteria as $crit)
                            @php $val = $criteriaScoresB[$crit->key] ?? 9.0; @endphp
                            <div style="margin-bottom:12px;">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;">{{ strtoupper($crit->name) }}</span>
                                    <span class="display tnum" style="font-size:15px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                                </div>
                                <input type="range" min="0" max="10" step="0.1"
                                    wire:model.live="criteriaScoresB.{{ $crit->key }}"
                                    style="width:100%;accent-color:var(--lime);" />
                            </div>
                        @empty
                            <p class="mono dim" style="font-size:11px;">Belum ada kriteria — assign di Admin.</p>
                        @endforelse
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
                        @else
                            @if($koMatchType === 'BRACKET')
                                <button wire:click="submitKnockoutScore" class="btn btn-lime" @if($criteria->isEmpty()) disabled @endif>Save Score</button>
                            @endif
                        @endif
                    </div>

                    @error('koMatchId') <p style="color:var(--red);font-size:11px;text-align:center;">{{ $message }}</p> @enderror

                    {{-- Set winner — head judge only --}}
                    <div style="padding-top:12px;border-top:1px solid var(--line);">
                        @if($koCurrentMatch->winner_registration_id)
                            <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;">
                                <span class="badge badge-lime">Pemenang: {{ $koCurrentMatch->winner?->name }}</span>
                                @if(auth()->user()->isHeadJudge())
                                    @if($koMatchType === 'QUALIFICATION')
                                        <button wire:click="resetQualMatchWinner({{ $koMatchId }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Batalkan pemenang match ini?">Batalkan</button>
                                    @else
                                        <button wire:click="resetBracketMatchWinner({{ $koMatchId }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Batalkan pemenang? Score akan direset.">Batalkan</button>
                                    @endif
                                @endif
                            </div>
                        @elseif(auth()->user()->isHeadJudge())
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
                        @else
                            <p class="mono dim" style="font-size:11px;">Hanya Head Judge yang dapat menetapkan pemenang.</p>
                        @endif
                    </div>

                    <a href="{{ route('live') }}" class="btn btn-ghost btn-sm" style="align-self:flex-start;">Live Board →</a>
                </div>

                {{-- KO: Other judges' scores + accumulated --}}
                @php
                    $koScoresA = $koOtherJudgeScoresA ?? collect();
                    $koScoresB = $koOtherJudgeScoresB ?? collect();
                    $avgA      = $koScoresA->count() ? round($koScoresA->avg('total'), 1) : null;
                    $avgB      = $koScoresB->count() ? round($koScoresB->avg('total'), 1) : null;
                @endphp
                <div class="panel" style="padding:16px;">
                    <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;display:block;margin-bottom:12px;">NILAI SEMUA JUDGE</span>

                    {{-- Accumulated bar --}}
                    <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px;text-align:center;padding:10px 12px;background:var(--surface-1,rgba(255,255,255,.04));border-radius:6px;margin-bottom:14px;">
                        <div>
                            <span class="display tnum text-glow-lime" style="font-size:28px;color:var(--lime);">
                                {{ $avgA !== null ? number_format($avgA, 1) : '—' }}
                            </span>
                            <p class="mono dim" style="font-size:9px;margin-top:2px;">{{ $riderA?->name ?? 'RIDER A' }}<br>avg {{ $koScoresA->count() }} judge</p>
                        </div>
                        <span class="mono dim" style="font-size:14px;">VS</span>
                        <div>
                            <span class="display tnum" style="font-size:28px;color:var(--fg);">
                                {{ $avgB !== null ? number_format($avgB, 1) : '—' }}
                            </span>
                            <p class="mono dim" style="font-size:9px;margin-top:2px;">{{ $riderB?->name ?? 'RIDER B' }}<br>avg {{ $koScoresB->count() }} judge</p>
                        </div>
                    </div>

                    {{-- Per-judge rows --}}
                    @if($koScoresA->count())
                        @foreach($koScoresA as $jsA)
                            @php $jsB = $koScoresB->firstWhere('judge_user_id', $jsA->judge_user_id); @endphp
                            <div style="padding:8px 0;border-bottom:1px solid var(--line);">
                                <div class="between" style="margin-bottom:5px;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="label" style="font-size:12px;">{{ $jsA->judge?->name ?? 'Judge' }}</span>
                                        @if($jsA->judge_user_id === auth()->id())
                                            <span class="badge badge-lime" style="font-size:9px;padding:1px 5px;">KAMU</span>
                                        @endif
                                    </div>
                                    <div style="display:flex;gap:16px;">
                                        <span class="mono" style="font-size:11px;">{{ $riderA?->name ?? 'A' }}: <span class="tnum" style="color:var(--lime);">{{ number_format($jsA->total, 1) }}</span></span>
                                        @if($jsB)
                                            <span class="mono" style="font-size:11px;">{{ $riderB?->name ?? 'B' }}: <span class="tnum" style="color:var(--fg);">{{ number_format($jsB->total, 1) }}</span></span>
                                        @endif
                                    </div>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    <div class="flex gap-s" style="flex-wrap:wrap;">
                                        @foreach($jsA->scoreDetails as $d)
                                            <span class="mono dim" style="font-size:9px;">{{ strtoupper($d->criteria) }}: <span style="color:var(--ink);">{{ number_format($d->score, 1) }}</span></span>
                                        @endforeach
                                    </div>
                                    @if($jsB)
                                        <div class="flex gap-s" style="flex-wrap:wrap;">
                                            @foreach($jsB->scoreDetails as $d)
                                                <span class="mono dim" style="font-size:9px;">{{ strtoupper($d->criteria) }}: <span style="color:var(--ink);">{{ number_format($d->score, 1) }}</span></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="mono dim" style="font-size:11px;text-align:center;padding:12px 0;">Belum ada judge yang submit score untuk match ini.</p>
                    @endif
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

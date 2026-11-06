{{-- Penilaian live milik Judge / Head Judge (slider kriteria, Best Trick, atau form heat Jamming). Dipakai juga di Head Judge console. Expects scope judging-panel ($isRunning, $criteria, $criteriaScores, ...) --}}
        @if($isOperator)
            {{-- Operator tidak menilai — panel setup di atas (setelah filter divisi/group) sudah cukup --}}
        @elseif(($jamDivision ?? null) && ($isHeadJudge || $isRunning))
            @include('livewire.partials.jam-scoring', ['isRunning' => $isRunning && ($activeEvent?->live_jam ?? false), 'jamAllScores' => $jamAllScores ?? [], 'isHeadJudge' => $isHeadJudge])
        @elseif(!$isHeadJudge && !$isRunning)
            {{-- Judge biasa: standby state --}}
            <div class="panel center col" style="padding:48px;gap:14px;text-align:center;">
                <span class="live-dot" style="width:14px;height:14px;margin:0 auto;opacity:0.5;"></span>
                <span class="kicker">MENUNGGU RUN DIMULAI</span>
                <p class="mono dim" style="font-size:12px;max-width:280px;">
                    Head Judge belum memulai sesi run.<br>Slider penilaian akan muncul otomatis saat run aktif.
                </p>
            </div>
        @else
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="prof-grid">
            <div class="panel" style="padding:22px;">
                <span class="kicker" style="display:block;margin-bottom:14px;">CONTEXT SCORING</span>

                {{-- Rider yang sudah disiapkan Operator (read-only untuk judge/head judge) --}}
                @php
                    $lockedRider = $liveRiderId ? $judgeRiders->find($liveRiderId) : null;
                    $lockedInitials = $lockedRider ? collect(preg_split('/\s+/', trim($lockedRider->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('') : '?';
                @endphp
                <div class="flex" style="align-items:center;gap:12px;margin-bottom:20px;padding:12px;background:{{ $isRunning ? 'color-mix(in srgb,var(--red) 8%,transparent)' : 'var(--bg-2)' }};border:1px solid {{ $isRunning ? 'var(--red)' : 'var(--line)' }};border-radius:3px;">
                    <x-avatar :initials="$lockedInitials" :src="$lockedRider?->photo_url" :size="72" :ring="true" />
                    <div class="col">
                        <span class="display" style="font-size:40px;line-height:1.05;letter-spacing:.02em;">{{ $lockedRider?->name ?? 'Menunggu...' }}</span>
                        <span class="mono" style="font-size:11px;color:{{ $isRunning ? 'var(--red)' : 'var(--ink-dim)' }};">
                            @if($isRunning)
                                <span class="live-dot" style="width:8px;height:8px;display:inline-block;vertical-align:middle;margin-right:4px;"></span>
                                {{ ($isBestTrickPhase ?? false) ? 'PERCOBAAN' : 'RUN' }} {{ $liveRunNumber }} — AKTIF
                            @else
                                {{ $lockedRider?->division?->name ?? '' }} · {{ ($isBestTrickPhase ?? false) ? 'PERCOBAAN' : 'RUN' }} {{ $liveRunNumber }}
                            @endif
                        </span>
                    </div>
                </div>

                @if($scoreReopened ?? false)
                    <div class="panel" style="padding:10px 14px;margin-bottom:16px;border:2px solid var(--red);background:color-mix(in srgb,var(--red) 8%,transparent);">
                        <span class="mono" style="font-size:11px;color:var(--red);">⚠ Head Judge meminta Anda merevisi skor. Nilai sebelumnya sudah dimuat ulang — sesuaikan lalu submit ulang.</span>
                    </div>
                @endif

                @if($isRunning && ($isBestTrickPhase ?? false))
                    <div style="margin-bottom:18px;">
                        <div class="between" style="margin-bottom:7px;">
                            <span class="mono" style="font-size:11px;letter-spacing:0.12em;">SKOR BEST TRICK (0-10)</span>
                        </div>
                        @if($scoreSubmitted)
                            <span class="display tnum" style="font-size:32px;color:var(--lime);">{{ number_format($bestTrickScore ?? 0, 1) }}</span>
                        @else
                            @include('livewire.partials.score-keypad', ['model' => 'bestTrickScore', 'value' => $bestTrickScore, 'max' => 10, 'label' => $lockedRider?->name ?? 'Best Trick'])
                        @endif
                    </div>
                @elseif($isRunning)
                    @if($criteria->isEmpty())
                        <p class="mono dim" style="font-size:12px;">Belum ada kriteria penilaian untuk event ini. Assign di Admin Panel.</p>
                    @elseif($scoreSubmitted)
                        <div class="col" style="gap:6px;">
                            @foreach($criteria as $crit)
                                <div class="between"><span class="mono" style="font-size:11px;letter-spacing:0.12em;">{{ strtoupper($crit->name) }}</span>
                                    <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ is_numeric($criteriaScores[$crit->key] ?? null) ? number_format($criteriaScores[$crit->key], 1) : '—' }}</span></div>
                            @endforeach
                            <p class="mono dim" style="font-size:11px;">Nilai terkirim dan terkunci. Revisi lewat Head Judge.</p>
                        </div>
                    @else
                        @include('livewire.partials.score-input', ['criteria' => $criteria, 'modelPrefix' => 'criteriaScores', 'scores' => $criteriaScores, 'name' => $lockedRider?->name ?? 'Nilai', 'max' => 100])
                        @error('criteriaScores') <p class="mono" style="font-size:11px;color:var(--red);margin-top:8px;">{{ $message }}</p> @enderror
                    @endif
                @else
                    <p class="mono dim" style="font-size:12px;margin-top:12px;">Slider penilaian akan muncul saat run dimulai.</p>
                @endif
            </div>

            <div class="col" style="gap:14px;">
                @if($isRunning && ($isBestTrickPhase ?? false))
                <div class="panel center col halftone" style="padding:22px;gap:8px;text-align:center;" wire:key="best-trick-panel-{{ $liveRiderId }}-{{ $liveRunNumber }}">
                    <span class="kicker">BEST TRICK SCORE</span>
                    <span class="display tnum text-glow-lime" style="font-size:clamp(70px,12vw,120px);color:var(--lime);line-height:0.8;">{{ number_format($bestTrickScore ?? 0, 1) }}</span>
                    <span class="mono dim" style="font-size:12px;">/ 10 · PERCOBAAN {{ $liveRunNumber }}</span>
                    @if($scoreSubmitted)
                        <span class="badge badge-lime" style="margin-top:14px;">✓ SCORE SUBMITTED</span>
                    @elseif(!($hideInlineSubmit ?? false))
                        <button wire:click="submitBestTrickScore" class="btn btn-lime" style="margin-top:14px;"
                            wire:loading.attr="disabled" wire:target="submitBestTrickScore"
                            @if(!$judgeEventId || !$liveRiderId || $bestTrickScore === null) disabled @endif>
                            <span wire:loading.remove wire:target="submitBestTrickScore">Submit Score →</span>
                            <span wire:loading wire:target="submitBestTrickScore">Mengirim…</span>
                        </button>
                    @endif
                </div>
                @elseif($isRunning)
                <div class="panel center col halftone" style="padding:22px;gap:8px;text-align:center;" wire:key="final-score-panel-{{ $liveRiderId }}-{{ $liveRunNumber }}">
                    <span class="kicker">FINAL SCORE</span>
                    <span class="display tnum text-glow-lime" style="font-size:clamp(70px,12vw,120px);color:var(--lime);line-height:0.8;">{{ number_format($totalLive, 1) }}</span>
                    <span class="mono dim" style="font-size:12px;">/ 100 · AVG OF {{ $critCount }} CRITERIA</span>
                    @if($scoreSubmitted)
                        <span class="badge badge-lime" style="margin-top:14px;">✓ SCORE SUBMITTED</span>
                    @elseif(!($hideInlineSubmit ?? false))
                        <button wire:click="submitScore" class="btn btn-lime" style="margin-top:14px;"
                            wire:loading.attr="disabled" wire:target="submitScore"
                            @if(!$judgeEventId || !$liveRiderId || $criteria->isEmpty()) disabled @endif>
                            <span wire:loading.remove wire:target="submitScore">Submit Score →</span>
                            <span wire:loading wire:target="submitScore">Mengirim…</span>
                        </button>
                    @endif
                </div>
                @endif {{-- isRunning --}}

                {{-- All judges' scores + accumulated --}}
                @if($isHeadJudge && isset($otherJudgeScores) && $otherJudgeScores->count())
                    @php
                        $myJudgeId   = auth()->id();
                        // Di mode Best Trick, skor juri yang lagi diketik itu skala 0-10 langsung
                        // (bukan rata-rata kriteria 0-100 kayak $totalLive) — jangan sampai ketuker.
                        $currentJudgeValue = ($isBestTrickPhase ?? false) ? ($bestTrickScore ?? 0) : $totalLive;
                        $allTotals   = $otherJudgeScores->pluck('total')->map(fn($t) => (float)$t);
                        // Include current judge's live score in accumulation
                        if ($currentJudgeValue > 0) $allTotals->push($currentJudgeValue);
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
                        @if($currentJudgeValue > 0 && !$otherJudgeScores->contains('judge_user_id', $myJudgeId))
                            <div style="padding:8px 0;">
                                <div class="between" style="margin-bottom:4px;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="label" style="font-size:12px;">{{ auth()->user()->name }}</span>
                                        <span class="badge badge-lime" style="font-size:9px;padding:1px 5px;">KAMU (live)</span>
                                    </div>
                                    <span class="display tnum" style="font-size:18px;color:var(--lime);">{{ number_format($currentJudgeValue, 1) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif {{-- end standby/active check --}}

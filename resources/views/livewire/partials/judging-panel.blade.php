<div class="col" style="gap:16px;" wire:poll.5s="syncLiveState">

    @php
        $isHeadJudge = auth()->user()->isHeadJudge();
        $isOperator  = auth()->user()->isOperator();
    @endphp

    {{-- ── OPERATOR STATUS SUMMARY (LIVE CONTROL milik Head Judge sekarang di bawah Context Scoring) ── --}}
    @if($isOperator && $scoringMode === 'live')
    <div class="panel" style="border:2px solid var(--lime);padding:18px;">
        <div class="between" style="margin-bottom:14px;">
            <span class="kicker" style="color:var(--lime);">⚙ OPERATOR — SETUP</span>
            @if($activeEvent?->live_phase)
                <span class="badge badge-red"><span class="live-dot" style="margin-right:5px;"></span>{{ $activeEvent->live_phase }}</span>
            @else
                <span class="badge badge-out">STANDBY</span>
            @endif
        </div>

        {{-- Idle screen: paksa /live nampilin logo FRAMEBLADESCORE aja, misal pas jeda
             antar sesi atau sebelum event mulai — independen dari state run/phase. --}}
        <div style="margin-bottom:14px;">
            @if($activeEvent?->idle_screen)
                <button wire:click="hideIdleScreen" class="btn btn-sm btn-lime" style="width:100%;justify-content:center;">▶ Kembali ke Live Score</button>
            @else
                <button wire:click="showIdleScreen" class="btn btn-sm btn-ghost" style="width:100%;justify-content:center;">🌙 Tampilkan Idle Screen</button>
            @endif
        </div>

        @if(!$activeEvent?->live_phase || $activeEvent?->live_phase === 'NEXT')

            @if($activeEvent?->live_phase === 'NEXT')
                @php $nextR = $activeEvent->live_rider_id ? \App\Models\Rider::find($activeEvent->live_rider_id) : null; @endphp
                <div class="flex" style="align-items:center;gap:10px;padding:10px;background:var(--bg-2);border-radius:3px;">
                    <span style="font-size:13px;">→</span>
                    <span class="label" style="font-size:13px;">{{ $nextR?->name ?? '—' }}</span>
                    <span class="mono dim" style="font-size:10px;margin-left:auto;">NEXT UP · RUN {{ $activeEvent->live_run_number }}</span>
                </div>
            @endif

        @elseif($activeEvent->live_phase === 'RUNNING')
            @php $liveR = $activeEvent->live_rider_id ? \App\Models\Rider::find($activeEvent->live_rider_id) : null; @endphp
            <div class="flex" style="align-items:center;gap:10px;margin-bottom:12px;padding:10px;background:var(--bg-2);border-radius:3px;">
                <span class="live-dot"></span>
                <span class="label" style="font-size:13px;">{{ $liveR?->name ?? 'Rider' }}</span>
                <span class="mono dim" style="font-size:10px;">RUN {{ $activeEvent->live_run_number }}</span>
                <span class="mono" style="font-size:10px;margin-left:auto;color:var(--lime);">{{ ($isBestTrickPhase ?? false) ? '1 TRICK' : $activeEvent->run_duration . 's' }}</span>
            </div>

            @include('livewire.partials.judge-status-list', ['assignedJudges' => $assignedJudges ?? collect(), 'liveJudgeScores' => $liveJudgeScores ?? collect(), 'isHeadJudge' => $isHeadJudge])

            <p class="mono dim" style="font-size:11px;">Menunggu Head Judge klik REVEAL SCORE.</p>

        @elseif($activeEvent->live_phase === 'REVEALING')
            <p class="mono" style="font-size:11px;margin-bottom:12px;color:var(--lime);">
                Skor sedang ditampilkan di layar publik.
            </p>

            @include('livewire.partials.judge-status-list', ['assignedJudges' => $assignedJudges ?? collect(), 'liveJudgeScores' => $liveJudgeScores ?? collect(), 'isHeadJudge' => $isHeadJudge])

            <button wire:click="endSession" class="btn btn-ghost" style="width:100%;justify-content:center;">
                ← KEMBALI KE LEADERBOARD
            </button>
        @endif
    </div>
    @endif

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
                @if($isOperator)
                    <div class="flex gap-s" style="flex-wrap:wrap;">
                        <button wire:click="$set('judgeDivisionId', 0)"
                            class="btn btn-sm {{ $judgeDivisionId === 0 ? 'btn-lime' : 'btn-ghost' }}">
                            Semua
                        </button>
                        @foreach($judgeDivisions as $div)
                            <button wire:click="$set('judgeDivisionId', {{ $div->id }})"
                                class="btn btn-sm {{ $judgeDivisionId === $div->id ? 'btn-lime' : 'btn-ghost' }}">
                                {{ $div->name }}
                                @if($scoringMode === 'live')
                                    <span class="mono" style="font-size:8px;opacity:0.7;">{{ $div->live_stage === 'FINAL' ? 'FINAL' : 'QUALI' }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @else
                    @php $mirroredDiv = $judgeDivisions->firstWhere('id', $judgeDivisionId); @endphp
                    <div class="mono" style="padding:8px 12px;border:2px solid var(--line);border-radius:3px;font-size:12px;">
                        {{ $mirroredDiv?->name ?? 'Semua divisi' }}
                        <span class="dim" style="font-size:10px;">· mengikuti Operator</span>
                    </div>
                @endif
            </div>
            @endif

            {{-- Group — muncul kalau divisi terpilih punya group kualifikasi --}}
            @if($judgeEventId && $scoringMode === 'live' && $judgeDivisionId && ($judgeGroups ?? collect())->count())
            <div class="col" style="gap:6px;min-width:160px;">
                <span class="mono dim" style="font-size:10px;">GROUP</span>
                @if($isOperator)
                    <div class="flex gap-s" style="flex-wrap:wrap;">
                        <button wire:click="$set('judgeGroupId', 0)"
                            class="btn btn-sm {{ $judgeGroupId === 0 ? 'btn-lime' : 'btn-ghost' }}">
                            Semua
                        </button>
                        @foreach($judgeGroups as $g)
                            <button wire:click="$set('judgeGroupId', {{ $g->id }})"
                                class="btn btn-sm {{ $judgeGroupId === $g->id ? 'btn-lime' : 'btn-ghost' }}">
                                {{ $g->name }}
                            </button>
                        @endforeach
                    </div>
                @else
                    @php $mirroredGroup = $judgeGroups->firstWhere('id', $judgeGroupId); @endphp
                    <div class="mono" style="padding:8px 12px;border:2px solid var(--line);border-radius:3px;font-size:12px;">
                        {{ $mirroredGroup?->name ?? 'Semua group' }}
                        <span class="dim" style="font-size:10px;">· mengikuti Operator</span>
                    </div>
                @endif
            </div>
            @endif

            {{-- Scoring mode — operator selalu di mode live, tidak perlu pindah --}}
            @if(!$isOperator)
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
            @endif

        </div>

        {{-- Active filter indicator --}}
        @if($judgeEventId && $judgeDivisionId)
            @php
                $activeDiv     = $judgeDivisions->firstWhere('id', $judgeDivisionId);
                $activeDivName = $activeDiv?->name;
            @endphp
            <div class="flex gap-s" style="margin-top:10px;align-items:center;padding-top:10px;border-top:1px solid var(--line);">
                <span class="mono dim" style="font-size:10px;">Filter aktif:</span>
                <span class="mono" style="font-size:10px;padding:2px 10px;background:var(--lime);color:#0a0a0b;font-weight:700;border-radius:2px;">{{ strtoupper($activeDivName) }}</span>
                <span class="mono dim" style="font-size:10px;">· hanya rider &amp; match divisi ini yang tampil</span>
                @if($scoringMode === 'live' && $activeDiv?->live_stage === 'FINAL')
                    <span class="mono dim" style="font-size:10px;">· fase FINAL — hanya finalis (dipilih manual) yang tampil di rider picker</span>
                @endif
                @if($scoringMode === 'live' && $activeDiv?->live_stage !== 'FINAL' && $judgeGroupId)
                    @php $activeGroupName = ($judgeGroups ?? collect())->firstWhere('id', $judgeGroupId)?->name; @endphp
                    <span class="mono dim" style="font-size:10px;">· group {{ strtoupper($activeGroupName) }}</span>
                @endif
            </div>
        @endif

        {{-- Operator: pengumuman publik untuk divisi yang sedang aktif.
             Dua tombol ini SENGAJA independen (bukan else-else berdasarkan live_stage) —
             begitu admin simpan finalis, live_stage langsung pindah ke FINAL, jadi kalau
             pakai else-else, tombol "Umumkan Hasil Kualifikasi" bakal hilang duluan
             sebelum sempat diklik. Umumkan kualifikasi tetap bisa dipencet kapan saja
             selama finalisnya sudah dipilih, gak peduli live_stage-nya udah FINAL atau
             belum. --}}
        @if($isOperator && $scoringMode === 'live' && ($activeDiv ?? null))
            @php $activeDivHasFinalists = \App\Models\DivisionFinalist::where('event_division_id', $activeDiv->id)->exists(); @endphp
            <div class="flex gap-s" style="margin-top:10px;flex-wrap:wrap;align-items:center;padding-top:10px;border-top:1px solid var(--line);">
                @if($activeDivHasFinalists)
                    @if($activeDiv->qualification_announced_at)
                        <button wire:click="unannounceQualificationResults({{ $activeDiv->id }})" class="btn btn-sm btn-ghost">🔇 Batalkan Pengumuman Kualifikasi</button>
                    @else
                        <button wire:click="announceQualificationResults({{ $activeDiv->id }})" class="btn btn-sm btn-lime">📢 Umumkan Hasil Kualifikasi</button>
                    @endif
                @endif
                @if($activeDiv->live_stage === 'FINAL')
                    @if($activeDiv->final_announced_at)
                        <button wire:click="unannounceFinalResults({{ $activeDiv->id }})" class="btn btn-sm btn-ghost">🔇 Batalkan Pengumuman Juara</button>
                    @else
                        <button wire:click="announceFinalResults({{ $activeDiv->id }})" class="btn btn-sm btn-lime">🏆 Umumkan Pemenang Final</button>
                    @endif
                @endif
            </div>
            @error('judgeEventId') <p style="color:var(--red);font-size:11px;margin-top:6px;">{{ $message }}</p> @enderror
        @endif
    </div>

    {{-- Operator setup panel — muncul setelah filter divisi/group di atas --}}
    @if($isOperator && $scoringMode === 'live' && (!$activeEvent?->live_phase || $activeEvent?->live_phase === 'NEXT'))
    <div class="panel" style="border:2px solid var(--lime);padding:18px;">
        <span class="kicker" style="color:var(--lime);display:block;margin-bottom:14px;">⚙ SETUP RUN</span>
        <div class="col" style="gap:12px;margin-bottom:12px;">
            <div>
                <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">
                    RIDER
                    @if($judgeDivisionId && isset($activeDivName))
                        <span style="color:var(--lime);margin-left:6px;">· {{ strtoupper($activeDivName) }}</span>
                    @endif
                </span>
                @if(!$judgeEventId)
                    <p class="mono dim" style="font-size:11px;">Pilih event terlebih dahulu.</p>
                @elseif($judgeRiders->isEmpty() && ($activeDiv ?? null)?->live_stage === 'FINAL')
                    <p class="mono" style="font-size:11px;color:var(--red);">Belum ada finalis untuk divisi ini. Pilih finalis dulu di tab Events → Divisi.</p>
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
            <div class="flex gap-s" style="align-items:flex-end;">
                <div>
                    @php $isBestTrickDivision = ($activeDiv ?? null)?->best_trick_active ?? false; @endphp
                    <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">{{ $isBestTrickDivision ? 'PERCOBAAN' : 'RUN NUMBER' }}</span>
                    <select wire:model.live="liveRunNumber" class="input-field" style="max-width:120px;">
                        <option value="1">{{ $isBestTrickDivision ? 'Percobaan 1' : 'Run 1' }}</option>
                        <option value="2">{{ $isBestTrickDivision ? 'Percobaan 2' : 'Run 2' }}</option>
                        <option value="3">{{ $isBestTrickDivision ? 'Percobaan 3' : 'Run 3' }}</option>
                    </select>
                </div>
                <button wire:click="showNextRider"
                    class="btn btn-ghost btn-sm"
                    @if(!$liveRiderId) disabled @endif>
                    👁 Preview
                </button>
            </div>
        </div>
        @if($activeEvent?->live_phase === 'NEXT')
            <button wire:click="endSession" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;font-size:11px;">
                ✗ Batal Preview
            </button>
        @endif
    </div>
    @endif

    {{-- Operator: leaderboard divisi/group yang sedang aktif — tampil di semua fase --}}
    {{-- Leaderboard + breakdown bonus Best Trick cuma buat Operator & Head Judge — rider/publik gak lihat ini. --}}
    @if(($isOperator || $isHeadJudge) && $scoringMode === 'live' && $judgeDivisionId)
        @if(($operatorLeaderboardSections ?? collect())->isNotEmpty())
            {{-- "Semua" group dipilih & divisi ini punya group — 1 group = 1 tabel,
                 ditampilkan berdampingan (kiri-kanan), maksimal 4 kolom per baris (mis.
                 8 group = 4 di atas, 4 di bawah; 6 group = 3+3). Flexbox + flex-grow
                 supaya baris terakhir yang gak penuh tetap melebar, gak nyisa kosong.
                 Tabelnya pakai container query (.score-scroll di app.css) buat ciutkan
                 kolom RUN otomatis kalau ruangnya sempit, alih-alih nampilin scrollbar. --}}
            @php
                $opSectionCount = $operatorLeaderboardSections->count();
                $opSectionRows  = (int) ceil($opSectionCount / 4);
                $opSectionCols  = $opSectionRows > 0 ? (int) ceil($opSectionCount / $opSectionRows) : 1;
                $opSectionGap   = 16;
            @endphp
            <div style="display:flex;flex-wrap:wrap;gap:{{ $opSectionGap }}px;align-items:flex-start;">
                @foreach($operatorLeaderboardSections as $section)
                    <div class="panel" style="overflow:hidden;flex:1 1 calc((100% - {{ ($opSectionCols - 1) * $opSectionGap }}px)/{{ $opSectionCols }});min-width:240px;">
                        <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                            <span class="kicker">📊 LEADERBOARD — {{ strtoupper($activeDivName ?? '') }} · KUALIFIKASI · {{ strtoupper($section['group']?->name ?? 'BELUM ADA GROUP') }}</span>
                        </div>
                        @if($section['leaderboard']->isEmpty())
                            <p class="mono dim" style="font-size:12px;padding:18px;">Belum ada skor masuk untuk group ini.</p>
                        @else
                            @include('livewire.partials.score-table', ['rows' => $section['leaderboard']])
                        @endif
                    </div>
                @endforeach
            </div>
        @else
        <div class="panel" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                <span class="kicker">📊 LEADERBOARD — {{ strtoupper($activeDivName ?? '') }}{{ ($activeDiv ?? null)?->live_stage === 'FINAL' ? ' · FINAL' : ' · KUALIFIKASI' }}</span>
            </div>
            @if(($operatorLeaderboard ?? collect())->isEmpty())
                <p class="mono dim" style="font-size:12px;padding:18px;">Belum ada skor masuk untuk divisi/group ini.</p>
            @else
                @include('livewire.partials.score-table', ['rows' => $operatorLeaderboard])
            @endif
        </div>
        @endif
    @endif

    @php
        $criteria  = $eventCriteria ?? collect();
        $critCount = $criteria->count() ?: 1;
        $totalLive = count($criteriaScores) > 0
            ? round(array_sum($criteriaScores) / $critCount, 1)
            : 0;
    @endphp

    @if($scoringMode === 'live')
        {{-- ── LIVE SCORING ── --}}

        @php $isRunning = $activeEvent?->live_phase === 'RUNNING'; @endphp

        @if($isOperator)
            {{-- Operator tidak menilai — panel setup di atas (setelah filter divisi/group) sudah cukup --}}
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
                    $lockedInitials = $lockedRider ? collect(explode(' ', $lockedRider->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('') : '?';
                @endphp
                <div class="flex" style="align-items:center;gap:12px;margin-bottom:20px;padding:12px;background:{{ $isRunning ? 'color-mix(in srgb,var(--red) 8%,transparent)' : 'var(--bg-2)' }};border:1px solid {{ $isRunning ? 'var(--red)' : 'var(--line)' }};border-radius:3px;">
                    <x-avatar :initials="$lockedInitials" :size="44" :ring="true" />
                    <div class="col">
                        <span class="display" style="font-size:22px;">{{ $lockedRider?->name ?? 'Menunggu...' }}</span>
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
                        <input type="number" min="0" max="10" step="0.1"
                            wire:model.live="bestTrickScore"
                            class="input-field" style="width:100%;font-size:28px;text-align:center;padding:14px;">
                    </div>
                @elseif($isRunning)
                    @forelse($criteria as $crit)
                        @php $val = $criteriaScores[$crit->key] ?? 90.0; @endphp
                        <div style="margin-bottom:18px;">
                            <div class="between" style="margin-bottom:7px;">
                                <span class="mono" style="font-size:11px;letter-spacing:0.12em;">{{ strtoupper($crit->name) }}</span>
                                <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                            </div>
                            <div class="flex gap-s" style="align-items:center;">
                                <input type="range" min="0" max="100" step="0.1"
                                    wire:model.live="criteriaScores.{{ $crit->key }}"
                                    style="flex:1;accent-color:var(--lime);" />
                                <input type="number" min="0" max="100" step="0.1"
                                    wire:model.live.debounce.400ms="criteriaScores.{{ $crit->key }}"
                                    class="input-field" style="width:64px;text-align:center;font-size:12px;padding:6px;" />
                            </div>
                        </div>
                    @empty
                        <p class="mono dim" style="font-size:12px;">Belum ada kriteria penilaian untuk event ini. Assign di Admin Panel.</p>
                    @endforelse
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
                    @else
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
                    @else
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
                @if(isset($otherJudgeScores) && $otherJudgeScores->count())
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

        {{-- ── HEAD JUDGE LIVE CONTROL — di bawah Context Scoring ── --}}
        @if($isHeadJudge)
        <div class="panel" style="border:2px solid var(--lime);padding:18px;">
            <div class="between" style="margin-bottom:14px;">
                <span class="kicker" style="color:var(--lime);">⚡ LIVE CONTROL</span>
                @if($activeEvent?->live_phase)
                    <span class="badge badge-red"><span class="live-dot" style="margin-right:5px;"></span>{{ $activeEvent->live_phase }}</span>
                @else
                    <span class="badge badge-out">STANDBY</span>
                @endif
            </div>

            @if(!$activeEvent?->live_phase || $activeEvent?->live_phase === 'NEXT')

                @if($activeEvent?->live_phase === 'NEXT')
                    @php $nextR = $activeEvent->live_rider_id ? \App\Models\Rider::find($activeEvent->live_rider_id) : null; @endphp
                    <div class="flex" style="align-items:center;gap:10px;margin-bottom:12px;padding:10px;background:var(--bg-2);border-radius:3px;">
                        <span style="font-size:13px;">→</span>
                        <span class="label" style="font-size:13px;">{{ $nextR?->name ?? '—' }}</span>
                        <span class="mono dim" style="font-size:10px;margin-left:auto;">NEXT UP · RUN {{ $activeEvent->live_run_number }}</span>
                    </div>
                @else
                    @php $readyRider = $liveRiderId ? $judgeRiders->find($liveRiderId) : null; @endphp
                    <p class="mono dim" style="font-size:11px;margin-bottom:12px;">
                        @if($readyRider)
                            Rider siap: <strong>{{ $readyRider->name }}</strong> · {{ ($isBestTrickPhase ?? false) ? 'Percobaan' : 'Run' }} {{ $liveRunNumber }}. Klik START RUN untuk mulai.
                        @else
                            Menunggu Operator memilih rider &amp; run.
                        @endif
                    </p>
                @endif

                <div class="flex gap-s" style="margin-bottom:8px;">
                    <button wire:click="startRun"
                        wire:key="start-run-{{ $liveRiderId }}-{{ $liveRunNumber }}-{{ ($riderAlreadyRan ?? false) ? 1 : 0 }}"
                        class="btn btn-lime"
                        @if(!$liveRiderId) disabled @endif
                        @if($riderAlreadyRan ?? false) wire:confirm="Rider ini sudah melakukan run {{ $liveRunNumber }} sebelumnya. Yakin ingin mengulang run?" @endif
                        style="flex:1;justify-content:center;font-size:13px;letter-spacing:0.1em;">
                        ▶ START RUN
                    </button>
                </div>
                @if(!$liveRiderId)
                    <p class="mono dim" style="font-size:10px;text-align:center;">Menunggu Operator memilih rider</p>
                @endif

            @elseif($activeEvent->live_phase === 'RUNNING')
                @php
                    $liveR        = $activeEvent->live_rider_id ? \App\Models\Rider::find($activeEvent->live_rider_id) : null;
                    $submittedIds = ($liveJudgeScores ?? collect())->where('status', 'DONE')->pluck('judge_user_id')->toArray();
                    $totalJudges  = ($assignedJudges ?? collect())->count();
                    $doneCount    = count($submittedIds);
                @endphp
                <div class="flex" style="align-items:center;gap:10px;margin-bottom:12px;padding:10px;background:var(--bg-2);border-radius:3px;">
                    <span class="live-dot"></span>
                    <span class="label" style="font-size:13px;">{{ $liveR?->name ?? 'Rider' }}</span>
                    <span class="mono dim" style="font-size:10px;">RUN {{ $activeEvent->live_run_number }}</span>
                    <span class="mono" style="font-size:10px;margin-left:auto;color:var(--lime);">{{ ($isBestTrickPhase ?? false) ? '1 TRICK' : $activeEvent->run_duration . 's' }}</span>
                </div>

                @include('livewire.partials.judge-status-list', ['assignedJudges' => $assignedJudges ?? collect(), 'liveJudgeScores' => $liveJudgeScores ?? collect(), 'isHeadJudge' => $isHeadJudge])

                @php
                    $pendingJudgeNames = ($assignedJudges ?? collect())
                        ->reject(fn ($aj) => in_array($aj->user_id, $submittedIds))
                        ->map(fn ($aj) => $aj->user?->name)
                        ->filter()
                        ->implode(', ');
                @endphp
                <div class="flex gap-s">
                    <button wire:click="revealScore"
                        wire:key="reveal-score-{{ $activeEvent->live_rider_id }}-{{ $activeEvent->live_run_number }}-{{ $doneCount }}-{{ $totalJudges }}"
                        @if($totalJudges && $doneCount < $totalJudges)
                            wire:confirm="Masih ada {{ $totalJudges - $doneCount }} judge yang belum menilai ({{ $pendingJudgeNames ?: 'tidak diketahui' }}). Yakin ingin reveal score sekarang?"
                        @endif
                        class="btn btn-lime" style="flex:1;justify-content:center;">✓ REVEAL SCORE</button>
                    <button wire:click="endSession" class="btn btn-ghost btn-sm">✗ BATAL</button>
                </div>

            @elseif($activeEvent->live_phase === 'REVEALING')
                <p class="mono" style="font-size:11px;margin-bottom:12px;color:var(--lime);">
                    Skor sedang ditampilkan di layar publik.
                </p>

                @include('livewire.partials.judge-status-list', ['assignedJudges' => $assignedJudges ?? collect(), 'liveJudgeScores' => $liveJudgeScores ?? collect(), 'isHeadJudge' => $isHeadJudge])

                <button wire:click="endSession" class="btn btn-ghost" style="width:100%;justify-content:center;">
                    ← KEMBALI KE LEADERBOARD
                </button>
            @endif
        </div>
        @endif

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
                        ? round(array_sum($criteriaScores) / $critCount, 1)
                        : 0;
                    $totalB  = count($criteriaScoresB) > 0
                        ? round(array_sum($criteriaScoresB) / $critCount, 1)
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
                            @php $val = $criteriaScores[$crit->key] ?? 90.0; @endphp
                            <div style="margin-bottom:12px;">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;">{{ strtoupper($crit->name) }}</span>
                                    <span class="display tnum" style="font-size:15px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                                </div>
                                <div class="flex gap-s" style="align-items:center;">
                                    <input type="range" min="0" max="100" step="0.1"
                                        wire:model.live="criteriaScores.{{ $crit->key }}"
                                        style="flex:1;accent-color:var(--lime);" />
                                    <input type="number" min="0" max="100" step="0.1"
                                        wire:model.live.debounce.400ms="criteriaScores.{{ $crit->key }}"
                                        class="input-field" style="width:56px;text-align:center;font-size:11px;padding:5px;" />
                                </div>
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
                            @php $val = $criteriaScoresB[$crit->key] ?? 90.0; @endphp
                            <div style="margin-bottom:12px;">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;letter-spacing:0.1em;">{{ strtoupper($crit->name) }}</span>
                                    <span class="display tnum" style="font-size:15px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                                </div>
                                <div class="flex gap-s" style="align-items:center;">
                                    <input type="range" min="0" max="100" step="0.1"
                                        wire:model.live="criteriaScoresB.{{ $crit->key }}"
                                        style="flex:1;accent-color:var(--lime);" />
                                    <input type="number" min="0" max="100" step="0.1"
                                        wire:model.live.debounce.400ms="criteriaScoresB.{{ $crit->key }}"
                                        class="input-field" style="width:56px;text-align:center;font-size:11px;padding:5px;" />
                                </div>
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

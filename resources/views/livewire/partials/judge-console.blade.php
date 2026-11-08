{{-- Layar Judge (acuan: design system FrameBladeScore / JudgeScoreSheet).
     Tanpa leaderboard dan tanpa nilai judge lain. Satu kartu per rider (heat Jamming) atau satu kartu (run biasa / best trick),
     input lewat keypad angka. Action Bar sticky: KIRIM SEMUA (heat Jamming) atau KIRIM NILAI. --}}
@php
    $jcPhase    = $activeEvent?->live_phase;                          // null | NEXT | RUNNING | REVEALING
    $isRunning  = $jcPhase === 'RUNNING';
    $jcIsJamDiv = (bool) ($jamDivision ?? null);
    $jcIsJamRun = $isRunning && (bool) $activeEvent->live_jam;
    $jcHeatRegs = $jamHeatRegs ?? collect();
    $jcBest     = (bool) ($isBestTrickPhase ?? false);
    $jcRunLabel = $jcBest ? 'Percobaan' : 'Run';
    $jcIsFinal  = ($activeDiv?->live_stage ?? null) === 'FINAL';
    $jcGroup    = ($judgeGroups ?? collect())->firstWhere('id', $judgeGroupId)?->name;

    // Variabel yang dipakai partial live-scoring
    $criteria  = $eventCriteria ?? collect();
    $critCount = $criteria->count() ?: 1;
    $totalLive = count($criteriaScores) > 0
        ? round(array_sum(array_filter($criteriaScores, 'is_numeric')) / $critCount, 1)
        : 0;

    // Timer
    $jcDuration  = $jcIsJamRun ? (int) $activeEvent->live_duration : (int) ($activeEvent?->run_duration ?? 0);
    if (!$isRunning && $jcIsJamDiv) { $jcDuration = (int) $jamDuration; }
    $jcStartedTs = $isRunning && $activeEvent->live_started_at ? $activeEvent->live_started_at->timestamp : 0;

    // Progres kirim
    $jcCriteriaKeys = $criteria->pluck('key');
    $jcSent  = 0; $jcTotal = 0; $jcReady = 0;
    if ($jcIsJamRun) {
        $jcTotal = $jcHeatRegs->count();
        foreach ($jcHeatRegs as $reg) {
            if (($jamSubmitted[$reg->id] ?? false)) { $jcSent++; continue; }
            if ($jcCriteriaKeys->isNotEmpty() && $jcCriteriaKeys->every(fn ($k) => is_numeric(($jamScores[$reg->id] ?? [])[$k] ?? null))) { $jcReady++; }
        }
    } elseif ($isRunning) {
        $jcTotal = 1;
        $jcSent  = $scoreSubmitted ? 1 : 0;
        $jcReady = (!$scoreSubmitted && ($jcBest ? is_numeric($bestTrickScore)
                    : ($jcCriteriaKeys->isNotEmpty() && $jcCriteriaKeys->every(fn ($k) => is_numeric($criteriaScores[$k] ?? null))))) ? 1 : 0;
    }

    if (!$judgeEventId)         { $jcStatus = 'Tidak ada event aktif.'; }
    elseif ($jcPhase === 'REVEALING') { $jcStatus = 'Skor sedang ditampilkan di layar publik.'; }
    elseif (!$isRunning)        { $jcStatus = 'Menunggu run dimulai.'; }
    elseif ($jcSent >= $jcTotal && $jcTotal > 0) { $jcStatus = 'Semua nilai sudah terkirim.'; }
    elseif ($jcReady === 0)     { $jcStatus = 'Isi nilai lalu tekan OK di keypad, baru kirim.'; }
    else                        { $jcStatus = $jcReady . ' nilai siap dikirim.'; }

    $jcAssignedMode = $judgeAssignment?->scoring_mode ?? 'BOTH';
    $jcShowKo       = in_array($jcAssignedMode, ['KNOCKOUT', 'BOTH']);
@endphp

@include('livewire.partials.console-styles')

<div class="oc" wire:key="judge-console">

    {{-- Top: nama judge, status, timer --}}
    <div class="oc-top" style="align-items:center;">
        <h2 class="display" style="font-size:32px;line-height:1;letter-spacing:.03em;">JUDGE · {{ strtoupper(auth()->user()->name) }}</h2>
        @if($isRunning)
            <span class="badge badge-red"><span class="live-dot" style="margin-right:5px;"></span>RUNNING</span>
        @elseif($jcPhase === 'REVEALING')
            <span class="badge badge-lime">SELESAI</span>
        @else
            <span class="badge badge-out">STANDBY</span>
        @endif
        @if($jcShowKo)
            <div class="oc-seg" role="group" aria-label="Mode scoring" style="min-width:220px;">
                <button type="button" wire:click="$set('scoringMode','live')" class="on" aria-pressed="true">★ LIVE</button>
                <button type="button" wire:click="$set('scoringMode','knockout')" aria-pressed="false">⚡ KNOCKOUT</button>
            </div>
        @endif
        @if($jcDuration > 0)
            <div style="margin-left:auto;--oc-timer:48px;--oc-timer-lh:44px;">
                @include('livewire.partials.run-timer', ['started' => $jcStartedTs, 'duration' => $jcDuration, 'running' => $isRunning])
            </div>
        @endif
    </div>

    {{-- Sub: konteks heat / run --}}
    <div class="between" style="padding:8px 0 12px;border-bottom:1px solid var(--line);gap:12px;flex-wrap:wrap;">
        <span class="oc-lbl">
            {{ $activeDivName ? $activeDivName : 'Menunggu Operator memilih divisi' }}
            @if($activeDiv) · {{ $jcIsFinal ? 'Final' . ($jcGroup && ($judgeGroups ?? collect())->count() > 1 ? ' · ' . preg_replace('/^Final\s+/i', '', $jcGroup) : '') : ($jcGroup ?: ($jcIsJamDiv ? 'Heat' : 'Kualifikasi')) }} · {{ $jcRunLabel }} {{ $liveRunNumber }}@endif
        </span>
        <span class="oc-lbl">Nilai tidak bisa dilihat judge lain</span>
    </div>

    <div style="flex:1;padding-top:16px;display:flex;flex-direction:column;gap:16px;min-width:0;">
        @if(!$judgeEventId)
            <div class="oc-card"><p class="mono" style="font-size:13px;color:var(--red);">⚠ Tidak ada active event.</p></div>

        @elseif($isRunning)
            @if($jcIsJamRun)
                @include('livewire.partials.jam-scoring', ['isRunning' => true, 'jamAllScores' => [], 'isHeadJudge' => false])
            @else
                @include('livewire.partials.live-scoring', ['hideInlineSubmit' => true])
            @endif

        @else
            {{-- Standby / reveal --}}
            <div class="oc-card" style="align-items:center;text-align:center;padding:40px 16px;">
                <span class="live-dot" style="width:14px;height:14px;opacity:.5;"></span>
                <span class="oc-lbl">{{ $jcPhase === 'REVEALING' ? 'Skor sedang tampil' : 'Menunggu run dimulai' }}</span>
                <p class="mono dim" style="font-size:13px;max-width:360px;">
                    {{ $jcPhase === 'REVEALING' ? 'Run ini sudah selesai. Tunggu run berikutnya dimulai.' : 'Form nilai muncul otomatis begitu Operator atau Head Judge memulai run.' }}
                </p>
                @if($jcIsJamDiv && $jcHeatRegs->isNotEmpty())
                    <div class="flex" style="flex-wrap:wrap;gap:8px;justify-content:center;margin-top:8px;">
                        @foreach($jcHeatRegs as $reg)
                            <span class="oc-chip"><x-avatar :initials="collect(preg_split('/\s+/', trim($reg->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$reg->photo_url" :size="32" />{{ $reg->name }}</span>
                        @endforeach
                    </div>
                @elseif(!$jcIsJamDiv && $liveRiderId)
                    @php $jcNext = ($judgeRiders ?? collect())->firstWhere('id', $liveRiderId); @endphp
                    @if($jcNext)<span class="oc-chip"><x-avatar :initials="collect(preg_split('/\s+/', trim($jcNext->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$jcNext->photo_url" :size="32" />{{ $jcNext->name }}</span>@endif
                @endif
            </div>
            @include('livewire.partials.judge-history')
        @endif
    </div>

    {{-- Action bar --}}
    <div class="oc-bar">
        <p class="mono" style="font-size:14px;">
            <b style="display:block;">{{ $isRunning ? $jcSent . ' dari ' . $jcTotal . ' terkirim' : 'Standby' }}</b>
            <span class="oc-lbl" style="text-transform:none;letter-spacing:.02em;font-size:13px;">{{ $jcStatus }}</span>
        </p>
        @if($isRunning)
            @if($jcIsJamRun)
                <button type="button" wire:key="jc-send-jam" wire:click="submitAllJamScores" wire:loading.attr="disabled" wire:target="submitAllJamScores"
                    class="oc-btn pri" @if($jcReady === 0) disabled @endif>KIRIM SEMUA</button>
            @elseif($jcBest)
                <button type="button" wire:key="jc-send-bt" wire:click="submitBestTrickScore" wire:loading.attr="disabled" wire:target="submitBestTrickScore"
                    class="oc-btn pri" @if($jcReady === 0) disabled @endif>KIRIM NILAI</button>
            @else
                <button type="button" wire:key="jc-send-score" wire:click="submitScore" wire:loading.attr="disabled" wire:target="submitScore"
                    class="oc-btn pri" @if($jcReady === 0) disabled @endif>KIRIM NILAI</button>
            @endif
        @endif
    </div>
    @error('judgeEventId') <p class="oc-err">{{ $message }}</p> @enderror
    @error('liveRiderId') <p class="oc-err">{{ $message }}</p> @enderror
</div>

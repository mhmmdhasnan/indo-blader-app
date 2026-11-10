{{-- Layar Head Judge (acuan: design system FrameBladeScore / HeadJudgeConsole).
     Dua kolom: kiri Status run (timer + progres tiap judge) dan Nilai saya; kanan Rangkuman nilai judge.
     Action Bar sticky: REVEAL SCORE (nonaktif sampai semua judge selesai; paksa = tahan 2 dtk) dan BATAL RUN (tahan 2 dtk).
     Memakai method Livewire yang sudah ada. Head Judge tetap bisa memulai run (cadangan kalau Operator tidak ada). --}}
@php
    $hjPhase     = $activeEvent?->live_phase;                     // null | NEXT | RUNNING | REVEALING
    $isRunning   = $hjPhase === 'RUNNING';
    $hjStep      = match ($hjPhase) { 'RUNNING' => 3, 'REVEALING' => 4, default => 1 };
    $hjIsJamDiv  = (bool) ($jamDivision ?? null);
    $hjIsJamRun  = $isRunning && (bool) $activeEvent->live_jam;
    $hjHeatRegs  = $jamHeatRegs ?? collect();
    $hjBestTrick = (bool) ($isBestTrickPhase ?? false) || (bool) ($activeDiv?->best_trick_active ?? false);
    $hjRunLabel  = $hjBestTrick ? 'PERCOBAAN' : 'RUN';
    // Warna per nomor run supaya Head Judge langsung tahu run berapa yang sedang disiapkan/berjalan.
    $hjRunColor  = [1 => '#5ec8ff', 2 => '#b78cff', 3 => '#ff7ac8'][(int) $liveRunNumber] ?? '#5ec8ff';
    $hjRunChip   = '<span class="mono" style="display:inline-block;background:' . $hjRunColor . ';color:#0a0a0b;font-weight:700;font-size:15px;letter-spacing:.1em;padding:3px 12px;">' . e($hjRunLabel . ' ' . (int) $liveRunNumber) . '</span>';
    $hjIsFinal   = ($activeDiv?->live_stage ?? null) === 'FINAL';

    // Variabel yang dipakai partial live-scoring (sebelumnya dihitung di judging-panel)
    $criteria  = $eventCriteria ?? collect();
    $critCount = $criteria->count() ?: 1;
    $totalLive = count($criteriaScores) > 0
        ? round(array_sum(array_filter($criteriaScores, 'is_numeric')) / $critCount, 1)
        : 0;

    // Durasi + waktu mulai untuk timer
    $hjDuration  = $hjIsJamRun ? (int) $activeEvent->live_duration : (int) ($activeEvent?->run_duration ?? 0);
    if (!$isRunning) { $hjDuration = $hjIsJamDiv ? (int) $jamDuration : (int) ($activeEvent?->run_duration ?? 0); }
    $hjStartedTs = $isRunning && $activeEvent->live_started_at ? $activeEvent->live_started_at->timestamp : 0;

    // Progres judge: tiap judge → [selesai, total]
    $hjJudges   = ($assignedJudges ?? collect());
    $hjProgress = [];
    if ($hjIsJamRun) {
        $hjTotal = $hjHeatRegs->count();
        foreach ($hjJudges as $aj) { $hjProgress[$aj->user_id] = [min(($jamProgress ?? [])[$aj->user_id] ?? 0, $hjTotal), $hjTotal]; }
    } else {
        $hjDoneIds = ($liveJudgeScores ?? collect())->where('status', 'DONE')->pluck('judge_user_id')->all();
        foreach ($hjJudges as $aj) { $hjProgress[$aj->user_id] = [in_array($aj->user_id, $hjDoneIds) ? 1 : 0, 1]; }
    }
    $hjDoneCount = collect($hjProgress)->filter(fn ($p) => $p[1] > 0 && $p[0] >= $p[1])->count();
    $hjPending   = $hjJudges->filter(fn ($aj) => ($hjProgress[$aj->user_id][0] ?? 0) < ($hjProgress[$aj->user_id][1] ?? 1));
    // Event tanpa judge ter-assign (Admin → Event → Judge): tidak ada yang bisa ditunggu, reveal tidak boleh terlihat terkunci.
    $hjNoJudges  = $hjJudges->isEmpty();
    $hjAllDone   = $hjNoJudges || $hjPending->isEmpty();
    $hjPendingTxt = $hjPending->map(function ($aj) use ($hjProgress, $hjIsJamRun) {
        $left = ($hjProgress[$aj->user_id][1] ?? 1) - ($hjProgress[$aj->user_id][0] ?? 0);
        return ($aj->user?->name ?? '—') . ($hjIsJamRun ? ' ' . $left . ' rider' : '');
    })->implode(', ');

    $hjReadyRider = (!$hjIsJamDiv && $liveRiderId) ? ($judgeRiders ?? collect())->firstWhere('id', $liveRiderId) : null;
    $hjCanStart = false;
    if (!$judgeEventId)                { $hjStatus = 'Tidak ada event aktif.'; }
    elseif ($hjPhase === 'REVEALING')  { $hjStatus = 'Skor sedang tampil di layar publik.'; }
    elseif (!$isRunning && ($liveSetupProblem ?? null)) { $hjStatus = $liveSetupProblem; }
    elseif ($isRunning && $hjNoJudges) { $hjStatus = 'Belum ada judge yang di-assign ke event ini (Admin → Event). Reveal tetap bisa.'; }
    elseif ($isRunning)                { $hjStatus = $hjAllDone ? 'Semua judge sudah selesai. Reveal sekarang.' : 'Menunggu ' . $hjPending->count() . ' judge lagi: ' . $hjPendingTxt; }
    elseif (!$judgeDivisionId)         { $hjStatus = 'Menunggu Operator memilih divisi.'; }
    elseif ($hjIsJamDiv && $hjHeatRegs->isEmpty()) { $hjStatus = 'Menunggu Operator memilih heat.'; }
    elseif ($hjIsJamDiv && ($judgeGroups ?? collect())->isNotEmpty() && !($judgeGroups ?? collect())->contains('id', $judgeGroupId)) { $hjStatus = 'Menunggu Operator memilih heat.'; }
    elseif ($hjIsJamDiv)               { $hjCanStart = true; $hjStatus = 'Siap: ' . $hjHeatRegs->count() . ' rider, ' . $hjDuration . ' dtk. ' . $hjRunLabel . ' ' . $liveRunNumber . ' (diatur Operator).'; }
    elseif (!$liveRiderId)             { $hjStatus = 'Menunggu Operator memilih rider & run.'; }
    else                               { $hjCanStart = true; $hjStatus = 'Siap: ' . ($hjReadyRider?->name ?? 'rider') . ' · ' . $hjRunLabel . ' ' . $liveRunNumber; }

    if (!$isRunning && ($liveSetupProblem ?? null)) { $hjCanStart = false; }

    // Mode scoring (Live / Knockout) hanya kalau Head Judge ditugaskan di keduanya
    $hjAssignedMode = $judgeAssignment?->scoring_mode ?? 'BOTH';
    $hjShowKo       = in_array($hjAssignedMode, ['KNOCKOUT', 'BOTH']);
    $hjDivName      = $activeDiv?->name;
    $hjGroupName    = ($judgeGroups ?? collect())->firstWhere('id', $judgeGroupId)?->name;
@endphp

@include('livewire.partials.console-styles')

<div class="oc" wire:key="head-judge-console">

    {{-- Top: event, divisi (mengikuti Operator), fase, status --}}
    <div class="oc-top">
        <div class="col" style="gap:2px;">
            <span class="oc-lbl">Event</span>
            @if($judgeEventId)
                <span class="mono" style="font-size:13px;color:var(--lime);">{{ strtoupper($events->firstWhere('id', $judgeEventId)?->title ?? '') }}</span>
            @else
                <span class="mono" style="font-size:13px;color:var(--red);">⚠ Tidak ada active event</span>
            @endif
        </div>
        <div class="col" style="gap:2px;">
            <span class="oc-lbl">Divisi · mengikuti Operator</span>
            <span class="mono" style="font-size:13px;">{{ $hjDivName ? strtoupper($hjDivName) . ($hjIsFinal ? ' · FINAL' . ($hjGroupName ? ' · ' . strtoupper($hjGroupName) : '') : ($hjGroupName ? ' · ' . strtoupper($hjGroupName) : '')) : 'Semua divisi' }}</span>
        </div>
        <div class="oc-steps" aria-label="Fase">
            @foreach([1 => '01 PERSIAPAN', 2 => '02 RUN', 3 => '03 PENILAIAN', 4 => '04 REVEAL'] as $n => $name)
                <div class="oc-st {{ $hjStep === $n ? 'on' : ($hjStep > $n ? 'done' : '') }}">{{ $name }}</div>
            @endforeach
        </div>
        @if($isRunning && $hjAllDone && !$hjNoJudges)
            <span class="badge badge-lime">SEMUA SELESAI</span>
        @elseif($isRunning)
            <span class="badge badge-red"><span class="live-dot" style="margin-right:5px;"></span>MENUNGGU JUDGE</span>
        @elseif($hjPhase === 'REVEALING')
            <span class="badge badge-lime">SELESAI</span>
        @elseif($hjPhase === 'NEXT')
            <span class="badge badge-out">PREVIEW</span>
        @else
            <span class="badge badge-out">STANDBY</span>
        @endif
        @if($hjShowKo)
            <div class="oc-seg" role="group" aria-label="Mode scoring" style="min-width:220px;">
                <button type="button" wire:click="$set('scoringMode','live')" class="on" aria-pressed="true">★ LIVE</button>
                <button type="button" wire:click="$set('scoringMode','knockout')" aria-pressed="false">⚡ KNOCKOUT</button>
            </div>
        @endif
    </div>

    <div class="oc-hj">

        {{-- ── KIRI: Status run + Nilai saya ── --}}
        <div class="col" style="gap:16px;min-width:0;">

            <section class="oc-card" style="border-top:6px solid {{ $hjRunColor }};">
                <div class="between">
                    <span class="oc-lbl" style="display:flex;align-items:center;gap:10px;">Status run · {!! $hjRunChip !!}{{ $hjIsJamDiv ? ' · ' . $hjHeatRegs->count() . ' rider' : '' }}</span>
                    @if($isRunning)<span class="badge badge-red">RUNNING</span>@elseif($hjPhase === 'REVEALING')<span class="badge badge-lime">REVEAL</span>@else<span class="badge badge-out">STANDBY</span>@endif
                </div>

                <div style="display:grid;grid-template-columns:auto minmax(0,1fr);gap:24px;align-items:center;">
                    <div>
                        @include('livewire.partials.run-timer', ['started' => $hjStartedTs, 'duration' => $hjDuration, 'running' => $isRunning])
                        @if($hjDuration <= 0)
                            <span class="display oc-timer dim">—</span>
                        @endif
                    </div>

                    <div class="col" style="gap:8px;min-width:0;">
                        <span class="oc-lbl">
                            @if($isRunning)
                                Progres judge · {{ $hjDoneCount }} dari {{ $hjJudges->count() }} selesai
                            @else
                                Judge bertugas · {{ $hjJudges->count() }}
                            @endif
                        </span>
                        @forelse($hjJudges as $aj)
                            @php
                                [$d, $tot] = $hjProgress[$aj->user_id] ?? [0, 1];
                                $fin   = $tot > 0 && $d >= $tot;
                                $me    = $aj->user_id === auth()->id();
                                $show  = $isRunning;
                                $ajScore = (!$hjIsJamRun) ? ($liveJudgeScores ?? collect())->firstWhere('judge_user_id', $aj->user_id) : null;
                            @endphp
                            <div class="oc-jr">
                                <b style="font-size:15px;">{{ $aj->user?->name ?? '—' }}{{ $me ? ' (kamu)' : '' }}</b>
                                <div class="oc-segs" aria-hidden="true">
                                    @for($i = 0; $i < max(1, $tot); $i++)<i class="{{ $show && $i < $d ? 'f' : '' }}"></i>@endfor
                                </div>
                                <span class="mono" style="font-weight:700;text-align:right;">{{ $show ? $d . '/' . $tot : '—' }}</span>
                                @if($show)
                                    <span class="oc-tg {{ $fin ? 'ok' : 'wt' }}">{{ $fin ? 'SELESAI' : 'MENUNGGU' }}</span>
                                @else
                                    <span class="oc-tg" style="color:var(--ink-3,#a3a39b);border-color:var(--ink-3,#8a8a90);">{{ $hjPhase === 'REVEALING' ? 'REVEAL' : 'SIAGA' }}</span>
                                @endif
                                @if($fin && !$hjIsJamRun && $ajScore && $isRunning)
                                    {{-- Head Judge boleh membuka ulang skor judge yang sudah submit --}}
                                    <span style="grid-column:1/-1;display:flex;align-items:center;gap:12px;">
                                        <span class="mono tnum" style="color:var(--lime);">{{ number_format($ajScore->total, 1) }}</span>
                                        <button type="button" wire:key="hj-reopen-{{ $ajScore->id }}" wire:click="reopenJudgeScore({{ $ajScore->id }})"
                                            wire:confirm="Buka ulang skor {{ $aj->user?->name ?? 'judge ini' }} untuk direvisi?"
                                            class="oc-ghost" style="min-height:36px;width:auto;font-size:12px;">✎ BUKA ULANG</button>
                                    </span>
                                @endif
                            </div>
                        @empty
                            <p class="mono dim" style="font-size:12px;">Belum ada judge yang ditugaskan di event ini.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="col" style="gap:12px;min-width:0;">
                <span class="oc-lbl">Nilai saya · {{ auth()->user()->name }}</span>
                @include('livewire.partials.live-scoring')
            </section>
        </div>

        {{-- ── KANAN: Rangkuman nilai judge ── --}}
        <section class="oc-card" style="min-width:0;">
            <div class="between">
                <span class="oc-lbl">Rangkuman nilai judge · {{ $summaryHeat === 'all' ? 'semua heat' : (($summaryHeat !== '' || ($activeEvent?->active_group_id ?: ($judgeGroupId ?? 0))) ? 'per heat' : 'semua heat') }}</span>
                <span class="oc-lbl">{{ ($headJudgeSummary ?? collect())->count() }} entri</span>
            </div>
            @if(($summaryHeatOptions ?? collect())->isNotEmpty())
                <div style="margin-top:8px;">
                    <select wire:model.live="summaryHeat" class="oc-sel" style="width:100%;padding:8px 10px;background:var(--bg-2);color:var(--ink);border:2px solid var(--line-strong);font-family:var(--font-mono);font-size:13px;">
                        <option value="">Ikuti heat Operator</option>
                        <option value="all">Semua heat</option>
                        @foreach($summaryHeatOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @php $hjSummary = ($headJudgeSummary ?? collect()); $hjEditCriteria = $hjEditCriteria ?? collect(); $hjHeats = $hjSummary->groupBy(fn ($r) => $r['heat'] ?? ''); @endphp
            @forelse($hjHeats as $heatName => $heatRows)
                @if($heatName !== '')
                    <div class="between" style="margin-top:14px;padding:6px 10px;background:var(--bg-2);border-left:4px solid var(--lime);">
                        <span class="oc-lbl" style="color:var(--ink);font-weight:700;">{{ strtoupper($heatName) }}</span>
                        <span class="oc-lbl">{{ $heatRows->pluck('rider.id')->unique()->count() }} rider</span>
                    </div>
                @endif
                @php
                    // Skor kembar antar rider (heat + mode + run yang sama) → peringatan untuk Head Judge.
                    $tieKey   = fn ($r) => ($r['mode'] ?? '') . '|' . ($r['run'] ?? '') . '|' . number_format($r['avg'], 1);
                    $tieCount = $heatRows->groupBy($tieKey)->map(fn ($g) => $g->pluck('rider.id')->unique()->count());
                    $tieGroups = $heatRows->groupBy($tieKey)->filter(fn ($g) => $g->pluck('rider.id')->unique()->count() > 1);
                @endphp
                @if($tieGroups->isNotEmpty())
                    <div style="margin-top:8px;padding:8px 10px;border:2px solid #ffb020;background:color-mix(in srgb,#ffb020 12%,transparent);">
                        <span class="oc-lbl" style="color:#ffb020;font-weight:700;">⚠ SKOR SAMA</span>
                        @foreach($tieGroups as $tg)
                            <div class="mono" style="font-size:12px;margin-top:4px;">
                                {{ number_format($tg->first()['avg'], 1) }} — {{ $tg->map(fn ($r) => $r['rider']?->name ?? '—')->unique()->implode(' & ') }}
                                ({{ $tg->first()['mode'] === 'BEST_TRICK' ? 'percobaan ' : 'run ' }}{{ $tg->first()['run'] }})
                            </div>
                        @endforeach
                    </div>
                @endif
                @foreach($heatRows as $row)
                    @php $isTie = ($tieCount[$tieKey($row)] ?? 1) > 1; @endphp
                    <div style="padding:10px 0;border-top:1px solid var(--line);{{ $isTie ? 'border-left:4px solid #ffb020;padding-left:10px;' : '' }}">
                        <div class="between" style="gap:12px;">
                            <div class="col" style="gap:2px;min-width:0;">
                                <span style="font-weight:700;font-size:15px;">{{ $row['rider']?->name ?? '—' }}</span>
                                <span class="oc-lbl" style="font-size:12px;">
                                    {{ $row['mode'] === 'BEST_TRICK' ? 'Best trick · percobaan ' . $row['run'] : 'Run ' . $row['run'] . ' · ' . ($row['stage'] === 'FINAL' ? 'Final' : 'Kualifikasi') }}
                                </span>
                            </div>
                            <span class="display tnum" style="font-size:40px;line-height:40px;color:{{ $isTie ? '#ffb020' : 'var(--lime)' }};">{{ number_format($row['avg'], 1) }}@if($isTie)<span class="mono" style="display:block;font-size:11px;line-height:12px;letter-spacing:.1em;text-align:right;">SAMA</span>@endif</span>
                        </div>
                        <div class="flex" style="flex-wrap:wrap;gap:6px;margin-top:6px;">
                            @foreach($row['judges'] as $j)
                                @php $jc = ['#5ec8ff','#ffb020','#ff7ac8','#b78cff','#4be3a0','#ff6b5e','#e6e84a','#7fa8ff'][((int) ($j['user_id'] ?? 0)) % 8]; @endphp
                                <span class="mono" style="font-size:13px;padding:2px 8px;background:var(--bg-2);border-left:4px solid {{ $jc }};color:{{ $jc }};">{{ $j['name'] }} · {{ number_format($j['total'], 1) }}</span>
                            @endforeach
                        </div>
                        {{-- Koreksi skor judge: edit langsung oleh Head Judge, atau minta judge yang bersangkutan merevisi. --}}
                        @foreach($row['judges'] as $j)
                            @php $editing = (int) $hjEditScoreId === (int) $j['id']; @endphp
                            <div wire:key="hj-fix-{{ $j['id'] }}" style="margin-top:8px;">
                                <div class="between" style="gap:8px;flex-wrap:wrap;">
                                    <span class="mono dim" style="font-size:12px;">{{ $j['name'] }}</span>
                                    @unless($editing)
                                        <span class="flex" style="gap:6px;">
                                            <button type="button" wire:click="openHeadJudgeEdit({{ $j['id'] }})" class="oc-ghost" style="min-height:32px;width:auto;font-size:12px;">✎ EDIT LANGSUNG</button>
                                            <button type="button" wire:click="reopenJudgeScore({{ $j['id'] }})"
                                                wire:confirm="Minta {{ $j['name'] }} merevisi skornya? Skor dibuka ulang dan judge harus submit lagi."
                                                class="oc-ghost" style="min-height:32px;width:auto;font-size:12px;">↩ MINTA REVISI</button>
                                        </span>
                                    @endunless
                                </div>
                                @if($editing)
                                    <div style="margin-top:6px;padding:10px;border:2px solid var(--lime);display:flex;flex-direction:column;gap:8px;">
                                        @if($row['mode'] === 'BEST_TRICK')
                                            <label class="oc-lbl">Nilai (0–10)
                                                <input type="number" step="0.1" min="0" max="10" wire:model="hjEditValues.total" class="input-field" style="width:100%;min-height:44px;">
                                            </label>
                                        @else
                                            @foreach($hjEditCriteria as $crit)
                                                <label class="oc-lbl">{{ $crit->name }} (0–100)
                                                    <input type="number" step="0.1" min="0" max="100" wire:model="hjEditValues.{{ $crit->key }}" class="input-field" style="width:100%;min-height:44px;">
                                                </label>
                                            @endforeach
                                        @endif
                                        @if($hjEditError)<p class="oc-err" style="margin:0;">{{ $hjEditError }}</p>@endif
                                        <div class="flex" style="gap:8px;">
                                            <button type="button" wire:click="saveHeadJudgeEdit" class="oc-btn pri" style="min-height:44px;">SIMPAN</button>
                                            <button type="button" wire:click="cancelHeadJudgeEdit" class="oc-btn out" style="min-height:44px;">BATAL</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @empty
                <p class="mono dim" style="font-size:12px;">Belum ada nilai masuk untuk heat/divisi ini.</p>
            @endforelse
            <p class="oc-lbl" style="margin-top:auto;font-size:12px;">Rata sementara, final setelah semua judge kirim</p>
        </section>
    </div>

    {{-- ── ACTION BAR ── --}}
    <div class="oc-bar">
        <p class="mono" style="font-size:14px;">
            <b style="display:block;">{!! $hjRunChip !!} {{ $hjStatus }}</b>
            @if($isRunning && !$hjAllDone)<span class="oc-lbl">Reveal sebelum semua judge selesai akan meminta konfirmasi</span>@endif
        </p>

        @if($hjPhase === 'REVEALING')
            <button type="button" wire:click="endSession" wire:key="hj-back" class="oc-btn pri">← KEMBALI KE LEADERBOARD</button>

        @elseif($isRunning)
            <div x-data="{ ask: false }" style="display:contents;">
                <button type="button" x-show="!ask" @click="ask = true" wire:key="hj-cancel" class="oc-btn out">BATAL {{ $hjIsJamRun ? 'HEAT' : 'RUN' }}</button>
                <span x-show="ask" x-cloak class="oc-ask">
                    <span class="mono" style="font-size:13px;">Nilai yang sudah masuk diapakan?</span>
                    <button type="button" wire:click="endSession(true)" wire:key="hj-cancel-del" class="oc-btn dng"
                        wire:confirm="{{ $hjIsJamRun ? 'Hapus semua skor heat ini dan batalkan?' : 'Hapus skor run ini dan batalkan?' }}">🗑 HAPUS NILAI</button>
                    <button type="button" wire:click="endSession(false)" wire:key="hj-cancel-keep" class="oc-btn sec">💾 SIMPAN NILAI</button>
                    <button type="button" @click="ask = false" class="oc-btn out">KEMBALI</button>
                </span>
            </div>
            {{-- Tanpa wire:confirm: cek "judge belum selesai" dilakukan server saat klik (revealScore), peringatannya tampil inline di bawah. --}}
            <button type="button" wire:click="revealScore" wire:key="hj-reveal" class="oc-btn pri {{ $hjAllDone ? '' : 'wait' }}">✓ REVEAL SCORE</button>

        @else
            @if($hjPhase === 'NEXT')
                <button type="button" wire:click="endSession" wire:key="hj-cancel-preview" class="oc-btn sec">✗ BATAL PREVIEW</button>
            @endif
            @if($hjIsJamDiv)
                <button type="button" wire:click="startJamHeat" wire:key="hj-start-heat-{{ ($heatAlreadyRan ?? false) ? 1 : 0 }}-{{ $liveRunNumber }}" class="oc-btn pri" @if(!$hjCanStart) disabled @endif
                    @if($heatAlreadyRan ?? false) wire:confirm="Heat ini sudah punya nilai untuk RUN {{ $liveRunNumber }}. Mulai lagi? Nilai yang sudah ada tidak otomatis terhapus." @endif>▶ MULAI HEAT</button>
            @else
                <button type="button" wire:click="startRun"
                    wire:key="hj-start-{{ $liveRiderId }}-{{ $liveRunNumber }}-{{ ($riderAlreadyRan ?? false) ? 1 : 0 }}"
                    class="oc-btn pri" @if(!$hjCanStart) disabled @endif
                    @if($riderAlreadyRan ?? false) wire:confirm="Rider ini sudah melakukan {{ strtolower($hjRunLabel) }} {{ $liveRunNumber }} sebelumnya. Yakin ingin mengulang?" @endif>▶ MULAI {{ $hjRunLabel }}</button>
            @endif
        @endif
    </div>
    @if($revealWarning ?? null)
        <div class="oc-card" style="border-color:#ffb020;margin-top:12px;flex-direction:row;align-items:center;flex-wrap:wrap;gap:12px;" role="alert">
            <p class="mono" style="flex:1;min-width:220px;font-size:14px;margin:0;">Masih ada judge yang belum selesai: <b>{{ $revealWarning }}</b>. Reveal tetap sekarang?</p>
            <button type="button" wire:click="cancelRevealWarning" class="oc-btn sec">TUNGGU</button>
            <button type="button" wire:click="revealScore(true)" class="oc-btn sec" style="border-color:#ffb020;color:#ffb020;">REVEAL TETAP</button>
        </div>
    @endif
    @error('liveSetup') <p class="oc-err">{{ $message }}</p> @enderror
    @error('judgeGroupId') <p class="oc-err">{{ $message }}</p> @enderror
    @error('judgeDivisionId') <p class="oc-err">{{ $message }}</p> @enderror
    @error('liveRiderId') <p class="oc-err">{{ $message }}</p> @enderror
</div>

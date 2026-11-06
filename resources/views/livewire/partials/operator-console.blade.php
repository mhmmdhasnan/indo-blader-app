{{-- Layar Operator (acuan: design system FrameBladeScore / OperatorConsole).
     Tiga kolom: Antrean · Panggung · Papan skor & Pengumuman, plus Action Bar sticky di bawah
     dengan SATU aksi utama per fase. Semua aksi memakai method Livewire yang sudah ada.
     Expects (scope induk): $events, $judgeEventId, $judgeDivisionId, $judgeDivisions, $judgeGroups,
     $judgeGroupId, $judgeRiders, $liveRiderId, $liveRunNumber, $activeEvent, $activeDiv, $activeDivName,
     $jamDivision, $jamHeatRegs, $jamProgress, $jamDuration, $assignedJudges, $liveJudgeScores,
     $isBestTrickPhase, $riderAlreadyRan, $isHeadJudge --}}
@php
    $ocPhase    = $activeEvent?->live_phase;                       // null | NEXT | RUNNING | REVEALING
    $ocStep     = match ($ocPhase) { 'RUNNING' => 2, 'REVEALING' => 4, default => 1 };
    $ocIsJam    = (bool) ($jamDivision ?? null);
    $ocGroups   = $judgeGroups ?? collect();
    $ocHeatRegs = $jamHeatRegs ?? collect();
    $ocIsFinal  = ($activeDiv?->live_stage ?? null) === 'FINAL';
    $ocBestTrick = (bool) ($isBestTrickPhase ?? false) || (bool) ($activeDiv?->best_trick_active ?? false);
    $ocRunLabel = $ocBestTrick ? 'PERCOBAAN' : 'RUN';
    $ocReadyRider = (!$ocIsJam && $liveRiderId) ? ($judgeRiders ?? collect())->firstWhere('id', $liveRiderId) : null;
    $ocRunning  = $ocPhase === 'RUNNING';
    $ocDuration = $ocIsJam
        ? (int) ($ocRunning ? ($activeEvent->live_duration ?? $jamDuration) : $jamDuration)
        : (int) ($activeEvent?->run_duration ?? 0);
    $ocStartedTs = $ocRunning && $activeEvent->live_started_at ? $activeEvent->live_started_at->timestamp : 0;

    // Progres judge (untuk kalimat status di Action Bar)
    $ocJudgesAll = ($assignedJudges ?? collect());
    if ($ocIsJam) {
        $ocHeatSize = $ocHeatRegs->count();
        $ocDone = $ocJudgesAll->filter(fn ($aj) => $ocHeatSize > 0 && (($jamProgress ?? [])[$aj->user_id] ?? 0) >= $ocHeatSize)->count();
    } else {
        $ocDone = ($liveJudgeScores ?? collect())->where('status', 'DONE')->count();
    }
    $ocJudgeTotal = $ocJudgesAll->count();

    // Divisi yang sudah punya finalis → tombol pengumuman (logika sama seperti sebelumnya)
    $ocHasFinalists = $activeDiv ? \App\Models\DivisionFinalist::where('event_division_id', $activeDiv->id)->exists() : false;

    // Kalimat status + alasan tombol utama nonaktif
    $ocCanStart = false;
    if (!$judgeEventId)            { $ocStatus = 'Tidak ada event aktif.'; }
    elseif (!$judgeDivisionId)     { $ocStatus = 'Pilih divisi di kolom Antrean.'; }
    elseif ($ocPhase === 'REVEALING') { $ocStatus = 'Skor sedang tampil di layar publik.'; }
    elseif ($ocRunning)            { $ocStatus = 'Run berjalan: ' . $ocDone . '/' . $ocJudgeTotal . ' judge selesai. Menunggu Head Judge reveal.'; }
    elseif ($ocIsJam && $ocHeatRegs->isEmpty()) { $ocStatus = $ocIsFinal ? 'Belum ada finalis. Pilih finalis dulu di Admin.' : 'Pilih heat di kolom Antrean.'; }
    elseif ($ocIsJam)              { $ocCanStart = true; $ocStatus = 'Siap: ' . ($ocIsFinal ? 'Final' : ($ocGroups->firstWhere('id', $judgeGroupId)?->name ?? 'Heat')) . ', ' . $ocHeatRegs->count() . ' rider, ' . $ocDuration . ' dtk'; }
    elseif (!$liveRiderId)         { $ocStatus = 'Pilih rider di kolom Antrean.'; }
    else                           { $ocCanStart = true; $ocStatus = 'Siap: ' . ($ocReadyRider?->name ?? 'rider') . ' · ' . $ocRunLabel . ' ' . $liveRunNumber; }
@endphp

@include('livewire.partials.console-styles')

<div class="oc" wire:key="operator-console">

    {{-- Top: event, fase, status --}}
    <div class="oc-top">
        <div class="col" style="gap:2px;">
            <span class="oc-lbl">Event</span>
            @if($judgeEventId)
                <span class="mono" style="font-size:13px;color:var(--lime);">{{ strtoupper($events->firstWhere('id', $judgeEventId)?->title ?? '') }}</span>
            @else
                <span class="mono" style="font-size:13px;color:var(--red);">⚠ Tidak ada active event</span>
            @endif
        </div>
        <div class="oc-steps" aria-label="Fase">
            @foreach([1 => '01 PERSIAPAN', 2 => '02 RUN', 3 => '03 PENILAIAN', 4 => '04 REVEAL'] as $n => $name)
                <div class="oc-st {{ $ocStep === $n ? 'on' : ($ocStep > $n ? 'done' : '') }}">{{ $name }}</div>
            @endforeach
        </div>
        @if($ocRunning)
            <span class="badge badge-red"><span class="live-dot" style="margin-right:5px;"></span>RUNNING</span>
        @elseif($ocPhase === 'REVEALING')
            <span class="badge badge-lime">SELESAI</span>
        @elseif($ocPhase === 'NEXT')
            <span class="badge badge-out">PREVIEW</span>
        @else
            <span class="badge badge-out">STANDBY</span>
        @endif
    </div>

    <div class="oc-main">

        {{-- ── KOLOM 1: ANTREAN ── --}}
        <section class="oc-card">
            <span class="oc-lbl">Antrean {{ $ocIsJam ? 'heat' : 'rider' }}</span>

            <div class="col" style="gap:4px;">
                <label class="oc-lbl" for="oc-div">Divisi</label>
                <select id="oc-div" wire:model.live="judgeDivisionId" class="input-field oc-sel">
                    <option value="0">— pilih divisi —</option>
                    @foreach($judgeDivisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }} · {{ $div->live_stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI' }}</option>
                    @endforeach
                </select>
            </div>

            @if($judgeDivisionId && !$ocIsFinal && $ocGroups->count() && !$ocIsJam)
                <div class="col" style="gap:4px;">
                    <label class="oc-lbl" for="oc-grp">Group</label>
                    <select id="oc-grp" wire:model.live="judgeGroupId" class="input-field oc-sel">
                        <option value="0">Semua group</option>
                        @foreach($ocGroups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(!$judgeDivisionId)
                <p class="mono dim" style="font-size:12px;">Pilih divisi untuk melihat antrean.</p>
            @elseif($ocIsJam)
                @if($ocIsFinal)
                    <button class="oc-row on" type="button"><b>FINAL<small class="mono dim" style="display:block;font-size:12px;font-weight:400;">{{ $ocHeatRegs->count() }} finalis · {{ $ocDuration }} dtk</small></b><span class="oc-tag go">SIAP</span></button>
                @elseif($ocGroups->isEmpty())
                    <p class="mono" style="font-size:12px;color:var(--red);">Divisi ini belum punya group/heat. Buat di Admin → Kelola Group.</p>
                @else
                    @foreach($ocGroups as $g)
                        @php $sel = (int) $judgeGroupId === (int) $g->id; @endphp
                        <button type="button" wire:click="$set('judgeGroupId', {{ $g->id }})" class="oc-row {{ $sel ? 'on' : '' }}" aria-pressed="{{ $sel ? 'true' : 'false' }}">
                            <b>{{ strtoupper($g->name) }}@if($sel)<small class="mono dim" style="display:block;font-size:12px;font-weight:400;">{{ $ocHeatRegs->count() }} rider · {{ $ocDuration }} dtk</small>@endif</b>
                            <span class="oc-tag {{ $sel ? 'go' : '' }}">{{ $sel ? 'SIAP' : 'MENUNGGU' }}</span>
                        </button>
                    @endforeach
                @endif
            @else
                @forelse(($judgeRiders ?? collect()) as $r)
                    @php $sel = (int) $liveRiderId === (int) $r->id; @endphp
                    <button type="button" wire:click="$set('liveRiderId', {{ $r->id }})" class="oc-row {{ $sel ? 'on' : '' }}" aria-pressed="{{ $sel ? 'true' : 'false' }}">
                        <x-avatar :initials="collect(preg_split('/\s+/', trim($r->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$r->photo_url" :size="36" :ring="$sel" />
                        <b>{{ $r->name }}</b>
                        <span class="oc-tag {{ $sel ? 'go' : '' }}">{{ $sel ? 'SIAP' : 'MENUNGGU' }}</span>
                    </button>
                @empty
                    <p class="mono" style="font-size:12px;color:var(--red);">
                        {{ $ocIsFinal ? 'Belum ada finalis. Pilih finalis dulu di Admin → Events → Divisi.' : 'Belum ada peserta approved untuk divisi/group ini.' }}
                    </p>
                @endforelse
            @endif
            @error('liveRiderId') <p class="oc-err">{{ $message }}</p> @enderror
        </section>

        {{-- ── KOLOM 2: PANGGUNG ── --}}
        <section class="oc-card">
            <span class="oc-lbl">Panggung</span>

            @if($judgeDivisionId)
                <h2 class="display oc-title">
                    @if($ocIsJam)
                        JAMMING · {{ $ocIsFinal ? 'FINAL' : strtoupper($ocGroups->firstWhere('id', $judgeGroupId)?->name ?? 'PILIH HEAT') }}
                    @else
                        {{ strtoupper($ocReadyRider?->name ?? ($activeDivName ?? '—')) }}
                    @endif
                </h2>
                <span class="mono dim" style="font-size:12px;">{{ strtoupper($activeDivName ?? '') }}{{ $ocBestTrick ? ' · BEST TRICK' : '' }}</span>
            @else
                <h2 class="display oc-title dim">PILIH DIVISI</h2>
            @endif

            @if($ocIsJam && $ocHeatRegs->isNotEmpty())
                <div class="flex" style="flex-wrap:wrap;gap:8px;">
                    @foreach($ocHeatRegs as $reg)<span class="oc-chip"><x-avatar :initials="collect(preg_split('/\s+/', trim($reg->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$reg->photo_url" :size="32" />{{ $reg->name }}</span>@endforeach
                </div>
            @endif

            {{-- Run / percobaan: hanya bisa diubah sebelum run mulai --}}
            <div class="col" style="gap:4px;">
                <span class="oc-lbl">{{ $ocBestTrick ? 'Percobaan' : 'Run' }}</span>
                @php $ocMaxRun = $ocIsJam ? 2 : 3; $ocLocked = $ocRunning || $ocPhase === 'REVEALING'; @endphp
                <div class="oc-seg" role="group" aria-label="Nomor run">
                    @for($i = 1; $i <= $ocMaxRun; $i++)
                        <button type="button" wire:click="$set('liveRunNumber', {{ $i }})" class="{{ (int) $liveRunNumber === $i ? 'on' : '' }}"
                            aria-pressed="{{ (int) $liveRunNumber === $i ? 'true' : 'false' }}" @if($ocLocked) disabled @endif>{{ $ocRunLabel }} {{ $i }}</button>
                    @endfor
                </div>
            </div>

            {{-- Timer --}}
            @if($ocIsJam)
                <div class="col" style="gap:4px;">
                    <span class="oc-lbl">Durasi timer (detik)</span>
                    <div class="flex" style="align-items:center;gap:12px;">
                        <input type="number" wire:model.live.debounce.500ms="jamDuration" min="10" max="3600" step="10" class="input-field" style="width:140px;min-height:48px;" @if($ocRunning) disabled @endif>
                        <span class="mono dim" style="font-size:12px;">{{ floor((int) $jamDuration / 60) }}m {{ (int) $jamDuration % 60 }}s</span>
                    </div>
                    @error('judgeGroupId') <p class="oc-err">{{ $message }}</p> @enderror
                    @error('judgeDivisionId') <p class="oc-err">{{ $message }}</p> @enderror
                </div>
            @endif
            @include('livewire.partials.run-timer', ['started' => $ocStartedTs, 'duration' => $ocDuration, 'running' => $ocRunning])

            {{-- Layar penonton --}}
            <div class="col" style="gap:4px;">
                <span class="oc-lbl">Layar penonton</span>
                <div class="oc-seg" role="group" aria-label="Layar penonton">
                    <button type="button" wire:click="showIdleScreen" class="{{ $activeEvent?->idle_screen ? 'on' : '' }}" aria-pressed="{{ $activeEvent?->idle_screen ? 'true' : 'false' }}">IDLE</button>
                    <button type="button" wire:click="hideIdleScreen" class="{{ !$activeEvent?->idle_screen ? 'on' : '' }}" aria-pressed="{{ !$activeEvent?->idle_screen ? 'true' : 'false' }}">LIVE SCORE</button>
                </div>
            </div>

            {{-- Progres judge saat run/reveal --}}
            @if($ocRunning || $ocPhase === 'REVEALING')
                @if($ocIsJam)
                    @include('livewire.partials.jam-heat-status', ['assignedJudges' => $assignedJudges ?? collect(), 'jamProgress' => $jamProgress ?? [], 'jamHeatRegs' => $ocHeatRegs, 'isHeadJudge' => $isHeadJudge])
                @else
                    @include('livewire.partials.judge-status-list', ['assignedJudges' => $assignedJudges ?? collect(), 'liveJudgeScores' => $liveJudgeScores ?? collect(), 'isHeadJudge' => $isHeadJudge])
                @endif
            @endif
        </section>

        {{-- ── KOLOM 3: PAPAN SKOR & PENGUMUMAN ── --}}
        <section class="col" style="gap:16px;min-width:0;">
            @if($judgeDivisionId)
                @include('livewire.partials.operator-leaderboard')
            @else
                <div class="oc-card"><span class="oc-lbl">Papan skor</span><p class="mono dim" style="font-size:12px;">Pilih divisi untuk melihat papan skor.</p></div>
            @endif

            @if($activeDiv)
            <div class="oc-card">
                <span class="oc-lbl">Pengumuman</span>
                {{-- Dua tombol sengaja independen (bukan else-else) — lihat catatan lama: live_stage pindah ke FINAL
                     begitu finalis disimpan, jadi pengumuman kualifikasi harus tetap bisa diklik. --}}
                @if($ocHasFinalists)
                    @if($activeDiv->qualification_announced_at)
                        <button type="button" wire:click="unannounceQualificationResults({{ $activeDiv->id }})" class="oc-ghost">🔇 BATALKAN PENGUMUMAN KUALIFIKASI</button>
                    @else
                        <button type="button" wire:click="announceQualificationResults({{ $activeDiv->id }})" class="oc-ghost">📢 UMUMKAN HASIL KUALIFIKASI</button>
                    @endif
                @endif
                @if($activeDiv->live_stage === 'FINAL')
                    @if($activeDiv->final_announced_at)
                        <button type="button" wire:click="unannounceFinalResults({{ $activeDiv->id }})" class="oc-ghost">🔇 BATALKAN PENGUMUMAN JUARA</button>
                    @else
                        <button type="button" wire:click="announceFinalResults({{ $activeDiv->id }})" class="oc-ghost">🏆 UMUMKAN PEMENANG FINAL</button>
                    @endif
                @endif
                @if(!$ocHasFinalists && $activeDiv->live_stage !== 'FINAL')
                    <p class="mono dim" style="font-size:12px;">Pengumuman tersedia setelah finalis dipilih.</p>
                @endif
                @error('judgeEventId') <p class="oc-err">{{ $message }}</p> @enderror
            </div>
            @endif
        </section>
    </div>

    {{-- ── ACTION BAR: satu aksi utama per fase ── --}}
    <div class="oc-bar">
        <p class="mono" style="font-size:14px;">{{ $ocStatus }}</p>

        @if($ocPhase === 'REVEALING')
            <button type="button" wire:click="endSession" wire:key="oc-back" class="oc-btn pri">← KEMBALI KE LEADERBOARD</button>

        @elseif($ocRunning)
            {{-- STOP = batalkan run (skor run ini dihapus) → harus ditahan 2 detik --}}
            {{-- STOP = batalkan run/heat (skor yang sudah masuk dihapus) → minta konfirmasi --}}
            <button type="button" wire:click="endSession" wire:key="oc-stop" class="oc-btn dng"
                wire:confirm="{{ $ocIsJam ? 'Batalkan heat? Semua skor heat ini akan dihapus.' : 'Batalkan run? Skor run ini akan dihapus.' }}">■ STOP {{ $ocIsJam ? 'HEAT' : 'RUN' }}</button>

        @else
            @if($ocPhase === 'NEXT')
                <button type="button" wire:click="endSession" wire:key="oc-cancel-preview" class="oc-btn sec">✗ BATAL PREVIEW</button>
            @elseif(!$ocIsJam && $liveRiderId)
                <button type="button" wire:click="showNextRider($wire.liveRiderId, $wire.liveRunNumber)" wire:key="oc-preview" class="oc-btn sec">👁 PREVIEW</button>
            @endif

            @if($ocIsJam)
                <button type="button" wire:click="startJamHeat" wire:key="oc-start-heat" class="oc-btn pri" @if(!$ocCanStart) disabled @endif>▶ MULAI HEAT</button>
            @else
                <button type="button" wire:click="startRun"
                    wire:key="oc-start-{{ $liveRiderId }}-{{ $liveRunNumber }}-{{ ($riderAlreadyRan ?? false) ? 1 : 0 }}"
                    class="oc-btn pri" @if(!$ocCanStart) disabled @endif
                    @if($riderAlreadyRan ?? false) wire:confirm="Rider ini sudah melakukan {{ strtolower($ocRunLabel) }} {{ $liveRunNumber }} sebelumnya. Yakin ingin mengulang?" @endif>▶ MULAI {{ $ocRunLabel }}</button>
            @endif
        @endif
    </div>
</div>

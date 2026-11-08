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
    elseif (!$ocRunning && $ocPhase !== 'REVEALING' && ($liveSetupProblem ?? null)) { $ocStatus = $liveSetupProblem; }
    elseif ($ocPhase === 'REVEALING') { $ocStatus = 'Skor sedang tampil di layar publik.'; }
    elseif ($ocRunning)            { $ocStatus = 'Run berjalan: ' . $ocDone . '/' . $ocJudgeTotal . ' judge selesai. Menunggu Head Judge reveal.'; }
    elseif ($ocIsJam && $ocGroups->isNotEmpty() && !$ocGroups->contains('id', $judgeGroupId)) { $ocStatus = 'Layar publik menampilkan semua heat. Pilih satu heat untuk mulai.'; }
    elseif ($ocIsJam && $ocHeatRegs->isEmpty()) { $ocStatus = $ocIsFinal ? 'Belum ada finalis. Pilih finalis dulu di kartu Fase divisi.' : 'Pilih heat di kolom Antrean.'; }
    elseif ($ocIsJam)              { $ocCanStart = true; $ocStatus = 'Siap: ' . ($ocIsFinal && $ocGroups->isEmpty() ? 'Final' : ($ocGroups->firstWhere('id', $judgeGroupId)?->name ?? 'Heat')) . ', ' . $ocHeatRegs->count() . ' rider, ' . $ocDuration . ' dtk'; }
    elseif (!$liveRiderId)         { $ocStatus = 'Pilih rider di kolom Antrean.'; }
    else                           { $ocCanStart = true; $ocStatus = 'Siap: ' . ($ocReadyRider?->name ?? 'rider') . ' · ' . $ocRunLabel . ' ' . $liveRunNumber; }
    if (!$ocRunning && ($liveSetupProblem ?? null)) { $ocCanStart = false; }
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

            {{-- Best Trick tidak mengenal group/heat: dropdown disembunyikan. --}}
            @if($judgeDivisionId && $ocGroups->count() && !$ocIsJam && !$ocBestTrick)
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
                @if($ocIsFinal && $ocGroups->isEmpty())
                    <button class="oc-row on" type="button"><b>FINAL<small class="mono dim" style="display:block;font-size:12px;font-weight:400;">{{ $ocHeatRegs->count() }} finalis · {{ $ocDuration }} dtk</small></b><span class="oc-tag go">SIAP</span></button>
                @elseif($ocGroups->isEmpty())
                    <p class="mono" style="font-size:12px;color:var(--red);">Divisi ini belum punya group/heat. Buat di Admin → Kelola Group.</p>
                @else
                    @php $allSel = !$judgeGroupId; @endphp
                    <button type="button" wire:click="$set('judgeGroupId', 0)" class="oc-row {{ $allSel ? 'on' : '' }}" aria-pressed="{{ $allSel ? 'true' : 'false' }}">
                        <b>SEMUA HEAT<small class="mono dim" style="display:block;font-size:12px;font-weight:400;">{{ $ocGroups->count() }} heat · tampil di layar publik</small></b>
                        <span class="oc-tag {{ $allSel ? 'go' : '' }}">{{ $allSel ? 'TAMPIL' : 'PREVIEW' }}</span>
                    </button>
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
                        {{ $ocIsFinal ? 'Belum ada finalis. Pilih finalis dulu di kartu Fase divisi.' : 'Belum ada peserta approved untuk divisi/group ini.' }}
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
                        JAMMING · {{ ($ocIsFinal && $ocGroups->isEmpty()) ? 'FINAL' : strtoupper($ocGroups->firstWhere('id', $judgeGroupId)?->name ?? 'PILIH HEAT') }}
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
                @php $ocMaxRun = $ocIsJam ? 1 : ($ocBestTrick ? 2 : 3); $ocLocked = $ocRunning || $ocPhase === 'REVEALING'; @endphp
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
                    @error('liveSetup') <p class="oc-err">{{ $message }}</p> @enderror
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
            {{-- Fase divisi (event aktif saja): Kualifikasi → Final (+ heat final) → Best Trick. Aksi dijaga di server (Judge\Dashboard). --}}
            @php
                $phBusy = in_array($ocPhase, ['RUNNING', 'NEXT'], true);         // hanya run berjalan / preview yang mengunci (reveal ditutup otomatis)
                $phDone = (bool) $activeDiv->live_final_completed_at;        // ranking final sudah dihitung (hanya Admin yang bisa membuka ulang)
            @endphp
            {{-- Ringkasan fase di kolom; kontrol lengkapnya ada di dialog lebar (lebih lega daripada kolom ini). --}}
            <div class="oc-card" wire:key="oc-phase-{{ $activeDiv->id }}">
                <span class="oc-lbl">Fase divisi · {{ strtoupper($activeDiv->name) }}</span>
                <div class="flex" style="flex-wrap:wrap;gap:6px;">
                    <span class="oc-chip" style="{{ $ocIsFinal ? '' : 'outline:2px solid var(--lime);' }}">KUALIFIKASI</span>
                    <span class="oc-chip" style="{{ $ocIsFinal ? 'outline:2px solid var(--lime);' : 'opacity:.5;' }}">FINAL</span>
                    @if($activeDiv->best_trick_active)<span class="oc-chip" style="outline:2px solid #ffb020;">BEST TRICK AKTIF</span>@endif
                    @if($phDone)<span class="oc-chip">RANKING SELESAI</span>@endif
                </div>

                <button type="button" wire:click="openPhaseModal" class="oc-ghost">⚙ ATUR FASE DIVISI</button>
            </div>

            @if($phaseModalOpen)
            <div class="oc-modal" role="dialog" aria-modal="true" aria-labelledby="oc-phase-title" wire:key="oc-phase-modal"
                 x-data x-init="$nextTick(() => $el.querySelector('[data-autofocus]')?.focus())"
                 @keydown.escape.window="$wire.closePhaseModal()" wire:click.self="closePhaseModal">
                <div class="oc-modal-box">
                    <div class="between" style="gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px;">
                        <div class="col" style="gap:8px;">
                            <span class="oc-lbl" id="oc-phase-title">Fase divisi</span>
                            <b class="display" style="font-size:28px;line-height:1.1;">{{ strtoupper($activeDiv->name) }}</b>
                            <div class="flex" style="flex-wrap:wrap;gap:6px;">
                                <span class="oc-chip" style="{{ $ocIsFinal ? '' : 'outline:2px solid var(--lime);' }}">KUALIFIKASI</span>
                                <span class="oc-chip" style="{{ $ocIsFinal ? 'outline:2px solid var(--lime);' : 'opacity:.5;' }}">FINAL</span>
                                @if($activeDiv->best_trick_active)<span class="oc-chip" style="outline:2px solid #ffb020;">BEST TRICK AKTIF</span>@endif
                                @if($phDone)<span class="oc-chip">RANKING SELESAI</span>@endif
                            </div>

                        </div>
                        <button type="button" data-autofocus wire:click="closePhaseModal" class="oc-btn out" style="min-width:0;">✕ TUTUP</button>
                    </div>

                    @if($phaseError) <p class="oc-err" role="alert" style="margin-bottom:12px;">{{ $phaseError }}</p> @endif

                    <div class="oc-modal-grid">
                        <section class="col" style="gap:8px;">
                    @if($phBusy)
                        <p class="mono dim" style="font-size:12px;">{{ $ocPhase === 'NEXT' ? 'Batalkan preview rider dulu untuk mengubah fase.' : 'Selesaikan atau batalkan run/heat yang sedang berjalan dulu untuk mengubah fase.' }}</p>
                    @elseif($ocPhase === 'REVEALING')
                        <p class="mono dim" style="font-size:12px;">Skor sedang tampil di layar publik — memilih aksi fase akan menutupnya.</p>
                    @endif
                    @if(!$ocIsFinal)
                        <button type="button" wire:click="openGroupManager({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif>👥 KELOLA GROUP</button>
                        <button type="button" wire:click="openFinalistPicker({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif>🏁 PILIH FINALIS &amp; BUKA FINAL</button>
                    @elseif(!$phDone)
                        <button type="button" wire:click="completeLiveFinal({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif
                            wire:confirm="{{ $activeDiv->best_trick_active ? 'Fase Best Trick masih AKTIF. Yakin mau selesaikan final & hitung ranking sekarang?' : 'Selesaikan final dan hitung ranking? Poin akan ditambahkan ke rider.' }}">✅ SELESAIKAN FINAL &amp; HITUNG RANKING</button>
                        @if($activeDiv->best_trick_active)
                            <button type="button" wire:click="endBestTrickPhase({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif>■ SELESAI BEST TRICK</button>
                        @else
                            <button type="button" wire:click="startBestTrickPhase({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif>⚡ MULAI BEST TRICK</button>
                        @endif
                        <button type="button" wire:click="reopenQualification({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif
                            wire:confirm="Kembali ke fase kualifikasi? Heat final yang sudah dibagi tetap tersimpan sampai finalis dipilih ulang.">↩ KEMBALI KE KUALIFIKASI</button>
                    @else
                        <p class="mono dim" style="font-size:12px;">Final sudah diselesaikan dan poin sudah ditambahkan ke rider.</p>
                        <button type="button" wire:click="reopenCompletedFinal({{ $activeDiv->id }})" class="oc-ghost" @if($phBusy) disabled @endif
                            wire:confirm="Divisi ini sudah SELESAI — poin/wins/podium yang sudah ditambahkan ke rider akan DIBATALKAN, lalu divisi kembali ke fase kualifikasi. Yakin?">↩ BUKA ULANG (BATALKAN POIN)</button>
                    @endif

                    {{-- Zona berbahaya --}}
                    <button type="button" wire:click="resetDivisionScores({{ $activeDiv->id }})" class="oc-ghost" style="border-color:var(--red);color:var(--red);" @if($phBusy) disabled @endif
                        wire:confirm="Reset SEMUA nilai rider di divisi '{{ $activeDiv->name }}'? Skor kualifikasi & final akan dihapus, finalis & pengumuman dibatalkan, divisi balik ke fase kualifikasi. Kalau final-nya sudah selesai, poin yang sudah ditambahkan dibatalkan dulu. Tidak bisa dikembalikan.">🗑 RESET SEMUA NILAI DIVISI</button>
                        </section>
                        @if($groupManagerOpen && !$ocIsFinal)
                        <section>
                @if($groupManagerOpen && !$ocIsFinal)
                    <div class="col" style="gap:10px;padding:12px;border:2px solid var(--ink);">
                        <span class="oc-lbl">Kelola group kualifikasi</span>
                        <div class="flex" style="gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="text" wire:model="newGroupName" wire:keydown.enter="createGroup({{ $activeDiv->id }})" class="input-field" placeholder="Nama group, mis. Heat A" style="flex:1;min-width:140px;min-height:44px;">
                            <button type="button" wire:click="createGroup({{ $activeDiv->id }})" class="oc-ghost">+ TAMBAH</button>
                        </div>
                        @error('newGroupName') <p class="oc-err">{{ $message }}</p> @enderror
                        @if(($phaseGroups ?? collect())->isNotEmpty())
                            <div class="flex" style="gap:6px;flex-wrap:wrap;align-items:center;">
                                @foreach($phaseGroups as $g)
                                    <span class="oc-chip" wire:key="pg-{{ $g->id }}">{{ $g->name }} ({{ $g->registrations_count }})
                                        <button type="button" wire:click="deleteGroup({{ $g->id }})" wire:confirm="Hapus group '{{ $g->name }}'? Rider di dalamnya jadi belum ter-group." style="background:none;border:none;color:var(--red);cursor:pointer;font-size:16px;min-width:32px;min-height:32px;" aria-label="Hapus group {{ $g->name }}">✕</button>
                                    </span>
                                @endforeach
                                <button type="button" wire:click="randomizeGroups({{ $activeDiv->id }})" class="oc-ghost" style="min-height:40px;">🎲 ACAK KE GROUP</button>
                            </div>
                                                        <div class="col" style="gap:4px;max-height:260px;overflow-y:auto;">
                                @foreach($phaseRegistrations as $reg)
                                    <div class="flex" style="gap:8px 10px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:6px 10px;background:var(--bg-2);" wire:key="pr-{{ $reg->id }}">
                                        <b style="font-size:14px;flex:1 1 140px;min-width:0;overflow-wrap:anywhere;">{{ $reg->name }}</b>
                                        <select wire:change="assignToGroup({{ $reg->id }}, $event.target.value)" class="input-field" style="width:auto;flex:0 0 auto;max-width:100%;min-height:40px;">
                                            <option value="" @selected(!$reg->division_group_id)>— belum ada group —</option>
                                            @foreach($phaseGroups as $g)
                                                <option value="{{ $g->id }}" @selected((int) $reg->division_group_id === (int) $g->id)>{{ $g->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mono dim" style="font-size:12px;">Belum ada group. Tambah group dulu, baru bisa acak/assign rider.</p>
                        @endif
                        <button type="button" wire:click="closeGroupManager" class="oc-btn out" style="min-width:0;">TUTUP</button>
                    </div>
                @endif

                        </section>
                        @endif
                        @if($finalistPickerOpen && !$ocIsFinal)
                        <section>
                @if($finalistPickerOpen && !$ocIsFinal)
                    <div class="col" style="gap:10px;padding:12px;border:2px solid var(--lime);">
                        <span class="oc-lbl">Pilih finalis (dari peringkat kualifikasi gabungan)</span>
                        <div class="flex" style="gap:8px;align-items:center;flex-wrap:wrap;">
                            <label class="oc-lbl" for="oc-topn">Top</label>
                            <input id="oc-topn" type="number" min="1" wire:model="finalistTopN" class="input-field" style="width:80px;min-height:44px;">
                            <button type="button" wire:click="autoSelectTopFinalists({{ $activeDiv->id }})" class="oc-ghost">AUTO-PILIH</button>
                        </div>
                        @if(($phaseRanking ?? collect())->isEmpty())
                            <p class="mono dim" style="font-size:12px;">Belum ada skor kualifikasi.</p>
                        @else
                            <div class="col" style="gap:4px;max-height:min(380px,50vh);overflow-y:auto;">
                                @foreach($phaseRanking as $i => $row)
                                    <label class="flex" style="gap:10px;align-items:center;padding:8px 10px;background:var(--bg-2);cursor:pointer;min-height:44px;">
                                        <input type="checkbox" wire:model="selectedFinalistRegIds" value="{{ $row['registration']->id }}" style="accent-color:var(--lime);width:20px;height:20px;">
                                        <span class="mono dim" style="font-size:12px;width:28px;">#{{ $i + 1 }}</span>
                                        <b style="flex:1;font-size:14px;">{{ $row['rider']->name }}</b>
                                        <span class="mono" style="font-size:13px;color:var(--lime);">{{ number_format($row['total'], 1) }}</span>
                                        @if($finalistSplitMode === 'manual' && $finalistHeatCount > 1)
                                            <select wire:model="finalistHeatOf.{{ $row['registration']->id }}" class="input-field" style="width:auto;min-height:40px;" aria-label="Heat final {{ $row['rider']->name }}">
                                                <option value="">Heat…</option>
                                                @for($h = 1; $h <= min($finalistHeatCount, 8); $h++)<option value="{{ $h }}">Heat {{ $h }}</option>@endfor
                                            </select>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex" style="gap:8px;align-items:center;flex-wrap:wrap;" title="1 = satu kolam gabungan. Lebih dari 1 = finalis dibagi rata ke heat (pola snake menurut peringkat kualifikasi). Peringkat akhir tetap gabungan semua heat.">
                            <label class="oc-lbl" for="oc-fheat">Heat final</label>
                            <input id="oc-fheat" type="number" min="1" max="8" wire:model.live.debounce.400ms="finalistHeatCount" class="input-field" style="width:80px;min-height:44px;">
                            <span class="mono dim" style="font-size:11px;">1 = satu kolam gabungan</span>
                        </div>
                        @if($finalistHeatCount > 1)
                            <div class="flex" style="gap:8px;align-items:center;flex-wrap:wrap;">
                                <span class="oc-lbl">Pembagian</span>
                                <label class="flex" style="gap:6px;align-items:center;min-height:44px;cursor:pointer;"><input type="radio" wire:model.live="finalistSplitMode" value="auto" style="accent-color:var(--lime);width:18px;height:18px;"> <b style="font-size:13px;">Otomatis</b></label>
                                <label class="flex" style="gap:6px;align-items:center;min-height:44px;cursor:pointer;"><input type="radio" wire:model.live="finalistSplitMode" value="manual" style="accent-color:var(--lime);width:18px;height:18px;"> <b style="font-size:13px;">Manual</b></label>
                                @if($finalistSplitMode === 'manual')
                                    <button type="button" wire:click="autoFillFinalistHeats({{ $activeDiv->id }})" class="oc-ghost" style="min-height:40px;">⚙ ISI OTOMATIS DULU</button>
                                @endif
                            </div>
                            @if($finalistSplitMode === 'manual')
                                <p class="mono dim" style="font-size:11px;">Pilih heat di samping tiap finalis yang dicentang. Tiap heat harus punya minimal 1 finalis.</p>
                            @endif
                        @endif
                        <div class="flex" style="gap:8px;flex-wrap:wrap;">
                            <button type="button" wire:click="openFinalPhase({{ $activeDiv->id }})" class="oc-btn pri" style="min-width:0;flex:1;">SIMPAN &amp; BUKA FINAL</button>
                            <button type="button" wire:click="cancelFinalistPicker" class="oc-btn out" style="min-width:0;">BATAL</button>
                        </div>
                        @if($finalistError) <p class="oc-err" role="alert">{{ $finalistError }}</p> @endif
                    </div>
                @endif
                        </section>
                        @endif
                        @if($ocIsFinal && !$phDone && ($finalHeatOverview ?? collect())->isNotEmpty())
                        <section>
                        @if(true)
                            <div class="col" style="gap:8px;padding:10px;border:2px solid var(--ink);">
                                <span class="oc-lbl">Atur heat final</span>
                                @foreach($finalHeatOverview as $ov)
                                    <div class="col" style="gap:4px;" wire:key="fh-{{ $ov['group']->id }}">
                                        <b style="font-size:13px;">{{ strtoupper($ov['group']->name) }} <span class="mono dim" style="font-weight:400;">({{ $ov['finalists']->count() }})</span></b>
                                        @foreach($ov['finalists'] as $fr)
                                            <div class="flex" style="gap:8px 10px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:4px 8px;background:var(--bg-2);" wire:key="fh-{{ $ov['group']->id }}-{{ $fr->id }}">
                                                <span style="font-size:14px;flex:1 1 130px;min-width:0;overflow-wrap:anywhere;">{{ $fr->name }}</span>
                                                <select wire:change="moveFinalistHeat({{ $fr->id }}, $event.target.value)" class="input-field" style="width:auto;flex:0 0 auto;min-height:40px;" @if($phBusy) disabled @endif aria-label="Pindahkan {{ $fr->name }} ke heat">
                                                    @foreach($finalHeatOverview as $opt)
                                                        <option value="{{ $opt['group']->id }}" @selected($opt['group']->id === $ov['group']->id)>{{ $opt['group']->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                                <p class="mono dim" style="font-size:11px;">Finalis yang sudah punya nilai final tidak bisa dipindah.</p>
                            </div>
                        @endif
                        </section>
                        @endif
                    </div>
                </div>
            </div>
            @endif

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
            {{-- STOP = batalkan run/heat → tanya dulu: hapus skor yang sudah masuk, atau simpan. --}}
            <div x-data="{ ask: false }" style="display:contents;">
                <button type="button" x-show="!ask" @click="ask = true" wire:key="oc-stop" class="oc-btn dng">■ STOP {{ $ocIsJam ? 'HEAT' : 'RUN' }}</button>
                <span x-show="ask" x-cloak class="oc-ask">
                    <span class="mono" style="font-size:13px;">Nilai yang sudah masuk diapakan?</span>
                    <button type="button" wire:click="endSession(true)" wire:key="oc-stop-del" class="oc-btn dng"
                        wire:confirm="{{ $ocIsJam ? 'Hapus semua skor heat ini dan batalkan?' : 'Hapus skor run ini dan batalkan?' }}">🗑 HAPUS NILAI</button>
                    <button type="button" wire:click="endSession(false)" wire:key="oc-stop-keep" class="oc-btn sec">💾 SIMPAN NILAI</button>
                    <button type="button" @click="ask = false" class="oc-btn out">KEMBALI</button>
                </span>
            </div>

        @else
            @if($ocPhase === 'NEXT')
                <button type="button" wire:click="endSession" wire:key="oc-cancel-preview" class="oc-btn sec">✗ BATAL PREVIEW</button>
            @elseif(!$ocIsJam && $liveRiderId)
                <button type="button" wire:click="showNextRider($wire.liveRiderId, $wire.liveRunNumber)" wire:key="oc-preview" class="oc-btn sec">👁 PREVIEW</button>
            @elseif($ocIsJam && $ocCanStart)
                <button type="button" wire:click="showNextHeat" wire:key="oc-preview-heat" class="oc-btn sec">👁 PREVIEW HEAT</button>
            @endif

            @if($ocIsJam)
                <button type="button" wire:click="startJamHeat" wire:key="oc-start-heat-{{ ($heatAlreadyRan ?? false) ? 1 : 0 }}-{{ $liveRunNumber }}" class="oc-btn pri" @if(!$ocCanStart) disabled @endif
                    @if($heatAlreadyRan ?? false) wire:confirm="Heat ini sudah punya nilai untuk RUN {{ $liveRunNumber }}. Mulai lagi? Nilai yang sudah ada tidak otomatis terhapus." @endif>▶ MULAI HEAT</button>
            @else
                <button type="button" wire:click="startRun"
                    wire:key="oc-start-{{ $liveRiderId }}-{{ $liveRunNumber }}-{{ ($riderAlreadyRan ?? false) ? 1 : 0 }}"
                    class="oc-btn pri" @if(!$ocCanStart) disabled @endif
                    @if($riderAlreadyRan ?? false) wire:confirm="Rider ini sudah melakukan {{ strtolower($ocRunLabel) }} {{ $liveRunNumber }} sebelumnya. Yakin ingin mengulang?" @endif>▶ MULAI {{ $ocRunLabel }}</button>
            @endif
        @endif
    </div>
</div>

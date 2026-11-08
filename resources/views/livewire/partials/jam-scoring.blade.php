{{-- Form nilai Jamming Season: semua rider di heat dinilai bersamaan.
     Expects $jamHeatRegs, $jamSubmitted, $jamAllScores, $eventCriteria, $jamScores, $jamDivision, $isRunning, $isHeadJudge --}}
@php
    $criteria   = $eventCriteria ?? collect();
    $jamRunNo   = (int) ($activeEvent?->live_run_number ?: $liveRunNumber);
    // Warna per nomor run (sama dengan konsol Head Judge) supaya jelas ini run keberapa.
    $jamRunColor = [1 => '#5ec8ff', 2 => '#b78cff', 3 => '#ff7ac8'][$jamRunNo] ?? '#5ec8ff';
@endphp
<div class="col" style="gap:14px;">
    <div class="panel" style="padding:16px 20px;border:2px solid {{ $isRunning ? 'var(--red)' : 'var(--line)' }};">
        <div class="between">
            <span class="kicker">JAMMING SEASON · {{ strtoupper($jamDivision->name) }} · {{ $jamDivision->live_stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI' }}</span>
            <span class="mono" style="font-size:12px;display:flex;align-items:center;gap:8px;"><b style="background:{{ $jamRunColor }};color:#0a0a0b;padding:3px 10px;letter-spacing:.1em;">RUN {{ $jamRunNo }}</b> {{ $jamHeatRegs->count() }} RIDER · {{ $jamDivision->jam_duration }}s</span>
        </div>
        @if(!$isRunning)
            <p class="mono dim" style="font-size:11px;margin-top:8px;">Heat belum dimulai. Form nilai tiap rider muncul saat heat dimulai.</p>
            <div class="flex gap-s" style="flex-wrap:wrap;margin-top:10px;">
                @foreach($jamHeatRegs as $reg)
                    <span class="badge badge-out">{{ $reg->name }}</span>
                @endforeach
            </div>
        @endif
    </div>

    @if($isRunning)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;">
            @foreach($jamHeatRegs as $reg)
                @php
                    $done  = $jamSubmitted[$reg->id] ?? false;
                    $total = count($jamScores[$reg->id] ?? []) ? round(array_sum(array_filter($jamScores[$reg->id], 'is_numeric')) / max(1, count($jamScores[$reg->id])), 1) : 0;
                    // Head Judge: skor akhir = rata-rata nilai SEMUA juri yang sudah submit (angka yang dipakai leaderboard).
                    $riderScores = ($jamAllScores ?? [])[$reg->id] ?? collect();
                    $judgeTotal  = ($assignedJudges ?? collect())->count();
                    $finalAvg    = ($isHeadJudge ?? false) && $riderScores->isNotEmpty() ? round($riderScores->avg('total'), 1) : null;
                    $finalAll    = $finalAvg !== null && $judgeTotal > 0 && $riderScores->count() >= $judgeTotal;
                    // Angka di samping TERKIRIM = nilai milik user ini sendiri. Setelah submit/reload, form kosong,
                    // jadi ambil dari nilai tersimpan; rata-rata semua juri hanya di baris SKOR AKHIR.
                    $ownSaved    = $done ? $riderScores->firstWhere('judge_user_id', auth()->id())?->total : null;
                    $shownTotal  = $ownSaved !== null ? round($ownSaved, 1) : $total;
                @endphp
                <div class="panel" style="padding:16px;border:2px solid {{ $done ? 'var(--lime)' : 'var(--ink)' }};border-top:6px solid {{ $jamRunColor }};" wire:key="jam-rider-{{ $reg->id }}">
                    <div class="between" style="margin-bottom:12px;">
                        <span class="flex" style="align-items:center;gap:12px;min-width:0;">
                            <x-avatar :initials="collect(preg_split('/\s+/', trim($reg->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$reg->photo_url" :size="56" />
                            <span class="display" style="font-size:32px;line-height:1.05;letter-spacing:.02em;">{{ $reg->name }}</span>
                        </span>
                        <span class="flex" style="align-items:center;gap:8px;">
                            @if($done)<span class="badge badge-lime" style="font-size:11px;">TERKIRIM</span>@endif
                            <span class="display tnum" style="font-size:32px;color:var(--lime);">{{ number_format($shownTotal, 1) }}</span>
                        </span>
                    </div>
                    @if(!$done)
                        @include('livewire.partials.score-input', ['criteria' => $criteria, 'modelPrefix' => 'jamScores.' . $reg->id, 'scores' => $jamScores[$reg->id] ?? [], 'name' => $reg->name, 'max' => 100])
                    @endif
                    @if($done)
                        {{-- Terkunci seperti live score biasa; revisi lewat tombol Edit Head Judge. --}}
                        <span class="badge badge-lime">✓ SUBMITTED</span>
                    @else
                        <button wire:click="submitJamScore({{ $reg->id }})" class="btn btn-lime btn-sm" style="margin-top:10px;" @if($criteria->isEmpty()) disabled @endif>Submit Score</button>
                        @error('jamScores.' . $reg->id) <p class="mono" style="font-size:11px;color:var(--red);margin-top:6px;">{{ $message }}</p> @enderror
                    @endif

                    {{-- Nilai judge lain yang sudah submit untuk rider ini --}}
                    @if(($isHeadJudge ?? false) && $riderScores->isNotEmpty())
                        <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--line);">
                            <span class="mono dim" style="font-size:9px;letter-spacing:0.12em;">NILAI JUDGE</span>
                            @foreach($riderScores as $js)
                                <div class="between" style="padding:3px 0;">
                                    <span class="mono" style="font-size:11px;">{{ $js->judge?->name ?? '—' }}</span>
                                    <span class="flex gap-s" style="align-items:center;">
                                        <span class="mono tnum" style="font-size:12px;color:var(--lime);">{{ number_format($js->total, 1) }}</span>
                                        @if(($isHeadJudge ?? false))
                                            <button wire:click="reopenJudgeScore({{ $js->id }})"
                                                wire:confirm="Buka ulang skor {{ $js->judge?->name ?? 'judge ini' }} untuk direvisi?"
                                                class="btn btn-sm btn-ghost" style="font-size:9px;padding:2px 8px;">✎ Edit</button>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                            @if($finalAvg !== null)
                                <div class="between" style="margin-top:8px;padding-top:8px;border-top:1px dashed var(--line);">
                                    <span class="mono" style="font-size:11px;letter-spacing:0.12em;font-weight:700;">SKOR AKHIR{{ $finalAll ? '' : ' (SEMENTARA)' }}</span>
                                    <span class="display tnum" style="font-size:26px;color:var(--lime);">{{ number_format($finalAvg, 1) }}</span>
                                </div>
                                <span class="mono dim" style="font-size:10px;">Rata-rata {{ $riderScores->count() }}{{ $judgeTotal ? ' dari ' . $judgeTotal : '' }} judge</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

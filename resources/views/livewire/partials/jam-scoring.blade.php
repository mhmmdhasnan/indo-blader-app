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
                            @unless($done)<span class="display tnum" style="font-size:32px;color:var(--lime);">{{ number_format($shownTotal, 1) }}</span>@endunless
                        </span>
                    </div>
                    @if(!$done)
                        @include('livewire.partials.score-input', ['criteria' => $criteria, 'modelPrefix' => 'jamScores.' . $reg->id, 'scores' => $jamScores[$reg->id] ?? [], 'name' => $reg->name, 'max' => 100])
                    @endif
                    @if($done)
                        {{-- Terkunci seperti live score biasa; revisi lewat tombol Edit Head Judge. --}}
                        <span class="flex" style="align-items:center;gap:12px;">
                            <span class="badge badge-lime">✓ SUBMITTED</span>
                            <span class="display tnum" style="font-size:32px;line-height:1;color:var(--lime);">{{ number_format($shownTotal, 1) }}</span>
                        </span>
                    @else
                        <button wire:click="submitJamScore({{ $reg->id }})" class="btn btn-lime btn-sm" style="margin-top:10px;" @if($criteria->isEmpty()) disabled @endif>Submit Score</button>
                        @error('jamScores.' . $reg->id) <p class="mono" style="font-size:11px;color:var(--red);margin-top:6px;">{{ $message }}</p> @enderror
                    @endif

                    {{-- Nilai judge lain yang sudah submit untuk rider ini --}}
                    @if(($isHeadJudge ?? false) && $riderScores->isNotEmpty())
                        <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--line);">
                            <span class="mono dim" style="font-size:9px;letter-spacing:0.12em;">NILAI JUDGE</span>
                            @foreach($riderScores as $js)
                                @php
                                    $jOrder = ($assignedJudges ?? collect())->pluck('user_id')->values(); $jIdx = $jOrder->search((int) $js->judge_user_id);
                                    $jc = ['#ff4d4d','#3ddc84','#4d9bff','#ffb020','#b78cff','#ff7ac8','#5ee0e0','#e6e84a'][($jIdx === false ? (int) $js->judge_user_id : $jIdx) % 8];
                                    $editing = (int) ($hjEditScoreId ?? 0) === (int) $js->id;
                                @endphp
                                <div wire:key="jam-js-{{ $js->id }}" style="padding:3px 0;border-left:4px solid {{ $jc }};padding-left:8px;margin-top:4px;">
                                    <div class="between" style="gap:8px;flex-wrap:wrap;align-items:center;">
                                        <span class="mono" style="font-size:16px;font-weight:700;color:{{ $jc }};">{{ $js->judge?->name ?? '—' }} · {{ number_format($js->total, 1) }}</span>
                                        @unless($editing)
                                            <span class="flex" style="gap:6px;">
                                                <button type="button" wire:click="openHeadJudgeEdit({{ $js->id }})" class="btn btn-sm btn-ghost" style="font-size:10px;padding:2px 8px;">✎ EDIT</button>
                                                <button type="button" wire:click="reopenJudgeScore({{ $js->id }})"
                                                    wire:confirm="Minta {{ $js->judge?->name ?? 'judge ini' }} merevisi skornya? Skor dibuka ulang dan judge harus submit lagi."
                                                    class="btn btn-sm btn-ghost" style="font-size:10px;padding:2px 8px;">↩ REVISI</button>
                                            </span>
                                        @endunless
                                    </div>
                                    @if($editing)
                                        <div style="margin-top:6px;padding:10px;border:2px solid var(--lime);display:flex;flex-direction:column;gap:8px;">
                                            @foreach(($hjEditCriteria ?? collect()) as $crit)
                                                <label class="mono" style="font-size:11px;letter-spacing:.08em;">{{ strtoupper($crit->name) }} (0–100)
                                                    <input type="number" step="0.1" min="0" max="100" wire:model="hjEditValues.{{ $crit->key }}" class="input-field" style="width:100%;min-height:44px;">
                                                </label>
                                            @endforeach
                                            @if($hjEditError ?? null)<p class="mono" style="font-size:11px;color:var(--red);margin:0;">{{ $hjEditError }}</p>@endif
                                            <div class="flex" style="gap:8px;">
                                                <button type="button" wire:click="saveHeadJudgeEdit" class="btn btn-lime btn-sm">SIMPAN</button>
                                                <button type="button" wire:click="cancelHeadJudgeEdit" class="btn btn-sm btn-ghost">BATAL</button>
                                            </div>
                                        </div>
                                    @endif
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

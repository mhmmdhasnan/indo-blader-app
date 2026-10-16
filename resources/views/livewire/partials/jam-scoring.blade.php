{{-- Form nilai Jamming Season: semua rider di heat dinilai bersamaan.
     Expects $jamHeatRegs, $jamSubmitted, $eventCriteria, $jamScores, $jamDivision, $isRunning --}}
@php $criteria = $eventCriteria ?? collect(); @endphp
<div class="col" style="gap:14px;">
    <div class="panel" style="padding:16px 20px;border:2px solid {{ $isRunning ? 'var(--red)' : 'var(--line)' }};">
        <div class="between">
            <span class="kicker">JAMMING SEASON · {{ strtoupper($jamDivision->name) }} · {{ $jamDivision->live_stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI' }}</span>
            <span class="mono" style="font-size:11px;color:var(--lime);">{{ $jamHeatRegs->count() }} RIDER · {{ $jamDivision->jam_duration }}s</span>
        </div>
        @if(!$isRunning)
            <p class="mono dim" style="font-size:11px;margin-top:8px;">Heat belum dimulai. Form nilai tiap rider muncul saat Head Judge menekan START HEAT.</p>
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
                @endphp
                <div class="panel" style="padding:16px;" wire:key="jam-rider-{{ $reg->id }}">
                    <div class="between" style="margin-bottom:12px;">
                        <span class="display" style="font-size:20px;">{{ $reg->name }}</span>
                        <span class="display tnum" style="font-size:22px;color:var(--lime);">{{ number_format($total, 1) }}</span>
                    </div>
                    @foreach($criteria as $crit)
                        @php $val = $jamScores[$reg->id][$crit->key] ?? 90.0; @endphp
                        <div style="margin-bottom:12px;">
                            <div class="between" style="margin-bottom:4px;">
                                <span class="mono" style="font-size:10px;letter-spacing:0.12em;">{{ strtoupper($crit->name) }}</span>
                                <span class="mono tnum" style="font-size:12px;color:var(--lime);">{{ number_format($val, 1) }}</span>
                            </div>
                            <div class="flex gap-s" style="align-items:center;">
                                <input type="range" min="0" max="100" step="0.1"
                                    wire:model.live="jamScores.{{ $reg->id }}.{{ $crit->key }}"
                                    style="flex:1;accent-color:var(--lime);" />
                                <input type="number" min="0" max="100" step="0.1"
                                    wire:model.live.debounce.400ms="jamScores.{{ $reg->id }}.{{ $crit->key }}"
                                    class="input-field" style="width:60px;text-align:center;font-size:11px;padding:4px;" />
                            </div>
                        </div>
                    @endforeach
                    @if($done)
                        <span class="badge badge-lime">✓ SUBMITTED</span>
                        <button wire:click="submitJamScore({{ $reg->id }})" class="btn btn-sm btn-ghost" style="margin-left:6px;font-size:10px;">Update</button>
                    @else
                        <button wire:click="submitJamScore({{ $reg->id }})" class="btn btn-lime btn-sm" @if($criteria->isEmpty()) disabled @endif>Submit Score</button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

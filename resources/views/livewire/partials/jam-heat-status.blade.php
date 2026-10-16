{{-- Progres penilaian heat Jamming — expects $assignedJudges, $jamProgress, $jamHeatRegs, $isHeadJudge --}}
@php
    $heatSize  = ($jamHeatRegs ?? collect())->count();
    $progress  = $jamProgress ?? [];
    $judgesAll = $assignedJudges ?? collect();
    $judgesFin = $judgesAll->filter(fn ($aj) => $heatSize > 0 && ($progress[$aj->user_id] ?? 0) >= $heatSize)->count();
@endphp
@if($judgesAll->count())
<div style="margin-bottom:12px;padding:10px;border:1px solid var(--line);border-radius:3px;">
    <div class="between" style="margin-bottom:8px;">
        <span class="mono dim" style="font-size:10px;">JUDGE ({{ $judgesFin }}/{{ $judgesAll->count() }} SELESAI · {{ $heatSize }} RIDER)</span>
    </div>
    @foreach($judgesAll as $aj)
        @php $done = $progress[$aj->user_id] ?? 0; @endphp
        <div class="between" style="padding:4px 0;border-bottom:1px solid var(--line);">
            <span class="mono" style="font-size:11px;">{{ $aj->user?->name ?? '—' }}</span>
            <span class="badge {{ $heatSize && $done >= $heatSize ? 'badge-lime' : 'badge-out' }}" style="font-size:9px;">{{ $done }}/{{ $heatSize }} RIDER</span>
        </div>
    @endforeach
</div>
@endif

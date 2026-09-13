{{-- Reusable "JUDGE (n/m submit)" status list — expects $assignedJudges, $liveJudgeScores, $isHeadJudge --}}
@php
    $submittedIds = ($liveJudgeScores ?? collect())->where('status', 'DONE')->pluck('judge_user_id')->toArray();
    $accTotal     = ($liveJudgeScores ?? collect())->where('status', 'DONE')->avg('total') ?? 0;
    $totalJudges  = ($assignedJudges ?? collect())->count();
    $doneCount    = count($submittedIds);
@endphp

@if($totalJudges)
<div style="margin-bottom:12px;padding:10px;border:1px solid var(--line);border-radius:3px;">
    <div class="between" style="margin-bottom:8px;">
        <span class="mono dim" style="font-size:10px;">JUDGE ({{ $doneCount }}/{{ $totalJudges }} SUBMIT)</span>
        @if($accTotal > 0)
            <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ number_format($accTotal, 1) }}</span>
        @endif
    </div>
    @foreach($assignedJudges as $aj)
        @php
            $submitted = in_array($aj->user_id, $submittedIds);
            $ajScore   = ($liveJudgeScores ?? collect())->firstWhere('judge_user_id', $aj->user_id);
        @endphp
        <div class="between" style="padding:4px 0;border-bottom:1px solid var(--line);">
            <span class="mono" style="font-size:11px;">{{ $aj->user?->name ?? '—' }}</span>
            <div class="flex gap-s" style="align-items:center;">
                @if($submitted && $ajScore?->total !== null)
                    <span class="mono tnum" style="font-size:11px;color:var(--lime);">{{ number_format($ajScore->total, 1) }}</span>
                @endif
                <span class="badge {{ $submitted ? 'badge-lime' : 'badge-out' }}" style="font-size:9px;">{{ $submitted ? '✓ DONE' : 'PENDING' }}</span>
                @if($submitted && $isHeadJudge && $ajScore)
                    <button wire:click="reopenJudgeScore({{ $ajScore->id }})"
                        wire:confirm="Buka ulang skor {{ $aj->user?->name ?? 'judge ini' }} untuk direvisi?"
                        class="btn btn-sm btn-ghost" style="font-size:9px;padding:2px 8px;">✎ Edit</button>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif

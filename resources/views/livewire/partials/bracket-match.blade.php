{{--
  Props:
    $match   — BracketMatch model
    $slotH   — pixel height of the slot
    $isFinal — bool, highlight with lime border/colors
--}}
@php
    $nameA    = $match->riderA?->name ?? 'TBD';
    $nameB    = $match->riderB?->name ?? 'TBD';
    $winnerA  = $match->winner_registration_id && $match->winner_registration_id === $match->rider_a_registration_id;
    $winnerB  = $match->winner_registration_id && $match->winner_registration_id === $match->rider_b_registration_id;
    $matchNum = str_pad($match->match_number, 2, '0', STR_PAD_LEFT);
    $accent   = $isFinal ? 'var(--lime)' : 'var(--ink-dim)';
    $border   = $isFinal ? '3px solid var(--lime)' : '2px solid var(--line)';
    $pad      = $isFinal ? '11px 14px' : '9px 12px';
    $fs       = $isFinal ? '14px' : '13px';
    $maxW     = $isFinal ? '150px' : '130px';
@endphp

<div style="height:{{ $slotH }}px;display:flex;flex-direction:column;justify-content:center;padding:0 4px;">
    <div class="mono" style="font-size:8px;letter-spacing:0.14em;color:{{ $accent }};margin-bottom:4px;">
        MATCH {{ $matchNum }}@if($match->trick) · {{ strtoupper($match->trick->name) }}@endif
    </div>
    <div style="border:{{ $border }};overflow:hidden;border-radius:2px;background:var(--surface);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:{{ $pad }};border-bottom:1px solid var(--line);background:{{ $winnerA ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
            <span style="font-size:{{ $fs }};font-weight:{{ $winnerA ? '700' : '400' }};color:{{ $winnerA ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:{{ $maxW }};">{{ $nameA }}</span>
            <span class="mono tnum" style="font-size:{{ $fs }};font-weight:700;color:{{ $winnerA ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_a ?? '—' }}</span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:{{ $pad }};background:{{ $winnerB ? 'color-mix(in srgb,var(--lime) 14%,transparent)' : 'transparent' }};">
            <span style="font-size:{{ $fs }};font-weight:{{ $winnerB ? '700' : '400' }};color:{{ $winnerB ? 'var(--ink)' : 'var(--ink-dim)' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:{{ $maxW }};">{{ $nameB }}</span>
            <span class="mono tnum" style="font-size:{{ $fs }};font-weight:700;color:{{ $winnerB ? 'var(--lime)' : 'var(--ink-dim)' }};margin-left:8px;flex-shrink:0;">{{ $match->score_b ?? '—' }}</span>
        </div>
    </div>
    @if($match->submission_deadline)
        <div class="mono" style="font-size:8px;letter-spacing:0.1em;color:{{ now()->gt($match->submission_deadline) ? 'var(--red)' : $accent }};margin-top:4px;">
            DEADLINE {{ $match->submission_deadline->format('d M H:i') }}{{ now()->gt($match->submission_deadline) ? ' · CLOSED' : '' }}
        </div>
    @endif
    @if($isFinal && $match->winner_registration_id)
        <div style="margin-top:8px;">
            <span class="badge badge-lime" style="font-size:10px;">🏆 CHAMPION: {{ $match->winner?->name }}</span>
        </div>
    @endif
</div>

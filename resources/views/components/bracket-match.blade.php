@props(['match', 'riders' => [], 'isFinal' => false])
@php
// Support both BracketMatch model (new) and legacy array (old)
$isModel = is_object($match);

if ($isModel) {
    $nameA   = $match->riderA?->name ?? 'TBD';
    $nameB   = $match->riderB?->name ?? 'TBD';
    $scoreA  = $match->score_a;
    $scoreB  = $match->score_b;
    $winnerA = $match->winner_registration_id && $match->winner_registration_id === $match->rider_a_registration_id;
    $winnerB = $match->winner_registration_id && $match->winner_registration_id === $match->rider_b_registration_id;
    $trick   = $match->trick?->name ?? null;
} else {
    $nameA   = $riders[$match['a']] ?? ucfirst($match['a']);
    $nameB   = $riders[$match['b']] ?? ucfirst($match['b']);
    $scoreA  = $match['sa'] ?? null;
    $scoreB  = $match['sb'] ?? null;
    $winnerA = ($match['w'] ?? null) === 'a';
    $winnerB = ($match['w'] ?? null) === 'b';
    $trick   = null;
}
@endphp
<div class="bracket-match" style="{{ $isFinal ? 'border:3px solid var(--lime);' : '' }}">
    @if($trick)
        <div style="padding:4px 12px;background:var(--bg-2);border-bottom:1px solid var(--line);">
            <span class="mono dim" style="font-size:9px;letter-spacing:0.1em;">TRICK: {{ strtoupper($trick) }}</span>
        </div>
    @endif
    {{-- Rider A --}}
    <div style="
        padding:12px 16px;
        border-bottom:1px solid var(--line);
        background:{{ $winnerA ? 'color-mix(in srgb,var(--lime) 12%,transparent)' : 'transparent' }};
        display:flex;justify-content:space-between;align-items:center;
    ">
        <div class="flex" style="gap:10px;align-items:center;">
            @if($winnerA)<span style="color:var(--lime);font-size:12px;">★</span>@endif
            <span class="{{ $winnerA ? 'label' : 'dim' }}" style="font-size:14px;">{{ $nameA }}</span>
        </div>
        @if($scoreA !== null)
            <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $winnerA ? 'var(--lime)' : 'var(--ink-dim)' }};">{{ $scoreA }}</span>
        @else
            <span class="mono dim" style="font-size:12px;">TBD</span>
        @endif
    </div>
    {{-- Rider B --}}
    <div style="
        padding:12px 16px;
        background:{{ $winnerB ? 'color-mix(in srgb,var(--lime) 12%,transparent)' : 'transparent' }};
        display:flex;justify-content:space-between;align-items:center;
    ">
        <div class="flex" style="gap:10px;align-items:center;">
            @if($winnerB)<span style="color:var(--lime);font-size:12px;">★</span>@endif
            <span class="{{ $winnerB ? 'label' : 'dim' }}" style="font-size:14px;">{{ $nameB }}</span>
        </div>
        @if($scoreB !== null)
            <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $winnerB ? 'var(--lime)' : 'var(--ink-dim)' }};">{{ $scoreB }}</span>
        @else
            <span class="mono dim" style="font-size:12px;">TBD</span>
        @endif
    </div>
</div>

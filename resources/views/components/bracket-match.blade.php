@props(['match', 'riders', 'isFinal' => false])
@php
$riderA = $riders[$match['a']] ?? ucfirst($match['a']);
$riderB = $riders[$match['b']] ?? ucfirst($match['b']);
$w = $match['w'];
@endphp
<div class="bracket-match" style="{{ $isFinal ? 'border:3px solid var(--lime);' : '' }}">
    {{-- Rider A --}}
    <div style="
        padding:12px 16px;
        border-bottom:1px solid var(--line);
        background:{{ $w === 'a' ? 'color-mix(in srgb,var(--lime) 12%,transparent)' : 'transparent' }};
        display:flex;justify-content:space-between;align-items:center;
    ">
        <div class="flex" style="gap:10px;align-items:center;">
            @if($w === 'a')<span style="color:var(--lime);font-size:12px;">★</span>@endif
            <span class="{{ $w === 'a' ? 'label' : 'dim' }}" style="font-size:14px;">{{ $riderA }}</span>
        </div>
        @if($match['sa'] !== null)
            <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $w === 'a' ? 'var(--lime)' : 'var(--ink-dim)' }};">{{ $match['sa'] }}</span>
        @else
            <span class="mono dim" style="font-size:12px;">TBD</span>
        @endif
    </div>
    {{-- Rider B --}}
    <div style="
        padding:12px 16px;
        background:{{ $w === 'b' ? 'color-mix(in srgb,var(--lime) 12%,transparent)' : 'transparent' }};
        display:flex;justify-content:space-between;align-items:center;
    ">
        <div class="flex" style="gap:10px;align-items:center;">
            @if($w === 'b')<span style="color:var(--lime);font-size:12px;">★</span>@endif
            <span class="{{ $w === 'b' ? 'label' : 'dim' }}" style="font-size:14px;">{{ $riderB }}</span>
        </div>
        @if($match['sb'] !== null)
            <span class="mono tnum" style="font-size:13px;font-weight:700;color:{{ $w === 'b' ? 'var(--lime)' : 'var(--ink-dim)' }};">{{ $match['sb'] }}</span>
        @else
            <span class="mono dim" style="font-size:12px;">TBD</span>
        @endif
    </div>
</div>

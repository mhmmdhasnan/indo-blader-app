@props(['initials' => '??', 'size' => 48, 'ring' => false])
<div class="avatar" style="
    width: {{ $size }}px;
    height: {{ $size }}px;
    font-size: {{ round($size * 0.38) }}px;
    {{ $ring ? 'border-color: var(--lime);' : '' }}
">
    <span style="mix-blend-mode:difference;color:#fff;font-family:'Bebas Neue',sans-serif;font-size:{{ round($size * 0.38) }}px;">{{ $initials }}</span>
</div>

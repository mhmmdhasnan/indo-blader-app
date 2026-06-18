@props(['initials' => '??', 'size' => 48, 'ring' => false, 'src' => null])
<div class="avatar" style="
    width: {{ $size }}px;
    height: {{ $size }}px;
    font-size: {{ round($size * 0.38) }}px;
    {{ $ring ? 'border-color: var(--lime);' : '' }}
    {{ $src ? 'padding:0;overflow:hidden;' : '' }}
">
    @if($src)
        <img src="{{ $src }}" alt="{{ $initials }}"
             style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;">
    @else
        <span style="mix-blend-mode:difference;color:#fff;font-family:'Bebas Neue',sans-serif;font-size:{{ round($size * 0.38) }}px;">{{ $initials }}</span>
    @endif
</div>

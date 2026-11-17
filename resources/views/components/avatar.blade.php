@props(['initials' => '??', 'size' => 48, 'ring' => false, 'src' => null, 'zoom' => false, 'name' => null])
<div class="avatar" style="
    width: {{ $size }}px;
    height: {{ $size }}px;
    font-size: {{ round($size * 0.38) }}px;
    {{ $ring ? 'border-color: var(--lime);' : '' }}
    {{ $src ? 'padding:0;overflow:hidden;' : '' }}
    {{ $zoom && $src ? 'cursor:zoom-in;' : '' }}
"
@if($zoom && $src)
    data-zoom-src="{{ $src }}" data-zoom-name="{{ $name ?? $initials }}"
    role="button" tabindex="0" title="Klik untuk memperbesar foto"
@endif
>
    @if($src)
        <img src="{{ $src }}" alt="{{ $initials }}" loading="lazy" decoding="async"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
             style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;mix-blend-mode:difference;color:#fff;font-family:'Bebas Neue',sans-serif;font-size:{{ round($size * 0.38) }}px;">{{ $initials }}</span>
    @else
        <span style="mix-blend-mode:difference;color:#fff;font-family:'Bebas Neue',sans-serif;font-size:{{ round($size * 0.38) }}px;">{{ $initials }}</span>
    @endif
</div>

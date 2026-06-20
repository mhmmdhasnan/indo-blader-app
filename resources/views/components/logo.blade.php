@props(['size' => 38])
<a href="{{ route('home') }}" class="flex" style="align-items:center;gap:12px;text-decoration:none;">
    <img src="{{ asset('images/logo-light.png') }}" alt="Indo Blader" class="logo-light" style="width:{{ $size }}px;height:{{ $size }}px;flex-shrink:0;">
    <img src="{{ asset('images/logo-dark.png') }}" alt="Indo Blader" class="logo-dark" style="width:{{ $size }}px;height:{{ $size }}px;flex-shrink:0;">
    <div class="col" style="line-height:0.92;">
        <span class="display" style="font-size:{{ $size * 0.46 }}px;letter-spacing:0.02em;">Indo Blader</span>
        <span class="mono" style="font-size:{{ $size * 0.18 }}px;letter-spacing:0.24em;color:var(--ink-dim);">AGGRESSIVE INLINE · ID</span>
    </div>
</a>

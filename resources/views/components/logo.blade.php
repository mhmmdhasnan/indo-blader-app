@props(['size' => 38])
<a href="{{ route('home') }}" class="flex" style="align-items:center;gap:12px;text-decoration:none;">
    <div style="width:{{ $size }}px;height:{{ $size }}px;background:var(--lime);display:flex;align-items:center;justify-content:center;border-radius:2px;flex-shrink:0;">
        <span class="display" style="font-size:{{ $size * 0.55 }}px;color:#0a0a0b;line-height:1;">IB</span>
    </div>
    <div class="col" style="line-height:0.92;">
        <span class="display" style="font-size:{{ $size * 0.46 }}px;letter-spacing:0.02em;">Indo Blader</span>
        <span class="mono" style="font-size:{{ $size * 0.18 }}px;letter-spacing:0.24em;color:var(--ink-dim);">AGGRESSIVE INLINE · ID</span>
    </div>
</a>

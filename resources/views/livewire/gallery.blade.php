<div>
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap" style="padding:48px 0 40px;">
            <div class="eyebrow-row" style="margin-bottom:12px;">
                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">MEDIA /</span>
                <span class="kicker">PHOTO & VIDEO</span>
            </div>
            <h1 class="display" style="font-size:clamp(40px,7vw,84px);">Gallery</h1>
            <p class="dim" style="margin-top:10px;max-width:520px;">Race day shots, podium moments, and rider edits from across the 2026 circuit.</p>
        </div>
    </div>

    <div class="wrap section" style="padding-top:40px;">
        <div class="gallery-grid">
            @foreach($items as $item)
                @php
                    $heights = ['tall' => 380, 'mid' => 260, 'short' => 180];
                    $h = $heights[$item->height] ?? 260;
                @endphp
                <div class="gallery-item">
                    <button wire:click="open({{ $item->id }})" style="width:100%;padding:0;border:none;background:transparent;cursor:pointer;display:block;" class="rise" style="animation-delay:{{ ($loop->index % 4) * 60 }}ms;">
                        <div class="ph scanlines" data-ph="{{ $item->label }}" style="height:{{ $h }}px;border:2px solid var(--ink);border-radius:var(--radius);transition:box-shadow .15s;"
                            onmouseover="this.style.boxShadow='6px 6px 0 var(--lime)'" onmouseout="this.style.boxShadow='none'">
                            @if($item->type === 'video')
                                <div style="position:absolute;top:12px;left:12px;" class="badge badge-red">▶ VIDEO</div>
                            @endif
                            <div style="position:absolute;bottom:0;left:0;right:0;padding:12px 14px;background:linear-gradient(transparent,rgba(0,0,0,0.6));text-align:left;">
                                <span class="label" style="font-size:13px;color:#fff;">{{ $item->caption }}</span>
                            </div>
                        </div>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Lightbox --}}
    @if($lightbox && $active)
        <div style="position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;padding:20px;"
            wire:click.self="close">
            <div style="max-width:900px;width:100%;">
                <div class="between" style="margin-bottom:16px;">
                    <span class="label" style="font-size:17px;color:#fff;">{{ $active->caption }}</span>
                    <button wire:click="close" style="color:#fff;font-size:24px;width:44px;height:44px;border:2px solid rgba(255,255,255,0.3);border-radius:3px;">✕</button>
                </div>
                <div class="ph no-label scanlines" data-ph="{{ $active->label }}" style="width:100%;height:clamp(300px,60vh,600px);border:2px solid var(--ink);border-radius:var(--radius);">
                    @if($active->type === 'video')
                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;">
                            <div class="center" style="width:80px;height:80px;border:3px solid #fff;border-radius:999px;margin:0 auto 12px;">
                                <span style="font-size:28px;color:#fff;margin-left:6px;">▶</span>
                            </div>
                            <span class="label" style="color:#fff;font-size:14px;">{{ $active->caption }}</span>
                        </div>
                    @endif
                </div>
                <div class="between" style="margin-top:14px;">
                    <span class="mono dim" style="font-size:11px;color:rgba(255,255,255,0.5);">{{ strtoupper($active->label) }}</span>
                    <span class="badge badge-out" style="color:rgba(255,255,255,0.5);border-color:rgba(255,255,255,0.3);">{{ strtoupper($active->type) }}</span>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Freeform drag-and-drop canvas for positioning a sponsor banner on one
     live-score screen. Expects: $sponsors (active sponsors, with `placements`
     eager loaded), $screen ('idle' | 'leaderboard' | 'nextup'), $label,
     $previewLabel, optional $aspectRatio (default '16/9'). --}}
@php $aspectRatio = $aspectRatio ?? '16/9'; @endphp
<div class="panel" style="padding:18px;"
    x-data="{
        dragId: null,
        // Reads/writes go through the chip's own data-* attributes (not the
        // Blade-baked values from initial render) so rapid-fire keypresses or
        // back-to-back gestures — faster than a Livewire round-trip — always
        // build on the latest LOCAL value instead of a stale server snapshot.
        startResize(e, sponsorId) {
            e.preventDefault();
            const chipEl = e.target.closest('[data-chip-id]');
            const baseW = parseFloat(chipEl.dataset.baseW);
            const startSize = parseInt(chipEl.dataset.size);
            const startX = e.clientX;
            let finalSize = startSize;
            const onMove = (ev) => {
                const deltaPct = ((ev.clientX - startX) / baseW) * 100;
                finalSize = Math.max(40, Math.min(250, Math.round((startSize + deltaPct) / 5) * 5));
                chipEl.style.width = (baseW * finalSize / 100) + 'px';
                chipEl.style.height = (parseFloat(chipEl.dataset.baseH) * finalSize / 100) + 'px';
            };
            const onUp = () => {
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
                chipEl.dataset.size = finalSize;
                $wire.resizeSponsor(sponsorId, '{{ $screen }}', finalSize);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },
        handleKey(e, sponsorId) {
            const chipEl = e.currentTarget;
            let x = parseFloat(chipEl.dataset.x);
            let y = parseFloat(chipEl.dataset.y);
            let size = parseInt(chipEl.dataset.size);
            const step = e.shiftKey ? 1 : 5;
            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                $wire.unplaceSponsor(sponsorId, '{{ $screen }}');
                return;
            }
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                size = Math.min(250, size + 5);
                chipEl.dataset.size = size;
                chipEl.style.width = (parseFloat(chipEl.dataset.baseW) * size / 100) + 'px';
                chipEl.style.height = (parseFloat(chipEl.dataset.baseH) * size / 100) + 'px';
                $wire.resizeSponsor(sponsorId, '{{ $screen }}', size);
                return;
            }
            if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                size = Math.max(40, size - 5);
                chipEl.dataset.size = size;
                chipEl.style.width = (parseFloat(chipEl.dataset.baseW) * size / 100) + 'px';
                chipEl.style.height = (parseFloat(chipEl.dataset.baseH) * size / 100) + 'px';
                $wire.resizeSponsor(sponsorId, '{{ $screen }}', size);
                return;
            }
            let moved = true;
            if (e.key === 'ArrowLeft')       x = Math.max(0, x - step);
            else if (e.key === 'ArrowRight') x = Math.min(100, x + step);
            else if (e.key === 'ArrowUp')    y = Math.max(0, y - step);
            else if (e.key === 'ArrowDown')  y = Math.min(100, y + step);
            else moved = false;
            if (moved) {
                e.preventDefault();
                chipEl.dataset.x = x;
                chipEl.dataset.y = y;
                chipEl.style.left = x + '%';
                chipEl.style.top = y + '%';
                $wire.placeSponsor(sponsorId, '{{ $screen }}', x, y);
            }
        }
    }"
>
    <div class="between" style="margin-bottom:4px;flex-wrap:wrap;gap:6px;">
        <span class="kicker">{{ $label }}</span>
        <span class="mono dim" style="font-size:10px;">Seret buat pindah/ubah ukuran · klik lalu panah/+/-/Delete di keyboard</span>
    </div>

    <div
        @dragover.prevent
        @drop="
            if (dragId === null) return;
            const rect = $el.getBoundingClientRect();
            let x = Math.max(0, Math.min(100, ((($event.clientX - rect.left) / rect.width) * 100)));
            let y = Math.max(0, Math.min(100, ((($event.clientY - rect.top) / rect.height) * 100)));
            const snap = 5; // snap to a 5%-step grid so banners line up cleanly
            x = Math.round(x / snap) * snap;
            y = Math.round(y / snap) * snap;
            $wire.placeSponsor(dragId, '{{ $screen }}', x, y);
            dragId = null;
        "
        style="
            position:relative;width:100%;aspect-ratio:{{ $aspectRatio }};margin-top:10px;
            background-color:var(--bg-2);
            background-image:
                linear-gradient(color-mix(in srgb, var(--line) 55%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--line) 55%, transparent) 1px, transparent 1px);
            background-size:10% 10%;
            border:2px dashed var(--line);border-radius:6px;overflow:hidden;
        "
    >
        {{-- Center crosshair — the most common alignment target --}}
        <div style="position:absolute;left:50%;top:0;bottom:0;width:1px;background:var(--lime);opacity:0.35;pointer-events:none;"></div>
        <div style="position:absolute;top:50%;left:0;right:0;height:1px;background:var(--lime);opacity:0.35;pointer-events:none;"></div>

        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
            <span class="mono dim" style="font-size:11px;letter-spacing:0.2em;opacity:0.3;">{{ $previewLabel }}</span>
        </div>

        @foreach($sponsors as $sp)
            @php $placement = $sp->placements->firstWhere('screen', $screen); @endphp
            @continue(!$placement)
            @php
                $chipW = round(88 * $placement->size / 100);
                $chipH = round(48 * $placement->size / 100);
            @endphp
            <div
                draggable="true"
                tabindex="0"
                x-on:dragstart="dragId = {{ $sp->id }}"
                x-on:keydown="handleKey($event, {{ $sp->id }})"
                data-chip-id="{{ $sp->id }}" data-base-w="88" data-base-h="48"
                data-x="{{ $placement->x }}" data-y="{{ $placement->y }}" data-size="{{ $placement->size }}"
                title="{{ $sp->name }} — seret untuk memindahkan, atau klik lalu pakai keyboard"
                style="
                    position:absolute;left:{{ $placement->x }}%;top:{{ $placement->y }}%;transform:translate(-50%,-50%);
                    width:{{ $chipW }}px;height:{{ $chipH }}px;background:var(--bg);border:2px solid var(--lime);border-radius:4px;
                    display:flex;align-items:center;justify-content:center;padding:6px;cursor:grab;outline-color:var(--lime);
                "
            >
                @if($sp->logo)
                    <img src="{{ $sp->logo_url }}" alt="{{ $sp->name }}" style="max-width:100%;max-height:100%;object-fit:contain;pointer-events:none;">
                @else
                    <span class="mono" style="font-size:9px;text-align:center;pointer-events:none;">{{ $sp->name }}</span>
                @endif
                <button wire:click="unplaceSponsor({{ $sp->id }}, '{{ $screen }}')" title="Lepas dari layar ini"
                    style="position:absolute;top:-8px;right:-8px;width:18px;height:18px;border-radius:50%;background:var(--red);color:#fff;border:none;font-size:10px;line-height:1;cursor:pointer;padding:0;z-index:1;">✕</button>
                <div
                    draggable="false"
                    x-on:mousedown="startResize($event, {{ $sp->id }})"
                    title="Seret untuk ubah ukuran"
                    style="position:absolute;bottom:-6px;right:-6px;width:14px;height:14px;background:var(--lime);border:1px solid var(--bg);border-radius:2px;cursor:nwse-resize;z-index:1;"
                ></div>
            </div>
        @endforeach
    </div>

    @php $unplaced = $sponsors->filter(fn($sp) => !$sp->placements->firstWhere('screen', $screen))->values(); @endphp
    @if($unplaced->isNotEmpty())
        <div style="margin-top:10px;">
            <span class="mono dim" style="font-size:9px;display:block;margin-bottom:6px;">BELUM DIPASANG — SERET KE KOTAK DI ATAS:</span>
            <div class="flex gap-s" style="flex-wrap:wrap;">
                @foreach($unplaced as $sp)
                    <div
                        draggable="true"
                        x-on:dragstart="dragId = {{ $sp->id }}"
                        title="{{ $sp->name }} — seret ke kotak di atas"
                        style="width:66px;height:38px;background:var(--bg);border:2px solid var(--line);border-radius:4px;
                            display:flex;align-items:center;justify-content:center;padding:4px;cursor:grab;"
                    >
                        @if($sp->logo)
                            <img src="{{ $sp->logo_url }}" alt="{{ $sp->name }}" style="max-width:100%;max-height:100%;object-fit:contain;pointer-events:none;">
                        @else
                            <span class="mono dim" style="font-size:8px;text-align:center;pointer-events:none;">{{ $sp->name }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

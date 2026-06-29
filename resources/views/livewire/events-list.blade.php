<div>
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap" style="padding:48px 0 40px;">
            <div class="eyebrow-row" style="margin-bottom:12px;">
                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">CIRCUIT /</span>
                <span class="kicker">THE 2026 NATIONAL CIRCUIT</span>
            </div>
            <h1 class="display" style="font-size:clamp(40px,7vw,84px);">Events</h1>
            <p class="dim" style="margin-top:10px;max-width:520px;">Five sanctioned stops across Indonesia. Register, compete, climb the national ranking.</p>
        </div>
    </div>

    <div class="wrap section" style="padding-top:40px;">
        <div style="margin-bottom:26px;">
            <div class="flex gap-s" style="flex-wrap:wrap;">
                @foreach(['ALL','STREET','PARK','VERT','FLAT'] as $opt)
                    <button wire:click="$set('category','{{ $opt }}')" class="label" style="
                        font-size:11px;padding:8px 14px;border:2px solid var(--ink);border-radius:3px;
                        background:{{ $category === $opt ? 'var(--ink)' : 'transparent' }};
                        color:{{ $category === $opt ? 'var(--bg)' : 'var(--ink)' }};
                        transition:background .15s,color .15s;
                    ">{{ $opt === 'FLAT' ? 'FLATLAND' : $opt }}</button>
                @endforeach
            </div>
        </div>

        <div class="col" style="gap:18px;">
            @foreach($events as $ev)
                @php $pct = $ev->fill_pct; @endphp
                <div class="rise" style="animation-delay:{{ $loop->index * 50 }}ms;">
                    <a href="{{ route('events.show', $ev->slug) }}" class="panel event-card" style="transition:box-shadow .15s;"
                        onmouseover="this.style.boxShadow='6px 6px 0 var(--lime)'" onmouseout="this.style.boxShadow='var(--paper-shadow)'">
                        <div class="ph scanlines event-card-thumb" data-ph="{{ $ev->title }}">
                            <div style="position:absolute;top:12px;left:12px;"><x-status-badge :status="$ev->status" /></div>
                        </div>
                        <div style="padding:22px 24px;">
                            <div class="flex gap-s" style="margin-bottom:10px;flex-wrap:wrap;align-items:center;">
                                <span class="mono" style="font-size:11px;color:var(--lime);letter-spacing:0.12em;">{{ $ev->date_label }}</span>
                                <span class="dim">·</span>
                                <span class="dim" style="font-size:13px;">{{ $ev->venue }}, {{ $ev->city }}</span>
                            </div>
                            <h2 class="display" style="font-size:36px;margin-bottom:8px;">{{ $ev->title }}</h2>
                            <div class="flex gap-s" style="margin-bottom:14px;">
                                @foreach($ev->categories as $cat)
                                    <x-cat-badge :cat="$cat" />
                                @endforeach
                                <span class="badge badge-out">{{ $ev->edition }}</span>
                            </div>
                            <p class="dim" style="font-size:14px;line-height:1.5;max-width:480px;">{{ $ev->blurb }}</p>
                        </div>
                        <div class="event-card-right">
                            <div class="col" style="align-items:flex-end;gap:4px;">
                                <span class="display tnum text-glow-lime" style="font-size:28px;color:var(--lime);">{{ $ev->prize_formatted }}</span>
                                <span class="mono dim" style="font-size:9px;letter-spacing:0.16em;">PRIZE POOL</span>
                            </div>
                            <div style="width:100%;">
                                <div class="between" style="margin-bottom:6px;">
                                    <span class="mono" style="font-size:10px;color:var(--ink-dim);">SLOTS</span>
                                    <span class="mono tnum" style="font-size:11px;font-weight:700;">{{ $ev->filled }}{{ $ev->slots !== null ? '/'.$ev->slots : '' }}</span>
                                </div>
                                <div style="height:5px;background:var(--panel-2);border:1px solid var(--line);">
                                    <div style="height:100%;width:{{ $pct }}%;background:{{ $pct > 85 ? 'var(--red)' : 'var(--lime)' }};"></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach

            @if($events->isEmpty())
                <div class="panel center col" style="padding:60px;gap:16px;text-align:center;">
                    <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
                    <p class="dim">No events in this category yet.</p>
                    <button wire:click="$set('category','ALL')" class="btn btn-ghost btn-sm">Show All</button>
                </div>
            @endif
        </div>
    </div>
</div>

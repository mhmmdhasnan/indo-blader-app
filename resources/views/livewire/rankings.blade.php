<div>
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap" style="padding:48px 0 40px;">
            <div class="eyebrow-row" style="margin-bottom:12px;">
                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">RANKING /</span>
                <span class="kicker">NATIONAL POWER RANKING 2026</span>
            </div>
            <h1 class="display" style="font-size:clamp(40px,7vw,84px);">Rankings</h1>
            <p class="dim" style="margin-top:10px;max-width:520px;">Points accumulate across the national circuit. Top riders qualify directly into Nationals finals.</p>
        </div>
    </div>

    <div class="wrap section" style="padding-top:40px;">
        <div class="between" style="margin-bottom:22px;flex-wrap:wrap;gap:12px;">
            <div class="flex gap-s" style="flex-wrap:wrap;">
                @foreach(['ALL','STREET','PARK','VERT','FLAT'] as $opt)
                    <button wire:click="$set('category','{{ $opt }}')" class="label" style="
                        font-size:11px;padding:8px 14px;border:2px solid var(--ink);border-radius:3px;
                        background:{{ $category === $opt ? 'var(--ink)' : 'transparent' }};
                        color:{{ $category === $opt ? 'var(--bg)' : 'var(--ink)' }};
                    ">{{ $opt === 'FLAT' ? 'FLATLAND' : $opt }}</button>
                @endforeach
            </div>
            <span class="mono dim" style="font-size:11px;">{{ $riders->count() }} RIDERS · UPDATED WK 24</span>
        </div>

        <div class="panel" style="overflow:hidden;">
            <div class="rank-scroll">
                <div class="rank-header">
                    @foreach(['RANK','RIDER','CAT','WINS','PODIUMS','POINTS'] as $i => $h)
                        <span class="mono {{ in_array($h,['WINS','PODIUMS']) ? 'rank-hide' : '' }}"
                            style="font-size:10px;letter-spacing:0.14em;color:var(--ink-dim);text-align:{{ $i >= 3 ? 'right' : 'left' }};">{{ $h }}</span>
                    @endforeach
                </div>
                @foreach($riders as $i => $r)
                    <a href="{{ route('riders.show', $r->slug) }}" class="rank-row"
                        style="border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};transition:background .12s;"
                        onmouseover="this.style.background='var(--panel-2)'" onmouseout="this.style.background='transparent'">
                        <span class="display tnum" style="font-size:26px;color:{{ $i === 0 ? 'var(--lime)' : ($i < 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
                        <div class="flex" style="align-items:center;gap:13px;min-width:0;">
                            <x-avatar :initials="$r->initials" :size="38" :ring="$i === 0" />
                            <div class="col" style="min-width:0;">
                                <span class="label" style="font-size:15px;">{{ $r->name }}</span>
                                <span class="mono dim" style="font-size:10px;">{{ strtoupper($r->city) }} · {{ $r->nick }}</span>
                            </div>
                        </div>
                        <div><x-cat-badge :cat="$r->category" :sm="true" /></div>
                        <span class="mono tnum rank-hide" style="font-size:14px;text-align:right;">{{ $r->wins }}</span>
                        <span class="mono tnum rank-hide" style="font-size:14px;text-align:right;">{{ $r->podiums }}</span>
                        <span class="display tnum" style="font-size:22px;text-align:right;color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">{{ number_format($r->points) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div>
    <div class="halftone" style="border-bottom:2px solid var(--ink);">
        <div class="wrap" style="padding:48px 0 40px;">
            <div class="eyebrow-row" style="margin-bottom:12px;">
                <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;">ROSTER /</span>
                <span class="kicker">THE NATIONAL ROSTER</span>
            </div>
            <h1 class="display" style="font-size:clamp(40px,7vw,84px);">Riders</h1>
            <p class="dim" style="margin-top:10px;max-width:520px;">Professional aggressive inline athletes competing across Indonesia.</p>
        </div>
    </div>

    <div class="wrap section" style="padding-top:40px;">
        <div style="margin-bottom:24px;">
            <div class="flex gap-s" style="flex-wrap:wrap;">
                @foreach(['ALL','STREET','PARK','VERT','FLAT'] as $opt)
                    <button wire:click="$set('category','{{ $opt }}')" class="label" style="
                        font-size:11px;padding:8px 14px;border:2px solid var(--ink);border-radius:3px;
                        background:{{ $category === $opt ? 'var(--ink)' : 'transparent' }};
                        color:{{ $category === $opt ? 'var(--bg)' : 'var(--ink)' }};
                    ">{{ $opt === 'FLAT' ? 'FLATLAND' : $opt }}</button>
                @endforeach
            </div>
        </div>

        <div class="riders-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
            @foreach($riders as $i => $r)
                <div class="rise" style="animation-delay:{{ ($i % 4) * 60 }}ms;">
                    <a href="{{ route('riders.show', $r->slug) }}" class="panel" style="display:block;overflow:hidden;height:100%;"
                        onmouseover="this.querySelector('.rc-photo').style.transform='scale(1.04)'"
                        onmouseout="this.querySelector('.rc-photo').style.transform='none'">
                        <div style="position:relative;overflow:hidden;border-bottom:2px solid var(--ink);">
                            <div class="ph rc-photo scanlines" data-ph="Rider portrait" style="height:230px;transition:transform .35s;"></div>
                            <div style="position:absolute;top:10px;left:10px;"><x-cat-badge :cat="$r->category" /></div>
                            <span class="display" style="position:absolute;bottom:6px;right:10px;font-size:50px;color:var(--lime);line-height:0.8;">#{{ $i+1 }}</span>
                        </div>
                        <div style="padding:14px 16px 16px;">
                            <span class="mono" style="font-size:10px;color:var(--red);letter-spacing:0.14em;">{{ $r->nick }}</span>
                            <h3 class="display" style="font-size:24px;margin:2px 0 4px;">{{ $r->name }}</h3>
                            <span class="mono dim" style="font-size:11px;">{{ strtoupper($r->city) }}</span>
                            <div class="flex" style="border-top:1px solid var(--line);margin-top:12px;padding-top:10px;">
                                @foreach([['PTS',number_format($r->points)],['WINS',$r->wins],['BEST',$r->best_score]] as $j => [$lbl,$val])
                                    <div class="col" style="flex:1;border-right:{{ $j < 2 ? '1px solid var(--line)' : 'none' }};">
                                        <span class="mono tnum" style="font-size:14px;font-weight:700;">{{ $val }}</span>
                                        <span class="mono dim" style="font-size:8px;letter-spacing:0.12em;">{{ $lbl }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

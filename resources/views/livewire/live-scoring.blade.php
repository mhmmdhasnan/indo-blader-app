<div wire:poll.5s>
    {{-- Header --}}
    <div style="border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap between" style="padding:20px 0;flex-wrap:wrap;gap:12px;">
            <div class="col">
                <div class="flex gap-s" style="margin-bottom:6px;">
                    <span class="badge badge-red"><span class="live-dot"></span>LIVE</span>
                    <span class="badge badge-out">FINAL RUN 2 OF 2</span>
                </div>
                <h1 class="display" style="font-size:clamp(28px,5vw,52px);">{{ $event?->title }} — Street Final</h1>
            </div>
            <div class="mono dim" style="font-size:12px;letter-spacing:0.12em;text-align:right;">
                AUTO-REFRESHES EVERY 5s<br>
                <span style="color:var(--lime);">● SYSTEM LIVE</span>
            </div>
        </div>
    </div>

    <div class="wrap section" style="padding-top:30px;">
        <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;" class="prof-grid">

            {{-- Left: Leaderboard --}}
            <div class="col" style="gap:20px;">
                <div class="panel" style="overflow:hidden;">
                    <div style="padding:16px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                        <span class="kicker">LIVE LEADERBOARD</span>
                    </div>
                    <div class="score-scroll">
                        <div class="score-header">
                            @foreach(['#','RIDER','RUN 1','RUN 2','BEST'] as $i => $h)
                                <span class="mono {{ $h === 'RUN 1' ? 'score-hide' : '' }}"
                                    style="font-size:10px;letter-spacing:0.12em;color:var(--ink-dim);text-align:{{ $i >= 2 ? 'right' : 'left' }};">{{ $h }}</span>
                            @endforeach
                        </div>
                        @foreach($scores as $i => $row)
                            @php $isOnCourse = $row['status'] === 'ON COURSE'; @endphp
                            <div class="score-row" style="
                                border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};
                                background:{{ $isOnCourse ? 'color-mix(in srgb,var(--red) 8%,transparent)' : 'transparent' }};
                                border-left:{{ $isOnCourse ? '3px solid var(--red)' : '3px solid transparent' }};
                            ">
                                <span class="display tnum" style="font-size:26px;color:{{ $i === 0 ? 'var(--lime)' : ($i < 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
                                <div class="flex" style="align-items:center;gap:12px;">
                                    <x-avatar :initials="$row['rider']->initials" :size="36" :ring="$i === 0" />
                                    <div class="col">
                                        <span class="label" style="font-size:14px;">{{ $row['rider']->name }}</span>
                                        <span class="mono dim" style="font-size:10px;">
                                            {{ $isOnCourse ? '🔴 ON COURSE' : $row['rider']->city }}
                                        </span>
                                    </div>
                                </div>
                                <span class="mono tnum score-hide" style="font-size:14px;text-align:right;color:var(--ink-dim);">{{ $row['run1'] ? number_format($row['run1']->total, 1) : '—' }}</span>
                                <span class="mono tnum" style="font-size:14px;text-align:right;color:{{ $isOnCourse ? 'var(--red)' : 'var(--ink-dim)' }};">
                                    {{ $isOnCourse ? '...' : ($row['run2'] ? number_format($row['run2']->total, 1) : '—') }}
                                </span>
                                <span class="display tnum" style="font-size:22px;text-align:right;color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">{{ $row['best'] > 0 ? number_format($row['best'], 1) : '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Judge Scores --}}
                <div class="panel" style="overflow:hidden;">
                    <div style="padding:16px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                        <span class="kicker">JUDGE CARDS — CURRENT RIDER</span>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="tbl">
                            <thead><tr><th>JUDGE</th><th style="text-align:right;">EXEC</th><th style="text-align:right;">STYLE</th><th style="text-align:right;">CREATIVITY</th><th style="text-align:right;">AVG</th></tr></thead>
                            <tbody>
                                @foreach($judges as $j)
                                    @php $avg = round(($j['exec']+$j['style']+$j['creativity'])/3, 1); @endphp
                                    <tr>
                                        <td><div class="col"><span class="label" style="font-size:13px;">{{ $j['name'] }}</span><span class="mono dim" style="font-size:9px;">{{ $j['seat'] }}</span></div></td>
                                        <td style="text-align:right;" class="mono tnum">{{ $j['exec'] }}</td>
                                        <td style="text-align:right;" class="mono tnum">{{ $j['style'] }}</td>
                                        <td style="text-align:right;" class="mono tnum">{{ $j['creativity'] }}</td>
                                        <td style="text-align:right;" class="display tnum" style="font-size:18px;color:var(--lime);">{{ $avg }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right: Current rider + trick feed --}}
            <div class="col" style="gap:16px;">
                @if($current)
                    <div class="panel" style="overflow:hidden;">
                        <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                            <span class="kicker"><span class="live-dot" style="margin-right:6px;"></span>NOW ON COURSE</span>
                        </div>
                        <div style="padding:20px 18px;">
                            <div class="flex" style="align-items:center;gap:14px;margin-bottom:18px;">
                                <x-avatar :initials="$current->initials" :size="60" :ring="true" />
                                <div class="col">
                                    <span class="display" style="font-size:26px;">{{ $current->name }}</span>
                                    <span class="mono dim" style="font-size:11px;">STREET FINAL · RUN 2</span>
                                </div>
                            </div>
                            <div class="panel halftone center col" style="padding:24px;gap:6px;text-align:center;border:none;background:color-mix(in srgb,var(--lime) 8%,transparent);">
                                <span class="kicker">BUILDING SCORE</span>
                                <span class="display tnum text-glow-lime" style="font-size:72px;color:var(--lime);line-height:0.9;">—</span>
                                <span class="mono dim" style="font-size:11px;">/ 100 POINTS</span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="panel" style="overflow:hidden;">
                    <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                        <span class="kicker">TRICK FEED</span>
                    </div>
                    @foreach($trickFeed as $tf)
                        <div class="flex" style="gap:12px;padding:13px 18px;border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};align-items:center;">
                            <span class="mono" style="font-size:11px;color:var(--lime);font-weight:700;min-width:42px;">{{ $tf['t'] }}</span>
                            <div class="col" style="flex:1;min-width:0;">
                                <span class="label" style="font-size:13px;">{{ $tf['trick'] }}</span>
                                <span class="badge badge-out" style="font-size:8px;align-self:flex-start;margin-top:4px;">{{ $tf['diff'] }}</span>
                            </div>
                            <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ $tf['pts'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

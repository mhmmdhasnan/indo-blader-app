<div wire:poll.5s>
    {{-- Header --}}
    <div style="border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap between" style="padding:20px 0;flex-wrap:wrap;gap:12px;">
            <div class="col">
                <div class="flex gap-s" style="margin-bottom:6px;">
                    @if($event && $event->status === 'LIVE')
                        <span class="badge badge-red"><span class="live-dot"></span>LIVE</span>
                    @else
                        <span class="badge badge-out">OFFLINE</span>
                    @endif
                    <span class="badge badge-out">STREET FINAL</span>
                </div>
                <h1 class="display" style="font-size:clamp(22px,5vw,48px);">
                    {{ $event ? $event->title . ' — Live Score' : 'Live Scoring' }}
                </h1>
            </div>
            <div class="col" style="gap:8px;align-items:flex-end;">
                {{-- Event selector --}}
                <select wire:model.live="selectedEventId"
                    class="mono"
                    style="padding:8px 12px;border:2px solid var(--ink);background:var(--bg);color:var(--ink);font-size:12px;letter-spacing:0.08em;border-radius:3px;cursor:pointer;">
                    <option value="0">— Pilih Event —</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}">{{ $ev->title }}{{ $ev->status === 'LIVE' ? ' ● LIVE' : '' }}</option>
                    @endforeach
                </select>
                <div class="mono dim" style="font-size:11px;letter-spacing:0.12em;text-align:right;">
                    AUTO-REFRESH 5s<br>
                    <span style="color:var(--lime);">● SYSTEM LIVE</span>
                </div>
            </div>
        </div>
    </div>

    @if(!$event)
        {{-- No event selected --}}
        <div class="wrap section center col" style="padding:80px 0;gap:16px;">
            <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
            <span class="kicker">PILIH EVENT</span>
            <p class="mono dim" style="font-size:13px;text-align:center;max-width:320px;">
                Pilih event di atas untuk melihat live score.
            </p>
        </div>
    @elseif($scores->isEmpty())
        {{-- Event selected but no scores yet --}}
        <div class="wrap section center col" style="padding:80px 0;gap:16px;">
            <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
            <span class="kicker">BELUM ADA SCORE</span>
            <p class="mono dim" style="font-size:13px;text-align:center;max-width:360px;">
                Belum ada skor masuk untuk <strong>{{ $event->title }}</strong>.<br>
                Halaman ini akan update otomatis setiap 5 detik.
            </p>
        </div>
    @else
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
                                                {{ $isOnCourse ? '🔴 ON COURSE' : ($row['rider']->city ?? '—') }}
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
                    @if($judgeScores->isNotEmpty())
                        <div class="panel" style="overflow:hidden;">
                            <div style="padding:16px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                                <span class="kicker">JUDGE CARDS — {{ $current?->name ?? 'CURRENT RIDER' }}</span>
                            </div>
                            <div style="overflow-x:auto;">
                                <table class="tbl">
                                    <thead>
                                        <tr>
                                            <th>JUDGE</th>
                                            @php
                                                $allCriteria = $judgeScores->flatMap(fn($js) => $js->scoreDetails)
                                                    ->groupBy('criteria')
                                                    ->keys()
                                                    ->map(fn($key) => ['key' => $key, 'name' => strtoupper($key)]);
                                            @endphp
                                            @foreach($allCriteria as $c)
                                                <th style="text-align:right;">{{ $c['name'] }}</th>
                                            @endforeach
                                            <th style="text-align:right;">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($judgeScores as $idx => $js)
                                            <tr>
                                                <td>
                                                    <div class="col">
                                                        <span class="label" style="font-size:13px;">{{ $js->judge?->name ?? 'Judge ' . ($idx+1) }}</span>
                                                        <span class="mono dim" style="font-size:9px;">{{ $js->judge?->role === 'head_judge' ? 'HEAD JUDGE' : 'JUDGE ' . ($idx+1) }}</span>
                                                    </div>
                                                </td>
                                                @foreach($allCriteria as $c)
                                                    @php $detail = $js->scoreDetails->firstWhere('criteria', $c['key']); @endphp
                                                    <td style="text-align:right;" class="mono tnum">{{ $detail ? number_format($detail->score, 1) : '—' }}</td>
                                                @endforeach
                                                <td style="text-align:right;" class="display tnum" style="font-size:18px;color:var(--lime);">{{ number_format($js->total, 1) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right: Current rider --}}
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
                                        <span class="mono dim" style="font-size:11px;">STREET FINAL</span>
                                    </div>
                                </div>
                                @php
                                    $currentScore = $scores->firstWhere(fn($r) => $r['rider']->id === $current->id);
                                    $displayScore = $currentScore ? ($currentScore['best'] > 0 ? number_format($currentScore['best'], 1) : null) : null;
                                @endphp
                                <div class="panel halftone center col" style="padding:24px;gap:6px;text-align:center;border:none;background:color-mix(in srgb,var(--lime) 8%,transparent);">
                                    <span class="kicker">BUILDING SCORE</span>
                                    <span class="display tnum text-glow-lime" style="font-size:72px;color:var(--lime);line-height:0.9;">{{ $displayScore ?? '—' }}</span>
                                    <span class="mono dim" style="font-size:11px;">/ 100 POINTS</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="panel center col" style="padding:40px 24px;gap:12px;text-align:center;">
                            <span class="mono dim" style="font-size:11px;letter-spacing:0.1em;">NO RIDER ON COURSE</span>
                            <span class="mono dim" style="font-size:11px;">Menunggu rider berikutnya...</span>
                        </div>
                    @endif

                    {{-- Event info --}}
                    <div class="panel" style="overflow:hidden;">
                        <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                            <span class="kicker">EVENT INFO</span>
                        </div>
                        <div style="padding:16px 18px;" class="col" style="gap:10px;">
                            <div class="flex between" style="padding:6px 0;border-bottom:1px solid var(--line);">
                                <span class="mono dim" style="font-size:11px;">EVENT</span>
                                <span class="label" style="font-size:12px;text-align:right;">{{ $event->title }}</span>
                            </div>
                            @if($event->city)
                            <div class="flex between" style="padding:6px 0;border-bottom:1px solid var(--line);">
                                <span class="mono dim" style="font-size:11px;">KOTA</span>
                                <span class="mono" style="font-size:12px;">{{ $event->city }}</span>
                            </div>
                            @endif
                            @if($event->venue)
                            <div class="flex between" style="padding:6px 0;border-bottom:1px solid var(--line);">
                                <span class="mono dim" style="font-size:11px;">VENUE</span>
                                <span class="mono" style="font-size:12px;text-align:right;">{{ $event->venue }}</span>
                            </div>
                            @endif
                            <div class="flex between" style="padding:6px 0;">
                                <span class="mono dim" style="font-size:11px;">RIDERS</span>
                                <span class="mono" style="font-size:12px;">{{ $scores->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

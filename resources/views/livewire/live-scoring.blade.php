<div wire:poll.3s>
    {{-- Hidden data bridge: Livewire updates these attrs; Alpine reads them via MutationObserver --}}
    <div id="live-data"
         data-phase="{{ $displayPhase ?? '' }}"
         data-started="{{ $liveStartedAt ?? 0 }}"
         data-duration="{{ $runDuration }}"
         data-score="{{ $revealScore ? number_format($revealScore, 1) : '' }}"
         data-rider="{{ $liveRider?->name ?? '' }}"
         data-initials="{{ $liveRider ? collect(explode(' ', $liveRider->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('') : '' }}"
         data-avatar="{{ $liveRider?->avatar ? asset('storage/' . $liveRider->avatar) : '' }}"
         style="display:none;">
    </div>

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
                <select wire:model.live="selectedEventId"
                    class="mono"
                    style="padding:8px 12px;border:2px solid var(--ink);background:var(--bg);color:var(--ink);font-size:12px;letter-spacing:0.08em;border-radius:3px;cursor:pointer;">
                    <option value="0">— Pilih Event —</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev->id }}">{{ $ev->title }}{{ $ev->status === 'LIVE' ? ' ● LIVE' : '' }}</option>
                    @endforeach
                </select>
                <div class="mono dim" style="font-size:11px;letter-spacing:0.12em;text-align:right;">
                    AUTO-REFRESH 3s<br>
                    <span style="color:var(--lime);">● SYSTEM LIVE</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── LIVE PHASE OVERLAY ── --}}
    <div x-data="livePhaseOverlay()" x-init="boot()" x-show="phase !== 'IDLE'" x-cloak
         style="border-bottom:2px solid var(--ink);background:var(--bg);">
        <div class="wrap" style="padding:30px 0;">

            {{-- NEXT UP phase --}}
            <div x-show="phase === 'NEXT'" style="display:none;">
                <div class="panel" style="padding:32px 40px;text-align:center;border:2px solid var(--ink);position:relative;overflow:hidden;">
                    <div style="position:relative;z-index:2;">
                        <div class="flex gap-s" style="justify-content:center;margin-bottom:20px;">
                            <span class="badge badge-out" style="font-size:11px;letter-spacing:0.12em;">→ NEXT UP</span>
                        </div>
                        <div style="margin:0 auto 16px;width:96px;height:96px;border-radius:50%;border:3px solid var(--ink);overflow:hidden;background:var(--bg-2);display:flex;align-items:center;justify-content:center;">
                            <template x-if="avatarSrc">
                                <img :src="avatarSrc" style="width:100%;height:100%;object-fit:cover;display:block;">
                            </template>
                            <template x-if="!avatarSrc">
                                <span style="font-family:'Bebas Neue',sans-serif;font-size:36px;color:var(--ink);" x-text="initials"></span>
                            </template>
                        </div>
                        <div class="display" style="font-size:clamp(36px,8vw,64px);margin-bottom:6px;" x-text="riderName"></div>
                        <p class="mono dim" style="font-size:12px;letter-spacing:0.1em;">STREET FINAL</p>
                    </div>
                </div>
            </div>

            {{-- RUNNING phase: countdown --}}
            <div x-show="phase === 'RUNNING'" style="display:none;">
                <div class="panel" style="padding:32px 40px;text-align:center;border:2px solid var(--red);position:relative;overflow:hidden;">
                    <div class="scanlines" style="position:absolute;inset:0;pointer-events:none;"></div>
                    <div style="position:relative;z-index:2;">
                        <div class="flex gap-s" style="justify-content:center;margin-bottom:20px;">
                            <span class="badge badge-red"><span class="live-dot" style="margin-right:5px;"></span>NOW ON COURSE</span>
                        </div>
                        {{-- Rider profile photo --}}
                        <div style="margin:0 auto 16px;width:96px;height:96px;border-radius:50%;border:3px solid var(--red);overflow:hidden;background:var(--bg-2);display:flex;align-items:center;justify-content:center;">
                            <template x-if="avatarSrc">
                                <img :src="avatarSrc" style="width:100%;height:100%;object-fit:cover;display:block;">
                            </template>
                            <template x-if="!avatarSrc">
                                <span style="font-family:'Bebas Neue',sans-serif;font-size:36px;color:var(--ink);" x-text="initials"></span>
                            </template>
                        </div>
                        <div class="display" style="font-size:clamp(36px,8vw,64px);margin-bottom:6px;" x-text="riderName"></div>
                        <p class="mono dim" style="font-size:12px;margin-bottom:28px;letter-spacing:0.1em;">STREET FINAL</p>
                        <div class="halftone center" style="padding:28px;max-width:260px;margin:0 auto;border:2px solid var(--red);border-radius:3px;">
                            <div>
                                <p class="kicker" style="margin-bottom:8px;">RUN TIMER</p>
                                <span class="display tnum text-glow-lime"
                                      style="font-size:clamp(72px,16vw,120px);color:var(--lime);line-height:1;"
                                      x-text="remainingFormatted"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- JUDGING phase: scanning animation --}}
            <div x-show="phase === 'JUDGING'" style="display:none;">
                <div class="panel judge-scan" style="padding:40px;text-align:center;position:relative;overflow:hidden;">
                    <span class="kicker" style="display:block;margin-bottom:16px;">JUDGES SCORING</span>
                    <div class="display" style="font-size:clamp(28px,6vw,48px);margin-bottom:24px;" x-text="riderName"></div>
                    <div style="display:flex;justify-content:center;gap:20px;margin-bottom:20px;">
                        <div class="live-dot" style="width:16px;height:16px;animation-delay:0s;"></div>
                        <div class="live-dot" style="width:16px;height:16px;animation-delay:0.4s;"></div>
                        <div class="live-dot" style="width:16px;height:16px;animation-delay:0.8s;"></div>
                    </div>
                    <p class="mono dim" style="font-size:11px;letter-spacing:0.1em;">MENUNGGU SEMUA JURI SELESAI MENILAI</p>
                </div>
            </div>

            {{-- REVEALING phase: score reveal --}}
            <div x-show="phase === 'REVEALING'" style="display:none;">
                <div class="panel" style="padding:40px;text-align:center;border:2px solid var(--lime);position:relative;overflow:hidden;">
                    <div class="halftone" style="position:absolute;inset:0;pointer-events:none;opacity:0.5;"></div>
                    <div style="position:relative;z-index:2;">
                        <span class="kicker" style="display:block;margin-bottom:12px;color:var(--lime);">FINAL SCORE</span>
                        <div class="display" style="font-size:clamp(24px,5vw,40px);margin-bottom:20px;" x-text="riderName"></div>
                        <div class="score-reveal-anim" style="margin:8px 0 16px;">
                            <span class="display tnum text-glow-lime"
                                  style="font-size:clamp(80px,18vw,140px);color:var(--lime);line-height:1;"
                                  x-text="score || '—'"></span>
                        </div>
                        <p class="mono dim" style="font-size:13px;letter-spacing:0.1em;">/ 100 POINTS</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if(!$event)
        <div class="wrap section center col" style="padding:80px 0;gap:16px;">
            <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
            <span class="kicker">PILIH EVENT</span>
            <p class="mono dim" style="font-size:13px;text-align:center;max-width:320px;">
                Pilih event di atas untuk melihat live score.
            </p>
        </div>
    @elseif($scores->isEmpty())
        <div class="wrap section center col" style="padding:80px 0;gap:16px;">
            <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
            <span class="kicker">BELUM ADA SCORE</span>
            <p class="mono dim" style="font-size:13px;text-align:center;max-width:360px;">
                Belum ada skor masuk untuk <strong>{{ $event->title }}</strong>.<br>
                Halaman ini akan update otomatis setiap 3 detik.
            </p>
        </div>
    @else
        <div class="wrap section" style="padding-top:30px;">
            <div class="col" style="gap:20px;">
                    @if(!$displayPhase)
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
                                @php $isOnCourse = $displayPhase === 'RUNNING' && $event?->live_rider_id && ($row['rider']->id === $event->live_rider_id); @endphp
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
                                    <span class="mono tnum score-hide" style="font-size:14px;text-align:right;color:var(--ink-dim);">{{ (!$isOnCourse && $row['run1'] !== null) ? number_format($row['run1'], 1) : '—' }}</span>
                                    <span class="mono tnum" style="font-size:14px;text-align:right;color:{{ $isOnCourse ? 'var(--red)' : 'var(--ink-dim)' }};">
                                        {{ $isOnCourse ? '...' : ($row['run2'] !== null ? number_format($row['run2'], 1) : '—') }}
                                    </span>
                                    <span class="display tnum" style="font-size:22px;text-align:right;color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">{{ (!$isOnCourse && $row['best'] > 0) ? number_format($row['best'], 1) : '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif {{-- !$displayPhase --}}

                    {{-- Judge Scores — hanya tampil setelah REVEAL --}}
                    @if($judgeScores->isNotEmpty() && $displayPhase === 'REVEALING')
                        <div class="panel" style="overflow:hidden;">
                            <div style="padding:16px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                                <span class="kicker">JUDGE CARDS — {{ $liveRider?->name ?? 'CURRENT RIDER' }}</span>
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
        </div>
    @endif
</div>

<script>
function livePhaseOverlay() {
    return {
        phase: 'IDLE',
        remaining: 0,
        riderName: '',
        initials: '',
        score: '',
        avatarSrc: '',
        _timer: null,
        _observer: null,

        boot() {
            this.readData();
            const el = document.getElementById('live-data');
            if (el) {
                this._observer = new MutationObserver(() => this.readData());
                this._observer.observe(el, { attributes: true });
            }
        },

        readData() {
            const el = document.getElementById('live-data');
            if (!el) return;
            const serverPhase = el.dataset.phase || '';
            const startedAt   = parseInt(el.dataset.started) || 0;
            const duration    = parseInt(el.dataset.duration) || 60;
            this.riderName = el.dataset.rider || '';
            this.initials  = el.dataset.initials || '';
            this.score     = el.dataset.score || '';
            this.avatarSrc = el.dataset.avatar || '';

            if (serverPhase === 'REVEALING') {
                this.phase = 'REVEALING';
                clearTimeout(this._timer);
            } else if (serverPhase === 'RUNNING') {
                this._startCountdown(startedAt, duration);
            } else if (serverPhase === 'NEXT') {
                this.phase = 'NEXT';
                clearTimeout(this._timer);
            } else {
                this.phase = 'IDLE';
                clearTimeout(this._timer);
            }
        },

        _startCountdown(startedAt, duration) {
            clearTimeout(this._timer);
            const tick = () => {
                const elapsed = Math.floor(Date.now() / 1000) - startedAt;
                this.remaining = Math.max(0, duration - elapsed);
                if (this.remaining > 0) {
                    this.phase = 'RUNNING';
                    this._timer = setTimeout(tick, 1000);
                } else {
                    this.phase = 'JUDGING';
                }
            };
            tick();
        },

        get remainingFormatted() {
            const m = Math.floor(this.remaining / 60);
            const s = this.remaining % 60;
            return (m > 0 ? String(m).padStart(2, '0') + ':' : '') + String(s).padStart(2, '0');
        }
    };
}
</script>

<div wire:poll.3s x-data="liveFullscreen()">
    {{-- Hidden data bridge: Livewire updates these attrs; Alpine reads them via MutationObserver --}}
    <div id="live-data"
         data-phase="{{ $displayPhase ?? '' }}"
         data-started="{{ $liveStartedAt ?? 0 }}"
         data-duration="{{ $runDuration }}"
         data-score="{{ $revealScore ? number_format($revealScore, 1) : '' }}"
         data-rider="{{ $liveRider?->name ?? '' }}"
         data-initials="{{ $liveRider ? collect(explode(' ', $liveRider->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('') : '' }}"
         data-avatar="{{ $liveRider?->avatar ? asset('storage/' . $liveRider->avatar) : '' }}"
         data-event="{{ $event?->title ?? '' }}"
         data-run="{{ $event?->live_run_number ?? '' }}"
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
                <select wire:model.live="selectedEventId" x-show="!isFullscreen"
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
                <button @click="toggle()" x-show="!isFullscreen"
                    class="mono"
                    style="padding:8px 12px;border:2px solid var(--ink);background:var(--bg);color:var(--ink);font-size:11px;letter-spacing:0.1em;border-radius:3px;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    ⤢ FULLSCREEN
                </button>
            </div>
        </div>
    </div>

    {{-- ── LIVE PHASE OVERLAY ── --}}
    <div x-data="livePhaseOverlay()" x-init="boot()" x-show="phase !== 'IDLE'" x-cloak
         style="border-bottom:2px solid var(--ink);background:var(--bg);padding:0 32px;">

            {{-- ── ESPORTS CARD ── --}}
            <div style="display:grid;grid-template-columns:3fr 3fr 6fr;height:calc(100vh - 130px);overflow:hidden;border-left:2px solid var(--ink);border-right:2px solid var(--ink);">

                {{-- COL 1: foto --}}
                <div style="display:flex;overflow:hidden;position:relative;border-right:1px solid var(--line);">
                    {{-- Phase color strip --}}
                    <div style="width:36px;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:background 0.4s;"
                         :style="{
                             background: phase === 'RUNNING'   ? 'var(--red)'  :
                                         phase === 'REVEALING' ? 'var(--lime)' : 'var(--ink)'
                         }">
                        <span style="writing-mode:vertical-lr;transform:rotate(180deg);font-family:'Bebas Neue',sans-serif;font-size:12px;letter-spacing:0.2em;white-space:nowrap;transition:color 0.4s;"
                              :style="{ color: phase === 'REVEALING' ? '#000' : 'var(--bg)' }"
                              x-text="{ NEXT:'NEXT UP', RUNNING:'ON COURSE', JUDGING:'JUDGING', REVEALING:'SCORE' }[phase] || ''">
                        </span>
                    </div>
                    {{-- Photo --}}
                    <div style="flex:1;overflow:hidden;background:var(--bg-2);position:relative;">
                        <template x-if="avatarSrc">
                            <img :src="avatarSrc" style="width:100%;height:100%;object-fit:cover;object-position:top center;display:block;position:absolute;inset:0;">
                        </template>
                        <template x-if="!avatarSrc">
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;position:absolute;inset:0;">
                                <span style="font-family:'Bebas Neue',sans-serif;font-size:clamp(64px,8vw,100px);color:var(--ink);opacity:0.15;" x-text="initials"></span>
                            </div>
                        </template>
                        <div x-show="phase === 'RUNNING'" class="scanlines" style="position:absolute;inset:0;pointer-events:none;display:none;"></div>
                    </div>
                </div>

                {{-- COL 2: profile --}}
                <div style="padding:40px 36px;display:flex;flex-direction:column;justify-content:center;gap:24px;border-right:1px solid var(--line);background:var(--bg);">
                    <div>
                        <span x-show="phase === 'NEXT'"      class="badge badge-out"                                            style="display:none;">→ NEXT UP</span>
                        <span x-show="phase === 'RUNNING'"   class="badge badge-red"                                            style="display:none;"><span class="live-dot" style="margin-right:5px;"></span>NOW ON COURSE</span>
                        <span x-show="phase === 'JUDGING'"   class="badge badge-out"                                            style="display:none;">... JUDGING</span>
                        <span x-show="phase === 'REVEALING'" class="badge badge-out" style="border-color:var(--lime);color:var(--lime);display:none;">✦ FINAL SCORE</span>
                    </div>
                    <div class="display" style="font-size:clamp(32px,3vw,52px);line-height:1;" x-text="riderName"></div>
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        <div class="mono dim" style="font-size:10px;letter-spacing:0.12em;" x-text="eventTitle"></div>
                        <div class="mono" style="font-size:10px;letter-spacing:0.12em;color:var(--lime);">INDO BLADER</div>
                        <div class="mono dim" style="font-size:10px;letter-spacing:0.14em;"
                             x-text="runNumber ? 'STREET FINAL · RUN ' + runNumber : 'STREET FINAL'"></div>
                    </div>
                </div>

                {{-- COL 3: timer / score --}}
                <div style="padding:40px 48px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--bg);position:relative;overflow:hidden;">
                    <div x-show="phase === 'REVEALING'" class="halftone" style="position:absolute;inset:0;pointer-events:none;opacity:0.4;display:none;"></div>

                    {{-- NEXT --}}
                    <div x-show="phase === 'NEXT'" style="display:none;text-align:center;">
                        <p class="mono dim" style="font-size:12px;letter-spacing:0.14em;">STREET FINAL</p>
                    </div>

                    {{-- RUNNING --}}
                    <div x-show="phase === 'RUNNING'" style="display:none;text-align:center;">
                        <p class="kicker" style="margin-bottom:8px;font-size:11px;">RUN TIMER</p>
                        <span class="display tnum text-glow-lime"
                              style="font-size:clamp(80px,11vw,180px);color:var(--lime);line-height:1;"
                              x-text="remainingFormatted"></span>
                    </div>

                    {{-- JUDGING --}}
                    <div x-show="phase === 'JUDGING'" style="display:none;text-align:center;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:12px;">
                            <div class="live-dot" style="width:12px;height:12px;animation-delay:0s;flex-shrink:0;"></div>
                            <div class="live-dot" style="width:12px;height:12px;animation-delay:0.4s;flex-shrink:0;"></div>
                            <div class="live-dot" style="width:12px;height:12px;animation-delay:0.8s;flex-shrink:0;"></div>
                        </div>
                        <p class="mono dim" style="font-size:12px;letter-spacing:0.08em;margin-top:16px;">MENUNGGU SEMUA JURI SELESAI MENILAI</p>
                    </div>

                    {{-- REVEALING --}}
                    <div x-show="phase === 'REVEALING'" style="display:none;text-align:center;position:relative;z-index:1;">
                        <div class="score-reveal-anim" style="display:flex;align-items:baseline;justify-content:center;gap:10px;">
                            <span class="display tnum text-glow-lime"
                                  style="font-size:clamp(80px,11vw,180px);color:var(--lime);line-height:1;"
                                  x-text="score || '—'"></span>
                            <span class="mono dim" style="font-size:16px;letter-spacing:0.1em;">/ 100</span>
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
function liveFullscreen() {
    return {
        isFullscreen: false,

        init() {
            const onChange = () => {
                this.isFullscreen = !!document.fullscreenElement;
                document.documentElement.classList.toggle('is-fullscreen', this.isFullscreen);
            };
            document.addEventListener('fullscreenchange', onChange);
            this.$cleanup = () => document.removeEventListener('fullscreenchange', onChange);
        },

        toggle() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    };
}

function livePhaseOverlay() {
    return {
        phase: 'IDLE',
        remaining: 0,
        riderName: '',
        initials: '',
        score: '',
        avatarSrc: '',
        eventTitle: '',
        runNumber: '',
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
            this.riderName  = el.dataset.rider || '';
            this.initials   = el.dataset.initials || '';
            this.score      = el.dataset.score || '';
            this.avatarSrc  = el.dataset.avatar || '';
            this.eventTitle = el.dataset.event || '';
            this.runNumber  = el.dataset.run || '';

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
            const h = Math.floor(this.remaining / 3600);
            const m = Math.floor((this.remaining % 3600) / 60);
            const s = this.remaining % 60;
            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
    };
}
</script>

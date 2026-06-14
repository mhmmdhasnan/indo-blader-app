<div class="admin-root">
    {{-- ── SIDEBAR ── --}}
    <aside class="admin-side" style="border-right:2px solid var(--ink);background:var(--bg-2);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;">
        <div style="padding:20px 18px;border-bottom:2px solid var(--ink);">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;background:var(--lime);display:flex;align-items:center;justify-content:center;border-radius:2px;">
                    <span class="display" style="font-size:18px;color:#0a0a0b;">IB</span>
                </div>
                <div class="col" style="line-height:0.9;">
                    <span class="display" style="font-size:16px;">Indo Blader</span>
                    <span class="mono" style="font-size:9px;letter-spacing:0.2em;color:var(--ink-dim);">AGGRESSIVE INLINE · ID</span>
                </div>
            </div>
            <span class="badge badge-lime" style="margin-top:12px;font-size:9px;">ADMIN CONSOLE</span>
        </div>
        <nav class="col" style="padding:12px;gap:3px;flex:1;">
            @foreach([
                ['overview',       '◧', 'Overview'],
                ['registrations',  '✓', 'Registrations'],
                ['payments',       '₨', 'Payments'],
                ['riders',         '◉', 'Riders'],
                ['events',         '◆', 'Events'],
                ['judging',        '★', 'Judge Panel'],
                ['brackets',       '⊟', 'Brackets'],
            ] as [$k,$ic,$lbl])
                <button wire:click="$set('view','{{ $k }}')" class="flex label" style="
                    align-items:center;gap:12px;padding:11px 13px;border-radius:3px;font-size:13px;text-align:left;width:100%;
                    background:{{ $view === $k ? 'var(--ink)' : 'transparent' }};
                    color:{{ $view === $k ? 'var(--bg)' : 'var(--ink-dim)' }};
                    transition:background .15s,color .15s;
                ">
                    <span style="font-size:15px;width:18px;">{{ $ic }}</span>{{ $lbl }}
                </button>
            @endforeach
        </nav>
        <div style="padding:14px;border-top:2px solid var(--ink);">
            <a href="{{ route('home') }}" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;">← Back to Site</a>
        </div>
    </aside>

    {{-- ── MAIN ── --}}
    <div style="min-width:0;display:flex;flex-direction:column;">
        {{-- Top bar --}}
        <header class="between admin-topbar" style="padding:16px 26px;border-bottom:2px solid var(--ink);position:sticky;top:0;background:color-mix(in srgb,var(--bg) 88%,transparent);backdrop-filter:blur(8px);z-index:20;">
            <div class="col">
                <span class="kicker">INDO BLADER NATIONALS '26</span>
                <h1 class="display" style="font-size:26px;">
                    {{ collect([
                        'overview' => 'Overview', 'registrations' => 'Registrations', 'payments' => 'Payments',
                        'riders' => 'Riders', 'events' => 'Events', 'judging' => 'Judge Panel', 'brackets' => 'Brackets'
                    ])->get($view, 'Overview') }}
                </h1>
            </div>
            <div class="flex" style="align-items:center;gap:14px;">
                <span class="badge badge-out"><span class="live-dot"></span>SYSTEM LIVE</span>
                <div class="flex adm-head-user" style="align-items:center;gap:10px;">
                    <x-avatar initials="AH" :size="36" />
                    <div class="col"><span class="label" style="font-size:13px;white-space:nowrap;">A. Hidayat</span><span class="mono dim" style="font-size:10px;">HEAD JUDGE</span></div>
                </div>
            </div>
        </header>

        <div style="padding:26px;flex:1;">

            {{-- ── OVERVIEW ── --}}
            @if($view === 'overview')
                <div class="col" style="gap:20px;">
                    <div class="adm-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
                        @foreach([
                            ['TOTAL REGISTRATIONS', $registrations->count(), '▲ 12 today', 'var(--lime)'],
                            ['PENDING APPROVAL', $registrations->where('status','PENDING')->count(), 'needs review', 'var(--red)'],
                            ['REVENUE (JT IDR)', round($revenue/1e6,1), '▲ Rp 4.2jt today', null],
                            ['ACTIVE RIDERS', $riders->count(), 'across 5 events', null],
                        ] as [$lbl,$val,$sub,$accent])
                            <div class="panel halftone" style="padding:18px;">
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.14em;">{{ $lbl }}</span>
                                <div class="display tnum" style="font-size:clamp(30px,4vw,44px);color:{{ $accent ?? 'var(--ink)' }};line-height:1;margin:8px 0 6px;">{{ $val }}</div>
                                <span class="mono" style="font-size:10px;color:var(--lime);">{{ $sub }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="adm-chart-grid">
                        {{-- Bar chart --}}
                        <div class="panel" style="padding:20px;">
                            <div class="between" style="margin-bottom:22px;">
                                <span class="label" style="font-size:15px;">REGISTRATIONS BY EVENT</span>
                                <span class="mono dim" style="font-size:10px;">FILLED / CAPACITY</span>
                            </div>
                            <div class="flex" style="align-items:flex-end;gap:18px;height:200px;padding-top:10px;">
                                @foreach($events as $i => $ev)
                                    <div class="col" style="flex:1;align-items:center;gap:8px;height:100%;justify-content:flex-end;">
                                        <span class="mono tnum" style="font-size:12px;font-weight:700;">{{ $ev->filled }}</span>
                                        <div style="width:100%;max-width:54px;height:100%;display:flex;align-items:flex-end;position:relative;background:var(--panel-2);border:1px solid var(--line);">
                                            <div style="width:100%;height:{{ round($ev->filled / max($events->max('slots'),1) * 100) }}%;background:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};transition:height .8s;"></div>
                                            <div style="position:absolute;bottom:{{ round($ev->slots / max($events->max('slots'),1) * 100) }}%;left:-2px;right:-2px;height:2px;background:var(--red);"></div>
                                        </div>
                                        <span class="mono dim" style="font-size:9px;letter-spacing:0.06em;text-align:center;">{{ strtoupper(explode(' ',$ev->title)[0]) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- Activity --}}
                        <div class="panel" style="overflow:hidden;">
                            <div style="padding:16px 18px;border-bottom:2px solid var(--ink);"><span class="label" style="font-size:14px;">RECENT ACTIVITY</span></div>
                            @foreach([
                                ['Rama Adhyaksa', 'registered for Nationals', '2m', 'reg'],
                                ['Payment verified', 'Bagus Pratama · Rp 350.000', '8m', 'pay'],
                                ['Yoga Saputra', 'updated category to Vert', '21m', 'edit'],
                                ['Park Jam Bali', 'reached 90% capacity', '44m', 'warn'],
                                ['Dimas Wibowo', 'registered for Best Trick', '1h', 'reg'],
                            ] as [$a,$b,$t,$type])
                                <div class="flex" style="gap:12px;padding:13px 18px;border-bottom:1px solid var(--line);align-items:flex-start;">
                                    <span style="width:8px;height:8px;border-radius:999px;margin-top:5px;flex-shrink:0;background:{{ $type==='warn'?'var(--red)':($type==='pay'?'var(--lime)':'var(--ink-dim)') }};display:block;"></span>
                                    <div class="col" style="min-width:0;gap:3px;">
                                        <span style="font-size:13px;line-height:1.4;"><strong class="label">{{ $a }}</strong> <span class="dim">{{ $b }}</span></span>
                                        <span class="mono dim" style="font-size:10px;">{{ $t }} ago</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- ── REGISTRATIONS ── --}}
            @if($view === 'registrations')
                <div class="col" style="gap:16px;">
                    <div class="flex gap-m" style="flex-wrap:wrap;">
                        @foreach([['PENDING','var(--red)'],['APPROVED','var(--lime)'],['REJECTED','var(--ink-dim)']] as [$s,$c])
                            <div class="panel flex" style="padding:12px 18px;gap:12px;align-items:center;">
                                <span class="display tnum" style="font-size:30px;color:{{ $c }};">{{ $registrations->where('status',$s)->count() }}</span>
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $s }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="panel" style="overflow:hidden;overflow-x:auto;">
                        <div style="display:grid;grid-template-columns:1.4fr 1.3fr 90px 110px 180px;padding:12px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);min-width:700px;">
                            @foreach(['RIDER','EVENT','CAT','STATUS','ACTION'] as $h)
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $h }}</span>
                            @endforeach
                        </div>
                        @forelse($registrations as $reg)
                            <div style="display:grid;grid-template-columns:1.4fr 1.3fr 90px 110px 180px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line);min-width:700px;">
                                <div class="flex" style="align-items:center;gap:11px;min-width:0;">
                                    <x-avatar :initials="collect(explode(' ',$reg->name))->map(fn($w)=>$w[0])->take(2)->implode('')" :size="32" />
                                    <div class="col"><span class="label" style="font-size:13px;">{{ $reg->name }}</span><span class="mono dim" style="font-size:9px;">{{ strtoupper($reg->city) }}</span></div>
                                </div>
                                <span class="dim" style="font-size:12px;">{{ $reg->event?->title }}</span>
                                <x-cat-badge :cat="$reg->category" :sm="true" />
                                <span class="badge badge-{{ $reg->status_variant }}">{{ $reg->status }}</span>
                                <div class="flex gap-s">
                                    @if($reg->status === 'PENDING')
                                        <button wire:click="approveRegistration({{ $reg->id }})" class="btn btn-sm btn-lime" style="padding:6px 12px;">Approve</button>
                                        <button wire:click="rejectRegistration({{ $reg->id }})" class="btn btn-sm btn-ghost" style="padding:6px 12px;">Reject</button>
                                    @else
                                        <button wire:click="pendingRegistration({{ $reg->id }})" class="mono dim" style="font-size:11px;text-decoration:underline;">undo</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="center" style="padding:40px;color:var(--ink-dim);">No registrations yet.</div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- ── PAYMENTS ── --}}
            @if($view === 'payments')
                <div class="panel" style="overflow:hidden;overflow-x:auto;">
                    <div style="display:grid;grid-template-columns:1.4fr 110px 120px 120px 1fr;padding:12px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);min-width:700px;">
                        @foreach(['RIDER','AMOUNT','METHOD','STATUS','ACTION'] as $h)
                            <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $h }}</span>
                        @endforeach
                    </div>
                    @forelse($registrations as $reg)
                        <div style="display:grid;grid-template-columns:1.4fr 110px 120px 120px 1fr;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line);min-width:700px;">
                            <div class="flex" style="align-items:center;gap:11px;">
                                <x-avatar :initials="collect(explode(' ',$reg->name))->map(fn($w)=>$w[0])->take(2)->implode('')" :size="32" />
                                <span class="label" style="font-size:13px;">{{ $reg->name }}</span>
                            </div>
                            <span class="mono tnum" style="font-size:13px;">Rp 350.000</span>
                            <span class="dim mono" style="font-size:12px;">{{ $reg->payment_method }}</span>
                            <span class="badge badge-{{ $reg->payment_status_variant }}">{{ $reg->payment_status }}</span>
                            <div class="flex gap-s" style="align-items:center;">
                                @if($reg->payment_proof)
                                    <span class="badge badge-out" style="font-size:9px;">📎 proof</span>
                                @endif
                                @if($reg->payment_status === 'PENDING')
                                    <button wire:click="verifyPayment({{ $reg->id }})" class="btn btn-sm btn-lime" style="padding:6px 12px;">Verify</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="center" style="padding:40px;color:var(--ink-dim);">No payments yet.</div>
                    @endforelse
                </div>
            @endif

            {{-- ── RIDERS ── --}}
            @if($view === 'riders')
                <div class="panel" style="overflow:hidden;overflow-x:auto;">
                    <table class="tbl" style="min-width:700px;">
                        <thead><tr><th>RIDER</th><th>CITY</th><th>CAT</th><th style="text-align:right;">POINTS</th><th style="text-align:right;">WINS</th><th>SPONSOR</th></tr></thead>
                        <tbody>
                            @foreach($riders as $r)
                                <tr>
                                    <td>
                                        <div class="flex" style="align-items:center;gap:11px;">
                                            <x-avatar :initials="$r->initials" :size="32" />
                                            <div class="col"><span class="label" style="font-size:13px;">{{ $r->name }}</span><span class="mono dim" style="font-size:9px;">{{ $r->nick }}</span></div>
                                        </div>
                                    </td>
                                    <td class="dim" style="font-size:13px;">{{ $r->city }}</td>
                                    <td><x-cat-badge :cat="$r->category" :sm="true" /></td>
                                    <td class="mono tnum" style="font-weight:700;text-align:right;">{{ number_format($r->points) }}</td>
                                    <td class="mono tnum" style="text-align:right;">{{ $r->wins }}</td>
                                    <td class="dim mono" style="font-size:12px;">{{ $r->sponsor }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- ── EVENTS ── --}}
            @if($view === 'events')
                <div class="adm-stats" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                    @foreach($events as $ev)
                        <div class="panel" style="padding:18px;">
                            <div class="between" style="margin-bottom:10px;"><x-status-badge :status="$ev->status" /><span class="mono dim" style="font-size:10px;">{{ $ev->edition }}</span></div>
                            <h3 class="display" style="font-size:24px;">{{ $ev->title }}</h3>
                            <span class="mono dim" style="font-size:11px;">{{ $ev->date_label }} · {{ $ev->city }}</span>
                            <div class="between" style="margin:16px 0 6px;"><span class="mono dim" style="font-size:10px;">FILLED</span><span class="mono tnum" style="font-size:11px;font-weight:700;">{{ $ev->filled }}/{{ $ev->slots }}</span></div>
                            <div style="height:6px;background:var(--panel-2);border:1px solid var(--line);"><div style="height:100%;width:{{ $ev->fill_pct }}%;background:{{ $ev->fill_pct > 85 ? 'var(--red)' : 'var(--lime)' }};"></div></div>
                            <div class="flex gap-s" style="margin-top:16px;">
                                <a href="{{ route('events.show', $ev->slug) }}" class="btn btn-sm btn-ghost" style="flex:1;justify-content:center;">View</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ── JUDGING ── --}}
            @if($view === 'judging')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="prof-grid">
                    <div class="panel" style="padding:22px;">
                        <span class="kicker">NOW SCORING</span>
                        @if($currentRider)
                            <div class="flex" style="align-items:center;gap:14px;margin:14px 0 22px;">
                                <x-avatar :initials="$currentRider->initials" :size="54" :ring="true" />
                                <div class="col">
                                    <span class="display" style="font-size:28px;">{{ $currentRider->name }}</span>
                                    <span class="mono dim" style="font-size:11px;">STREET FINAL · RUN 2</span>
                                </div>
                            </div>
                        @endif
                        @foreach([
                            ['judgeExec',       'EXECUTION'],
                            ['judgeStyle',      'STYLE'],
                            ['judgeCreativity', 'CREATIVITY'],
                            ['judgeDiff',       'DIFFICULTY'],
                        ] as [$prop,$lbl])
                            <div style="margin-bottom:18px;">
                                <div class="between" style="margin-bottom:7px;">
                                    <span class="mono" style="font-size:11px;letter-spacing:0.12em;">{{ $lbl }}</span>
                                    <span class="display tnum" style="font-size:20px;color:var(--lime);">{{ number_format($this->$prop, 1) }}</span>
                                </div>
                                <input type="range" min="0" max="10" step="0.1" wire:model.live="{{ $prop }}"
                                    style="width:100%;accent-color:var(--lime);" />
                            </div>
                        @endforeach
                    </div>
                    <div class="panel center col halftone" style="padding:22px;gap:8px;text-align:center;">
                        <span class="kicker">FINAL SCORE</span>
                        @php $total = (($judgeExec + $judgeStyle + $judgeCreativity + $judgeDiff) / 4) * 10; @endphp
                        <span class="display tnum text-glow-lime" style="font-size:clamp(70px,12vw,128px);color:var(--lime);line-height:0.8;">{{ number_format($total, 1) }}</span>
                        <span class="mono dim" style="font-size:12px;">/ 100 · AVG OF 4 CRITERIA</span>
                        @if($scoreSubmitted)
                            <span class="badge badge-lime" style="margin-top:18px;">✓ SCORE SUBMITTED</span>
                        @else
                            <button wire:click="submitScore" class="btn btn-lime" style="margin-top:18px;">Submit Score →</button>
                        @endif
                        <a href="{{ route('live') }}" class="btn btn-ghost btn-sm">View Live Board</a>
                    </div>
                </div>
            @endif

            {{-- ── BRACKETS ── --}}
            @if($view === 'brackets')
                <div class="panel center col" style="padding:50px;gap:16px;text-align:center;">
                    <span style="font-size:44px;">⊟</span>
                    <h3 class="display" style="font-size:30px;">Bracket Manager</h3>
                    <p class="dim" style="max-width:380px;">Seed riders, advance winners, and publish live brackets to the public site.</p>
                    <a href="{{ route('bracket') }}" class="btn btn-lime">Open Live Bracket →</a>
                </div>
            @endif

        </div>
    </div>
</div>

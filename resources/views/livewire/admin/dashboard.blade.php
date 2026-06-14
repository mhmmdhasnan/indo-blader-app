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
                ['categories',     '⊞', 'Categories'],
                ['qualification',  '⚡', 'Qualification'],
                ['tricks',         '◈', 'Tricks'],
                ['submissions',    '▶', 'Submissions'],
                ['ranking_admin',  '▲', 'Rankings'],
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
                        'riders' => 'Riders', 'events' => 'Events', 'judging' => 'Judge Panel', 'brackets' => 'Brackets',
                        'categories' => 'Categories', 'qualification' => 'Qualification', 'tricks' => 'Tricks',
                        'submissions' => 'Submissions', 'ranking_admin' => 'Rankings',
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
                <div class="col" style="gap:16px;">
                    {{-- Create / Edit form --}}
                    @if($evEditing)
                        <div class="panel" style="padding:22px;border-left:3px solid var(--lime);">
                            <span class="kicker" style="display:block;margin-bottom:16px;">{{ $evId ? 'EDIT EVENT' : 'CREATE EVENT' }}</span>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="prof-grid">
                                <div class="col" style="gap:12px;">
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">TITLE *</span>
                                        <input wire:model.live="evTitle" type="text" class="input-field" style="width:100%;" placeholder="Indo Blader Nationals">
                                        @error('evTitle') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">EDITION</span>
                                            <input wire:model="evEdition" type="text" class="input-field" style="width:100%;" placeholder="Vol. 07">
                                        </div>
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">SLUG *</span>
                                            <input wire:model="evSlug" type="text" class="input-field" style="width:100%;" placeholder="nationals-2027">
                                            @error('evSlug') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">CITY *</span>
                                            <input wire:model="evCity" type="text" class="input-field" style="width:100%;" placeholder="Jakarta">
                                            @error('evCity') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">VENUE *</span>
                                            <input wire:model="evVenue" type="text" class="input-field" style="width:100%;" placeholder="Senayan Park">
                                            @error('evVenue') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">DATE *</span>
                                            <input wire:model="evDate" type="date" class="input-field" style="width:100%;">
                                            @error('evDate') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">DATE LABEL</span>
                                            <input wire:model="evDateLabel" type="text" class="input-field" style="width:100%;" placeholder="AUG 22–24, 2027">
                                        </div>
                                    </div>
                                </div>
                                <div class="col" style="gap:12px;">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">STATUS *</span>
                                            <select wire:model="evStatus" class="input-field" style="width:100%;">
                                                @foreach(['SOON','OPEN','CLOSING','FULL','LIVE','CLOSED'] as $s)
                                                    <option value="{{ $s }}">{{ $s }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">SLOTS *</span>
                                            <input wire:model="evSlots" type="number" min="1" class="input-field" style="width:100%;">
                                            @error('evSlots') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">PRIZE (IDR) *</span>
                                        <input wire:model="evPrize" type="number" min="0" class="input-field" style="width:100%;" placeholder="5000000">
                                        @error('evPrize') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:8px;">CATEGORIES</span>
                                        <div class="flex gap-s" style="flex-wrap:wrap;">
                                            @foreach(['STREET','PARK','VERT','FLAT'] as $cat)
                                                <label class="flex label" style="gap:6px;align-items:center;font-size:12px;cursor:pointer;">
                                                    <input type="checkbox" wire:model="evCategories" value="{{ $cat }}" style="accent-color:var(--lime);">
                                                    {{ $cat }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">DESCRIPTION</span>
                                        <textarea wire:model="evBlurb" rows="3" class="input-field" style="width:100%;resize:vertical;" placeholder="Event description..."></textarea>
                                    </div>
                                    <label class="flex label" style="gap:8px;align-items:center;font-size:12px;cursor:pointer;">
                                        <input type="checkbox" wire:model="evFeatured" style="accent-color:var(--lime);">
                                        Featured event (tampil di homepage)
                                    </label>
                                </div>
                            </div>
                            <div class="flex gap-s" style="margin-top:18px;">
                                <button wire:click="saveEvent" class="btn btn-lime">{{ $evId ? '✓ Update Event' : '+ Create Event' }}</button>
                                <button wire:click="cancelEvent" class="btn btn-ghost">Cancel</button>
                            </div>
                        </div>
                    @else
                        <div class="between">
                            <span class="kicker">{{ $events->count() }} EVENTS</span>
                            <button wire:click="openCreateEvent" class="btn btn-lime btn-sm">+ New Event</button>
                        </div>
                    @endif

                    {{-- Event list --}}
                    @foreach($events as $ev)
                        <div class="panel" style="padding:18px;">
                            <div class="between" style="flex-wrap:wrap;gap:10px;">
                                <div class="col" style="gap:4px;">
                                    <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;">
                                        <x-status-badge :status="$ev->status" />
                                        <span class="mono dim" style="font-size:10px;">{{ $ev->edition }}</span>
                                        @if($ev->featured) <span class="badge badge-lime" style="font-size:9px;">FEATURED</span> @endif
                                    </div>
                                    <h3 class="display" style="font-size:22px;margin-top:4px;">{{ $ev->title }}</h3>
                                    <span class="mono dim" style="font-size:11px;">{{ $ev->date_label }} · {{ $ev->venue }}, {{ $ev->city }}</span>
                                    <span class="mono dim" style="font-size:10px;">{{ $ev->prize_formatted }} prize · {{ $ev->filled }}/{{ $ev->slots }} filled</span>
                                </div>
                                <div class="flex gap-s" style="align-items:center;">
                                    <a href="{{ route('events.show', $ev->slug) }}" class="btn btn-sm btn-ghost">View →</a>
                                    <button wire:click="openEditEvent({{ $ev->id }})" class="btn btn-sm btn-ghost">Edit</button>
                                    <button wire:click="deleteEvent({{ $ev->id }})" class="btn btn-sm btn-ghost"
                                        wire:confirm="Yakin hapus event '{{ $ev->title }}'? Semua registrasi terkait akan terpengaruh."
                                        style="color:var(--red);">Delete</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ── JUDGING ── --}}
            @if($view === 'judging')
                @include('livewire.partials.judging-panel', [
                    'events'          => $events,
                    'judgeEventId'    => $judgeEventId,
                    'scoringMode'     => $scoringMode,
                    'koMatchType'     => $koMatchType,
                    'koMatchId'       => $koMatchId,
                    'koCurrentMatch'  => $koCurrentMatch ?? null,
                    'koMatches'       => $koMatches ?? collect(),
                    'judgeRiders'     => $judgeRiders ?? collect(),
                    'liveRiderId'     => $liveRiderId,
                    'liveRunNumber'   => $liveRunNumber,
                    'judgeExec'       => $judgeExec,
                    'judgeStyle'      => $judgeStyle,
                    'judgeCreativity' => $judgeCreativity,
                    'judgeDiff'       => $judgeDiff,
                    'judgeConsistency'=> $judgeConsistency,
                    'scoreSubmitted'  => $scoreSubmitted,
                ])
            @endif

            {{-- ── BRACKETS ── --}}
            @if($view === 'brackets')
                <div class="col" style="gap:20px;">
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="margin-bottom:14px;display:block;">GENERATE BRACKET</span>
                        <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                            <div class="col" style="gap:6px;flex:1;min-width:200px;">
                                <span class="mono dim" style="font-size:10px;">EVENT</span>
                                <select wire:model="selectedEventId" class="input-field" style="width:100%;">
                                    <option value="0">— select event —</option>
                                    @foreach($events as $ev)
                                        <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:6px;flex:1;min-width:200px;">
                                <span class="mono dim" style="font-size:10px;">TYPE</span>
                                <select wire:model="bracketType" class="input-field" style="width:100%;">
                                    <option value="SINGLE_ELIMINATION">Single Elimination</option>
                                    <option value="DOUBLE_ELIMINATION">Double Elimination</option>
                                </select>
                            </div>
                            <button wire:click="generateBracket({{ $selectedEventId }})" class="btn btn-lime" @if(!$selectedEventId) disabled @endif>Generate Bracket</button>
                        </div>
                    </div>

                    @if(isset($brackets) && $brackets->count())
                        @foreach($brackets as $bracket)
                            <div class="panel" style="padding:20px;">
                                <div class="between" style="margin-bottom:16px;">
                                    <div class="col">
                                        <span class="label" style="font-size:16px;">{{ $bracket->event->title }}</span>
                                        <span class="mono dim" style="font-size:10px;">{{ $bracket->type }} · {{ $bracket->status }}</span>
                                    </div>
                                    <div class="flex gap-s">
                                        <a href="{{ route('bracket', $bracket->event->slug) }}" class="btn btn-sm btn-ghost">View Public →</a>
                                        @if($bracket->status !== 'COMPLETED')
                                            <button wire:click="completeBracket({{ $bracket->id }})" class="btn btn-sm btn-lime">Complete & Award Points</button>
                                        @else
                                            <span class="badge badge-lime">COMPLETED</span>
                                        @endif
                                        <button wire:click="deleteBracket({{ $bracket->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);" wire:confirm="Hapus bracket ini dan semua match-nya?">Hapus</button>
                                    </div>
                                </div>
                                @php $matchesByRound = $bracket->bracketMatches->groupBy('round'); @endphp
                                @foreach($matchesByRound as $round => $matches)
                                    <div style="margin-bottom:14px;">
                                        <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;display:block;margin-bottom:8px;">{{ $round }}</span>
                                        @foreach($matches as $match)
                                            <div class="panel" style="padding:12px;margin-bottom:6px;">
                                                <div class="between" style="flex-wrap:wrap;gap:8px;">
                                                    <div class="flex gap-m" style="align-items:center;flex-wrap:wrap;">
                                                        <span class="label" style="font-size:13px;">{{ $match->riderA?->name ?? 'TBD' }}</span>
                                                        <span class="mono dim" style="font-size:11px;">vs</span>
                                                        <span class="label" style="font-size:13px;">{{ $match->riderB?->name ?? 'TBD' }}</span>
                                                        @if($match->trick) <span class="badge badge-out" style="font-size:10px;">{{ $match->trick->name }}</span> @endif
                                                        @if($match->winner_registration_id)
                                                            <span class="badge badge-lime">Winner: {{ $match->winner?->name }}</span>
                                                        @endif
                                                    </div>
                                                                <div class="flex gap-s" style="flex-wrap:wrap;" x-data="{ trickId: {{ $match->trick_id ?? 0 }} }">
                                                        {{-- Assign trick (Alpine local state — isolated per match) --}}
                                                        @if(isset($tricks) && $tricks->count())
                                                            <select x-model="trickId" class="input-field" style="font-size:11px;padding:4px 8px;">
                                                                <option value="0">Assign trick…</option>
                                                                @foreach($tricks as $t)
                                                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->difficulty }})</option>
                                                                @endforeach
                                                            </select>
                                                            <button x-show="trickId > 0" @click="$wire.assignTrickToBracketMatch({{ $match->id }}, trickId)" class="btn btn-sm btn-ghost" style="font-size:11px;">Assign</button>
                                                        @endif
                                                        {{-- Advance winner --}}
                                                        @if($match->status === 'PENDING' && $match->rider_a_registration_id && $match->rider_b_registration_id)
                                                            <button wire:click="advanceBracketWinner({{ $match->id }}, {{ $match->rider_a_registration_id }})" class="btn btn-sm btn-ghost">{{ $match->riderA?->name }} Wins</button>
                                                            <button wire:click="advanceBracketWinner({{ $match->id }}, {{ $match->rider_b_registration_id }})" class="btn btn-sm btn-ghost">{{ $match->riderB?->name }} Wins</button>
                                                        @endif
                                                        <button wire:click="deleteBracketMatch({{ $match->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Hapus bracket match ini?">Hapus</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="panel center col" style="padding:40px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">⊟</span>
                            <p class="dim">No brackets generated yet. Select an event above to generate one.</p>
                            <a href="{{ route('bracket') }}" class="btn btn-ghost btn-sm">View Public Bracket</a>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── CATEGORIES ── --}}
            @if($view === 'categories')
                <div class="col" style="gap:16px;">
                    @if(isset($pendingCategoryAssignments) && $pendingCategoryAssignments->count())
                        <div class="panel" style="overflow:hidden;overflow-x:auto;">
                            <div style="padding:12px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                                <span class="label">PENDING CATEGORY ASSIGNMENTS ({{ $pendingCategoryAssignments->count() }})</span>
                            </div>
                            @foreach($pendingCategoryAssignments as $rc)
                                <div style="padding:16px 18px;border-bottom:1px solid var(--line);">
                                    <div class="between" style="margin-bottom:10px;">
                                        <div class="col" style="gap:3px;">
                                            <span class="label" style="font-size:14px;">{{ $rc->registration->name }}</span>
                                            <span class="mono dim" style="font-size:10px;">{{ $rc->registration->event?->title }} · {{ $rc->registration->entry_code }}</span>
                                        </div>
                                        <span class="badge badge-out">{{ $rc->category->name }}</span>
                                    </div>
                                    <div class="flex gap-s" style="flex-wrap:wrap;align-items:center;">
                                        <button wire:click="approveCategoryAssignment({{ $rc->id }})" class="btn btn-sm btn-lime">Approve</button>
                                        <button wire:click="rejectCategoryAssignment({{ $rc->id }})" class="btn btn-sm btn-ghost">Reject</button>
                                        <select wire:model.live="moveToCategoryId" class="input-field" style="font-size:12px;padding:6px 10px;">
                                            <option value="0">Move to…</option>
                                            @foreach($allCategories as $cat)
                                                @if($cat->id !== $rc->category_id)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @if($moveToCategoryId)
                                            <button wire:click="moveCategoryAssignment({{ $rc->id }})" class="btn btn-sm btn-ghost">Confirm Move</button>
                                        @endif
                                        <input type="text" wire:model="categoryNotes" placeholder="Notes (optional)" class="input-field" style="font-size:12px;flex:1;min-width:180px;" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">⊞</span>
                            <p class="dim">No pending category assignments.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── QUALIFICATION ── --}}
            @if($view === 'qualification')
                <div class="col" style="gap:16px;">
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="margin-bottom:14px;display:block;">CREATE ROUND</span>
                        <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                            <div class="col" style="gap:6px;flex:1;min-width:180px;">
                                <span class="mono dim" style="font-size:10px;">EVENT</span>
                                <select wire:model.live="selectedEventId" class="input-field" style="width:100%;">
                                    <option value="0">— select event —</option>
                                    @foreach($events as $ev)
                                        <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:6px;flex:1;min-width:180px;">
                                <span class="mono dim" style="font-size:10px;">ROUND NAME</span>
                                <input type="text" wire:model.live="newRoundName" placeholder="e.g. Round 1 Qualifiers" class="input-field" style="width:100%;" />
                            </div>
                            <button wire:click="createQualificationRound" class="btn btn-lime" @if(!$selectedEventId || !$newRoundName) disabled @endif>+ Add Round</button>
                        </div>
                    </div>

                    @if(isset($qualificationRounds) && $qualificationRounds->count())
                        @foreach($qualificationRounds as $round)
                            <div class="panel" style="overflow:hidden;">
                                <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);" class="between">
                                    <div class="col" style="gap:3px;">
                                        <span class="label" style="font-size:15px;">{{ $round->name }}</span>
                                        <span class="mono dim" style="font-size:10px;">{{ $round->event->title }} · {{ $round->pairing_type }} · {{ $round->status }}</span>
                                    </div>
                                    <div class="flex gap-s">
                                        <button wire:click="editQualRound({{ $round->id }})" class="btn btn-sm btn-ghost">Edit</button>
                                        <button wire:click="deleteQualRound({{ $round->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);" wire:confirm="Hapus round '{{ $round->name }}' dan semua match-nya?">Hapus</button>
                                        <button wire:click="randomizePairings({{ $round->id }})" class="btn btn-sm btn-ghost">⚡ Randomize</button>
                                    </div>
                                </div>

                                {{-- Inline edit form --}}
                                @if($editQualRoundId === $round->id)
                                    <div style="padding:12px 18px;border-bottom:2px solid var(--lime);background:color-mix(in srgb,var(--lime) 6%,transparent);">
                                        <div class="flex gap-s" style="flex-wrap:wrap;align-items:center;">
                                            <input type="text" wire:model.live="editQualRoundName" class="input-field" style="flex:1;min-width:160px;" placeholder="Nama round">
                                            <select wire:model.live="editQualPairing" class="input-field" style="font-size:12px;">
                                                <option value="MANUAL">Manual</option>
                                                <option value="RANDOM">Random</option>
                                            </select>
                                            <button wire:click="updateQualRound" class="btn btn-sm btn-lime" @if(!$editQualRoundName) disabled @endif>Simpan</button>
                                            <button wire:click="cancelEditQualRound" class="btn btn-sm btn-ghost">Batal</button>
                                        </div>
                                        @error('editQualRoundName') <p style="color:var(--red);font-size:11px;margin-top:4px;">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                {{-- Manual pairing form --}}
                                @if(isset($approvedRegistrations) && $approvedRegistrations->count())
                                    <div style="padding:12px 18px;border-bottom:1px solid var(--line);background:color-mix(in srgb,var(--bg-2) 50%,transparent);">
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:8px;">+ MANUAL PAIRING</span>
                                        <div class="flex gap-s" style="flex-wrap:wrap;align-items:center;">
                                            <select wire:model.live="manualRiderAId" class="input-field" style="font-size:12px;min-width:160px;">
                                                <option value="0">Rider A…</option>
                                                @foreach($approvedRegistrations as $reg)
                                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="mono dim" style="font-size:11px;">vs</span>
                                            <select wire:model.live="manualRiderBId" class="input-field" style="font-size:12px;min-width:160px;">
                                                <option value="0">Rider B…</option>
                                                @foreach($approvedRegistrations as $reg)
                                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                                @endforeach
                                            </select>
                                            <button wire:click="addManualPairing({{ $round->id }})" class="btn btn-sm btn-lime"
                                                @if(!$manualRiderAId || !$manualRiderBId) disabled @endif>Add Pair</button>
                                        </div>
                                        @error('manualRiderAId') <p style="color:var(--red);font-size:11px;margin-top:4px;">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                @if($round->qualificationMatches->count())
                                    @foreach($round->qualificationMatches as $match)
                                        <div style="padding:12px 18px;border-bottom:1px solid var(--line);" class="between">
                                            <div class="flex gap-m" style="align-items:center;flex-wrap:wrap;">
                                                <span class="label" style="font-size:13px;">{{ $match->riderA?->name ?? '—' }}</span>
                                                <span class="mono dim">vs</span>
                                                <span class="label" style="font-size:13px;">{{ $match->riderB?->name ?? '—' }}</span>
                                                @if($match->trick) <span class="badge badge-out" style="font-size:10px;">{{ $match->trick->name }}</span> @endif
                                                @if($match->winner_registration_id) <span class="badge badge-lime">Winner: {{ $match->winner?->name }}</span> @endif
                                            </div>
                                            <div class="flex gap-s">
                                                @if($match->status === 'PENDING')
                                                    @if($match->rider_a_registration_id)
                                                        <button wire:click="setQualMatchWinner({{ $match->id }}, {{ $match->rider_a_registration_id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">{{ $match->riderA?->name }} Wins</button>
                                                    @endif
                                                    @if($match->rider_b_registration_id)
                                                        <button wire:click="setQualMatchWinner({{ $match->id }}, {{ $match->rider_b_registration_id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">{{ $match->riderB?->name }} Wins</button>
                                                    @endif
                                                @endif
                                                <button wire:click="deleteQualMatch({{ $match->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Hapus match ini?">Hapus</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div style="padding:20px 18px;color:var(--ink-dim);font-size:13px;text-align:center;">No pairings yet — Randomize atau tambah manual di atas.</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">⚡</span>
                            <p class="dim">No qualification rounds yet. Select an event and create one above.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── TRICKS ── --}}
            @if($view === 'tricks')
                <div class="col" style="gap:16px;">
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="margin-bottom:14px;display:block;">ADD TRICK</span>
                        <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                            <div class="col" style="gap:6px;flex:1;min-width:160px;">
                                <span class="mono dim" style="font-size:10px;">NAME</span>
                                <input type="text" wire:model.live="trickName" placeholder="e.g. Fishbrain" class="input-field" style="width:100%;" />
                            </div>
                            <div class="col" style="gap:6px;min-width:140px;">
                                <span class="mono dim" style="font-size:10px;">DIFFICULTY</span>
                                <select wire:model="trickDifficulty" class="input-field">
                                    @foreach(['Easy','Medium','Hard','Expert'] as $d)
                                        <option>{{ $d }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:6px;flex:2;min-width:200px;">
                                <span class="mono dim" style="font-size:10px;">DESCRIPTION</span>
                                <input type="text" wire:model="trickDescription" placeholder="Optional description" class="input-field" style="width:100%;" />
                            </div>
                            <button wire:click="createTrick" class="btn btn-lime" @if(!$trickName) disabled @endif>+ Add</button>
                        </div>
                    </div>

                    @if(isset($tricks) && $tricks->count())
                        <div class="panel" style="overflow:hidden;overflow-x:auto;">
                            <div style="display:grid;grid-template-columns:1fr 120px 1fr 80px;padding:12px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);min-width:500px;">
                                @foreach(['TRICK','DIFFICULTY','DESCRIPTION','STATUS'] as $h)
                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $h }}</span>
                                @endforeach
                            </div>
                            @foreach($tricks as $trick)
                                <div style="display:grid;grid-template-columns:1fr 120px 1fr 80px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line);min-width:500px;">
                                    <span class="label" style="font-size:13px;">{{ $trick->name }}</span>
                                    <span class="badge badge-{{ match($trick->difficulty){ 'Easy'=>'lime','Medium'=>'out','Hard'=>'red','Expert'=>'red',default=>'out' } }}">{{ $trick->difficulty }}</span>
                                    <span class="dim" style="font-size:12px;">{{ $trick->description ?? '—' }}</span>
                                    <button wire:click="toggleTrickActive({{ $trick->id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">
                                        {{ $trick->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">◈</span>
                            <p class="dim">No tricks yet. Add one above.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── SUBMISSIONS ── --}}
            @if($view === 'submissions')
                <div class="col" style="gap:16px;">
                    @if(isset($pendingSubmissions) && $pendingSubmissions->count())
                        @foreach($pendingSubmissions as $sub)
                            <div class="panel" style="padding:18px;">
                                <div class="between" style="margin-bottom:12px;">
                                    <div class="col" style="gap:4px;">
                                        <span class="label" style="font-size:14px;">{{ $sub->registration->name }}</span>
                                        <span class="mono dim" style="font-size:10px;">{{ strtoupper($sub->match_type) }} match #{{ $sub->match_id }} · {{ $sub->created_at->diffForHumans() }}</span>
                                    </div>
                                    <span class="badge badge-red">PENDING</span>
                                </div>
                                @php
                                    $igUrl = $sub->video_path ?? '';
                                    preg_match('#instagram\.com/(p|reel)/([A-Za-z0-9_-]+)#', $igUrl, $igm);
                                @endphp
                                @if(!empty($igm[2]))
                                    <div style="margin-bottom:12px;">
                                        <iframe src="https://www.instagram.com/p/{{ $igm[2] }}/embed/"
                                            width="320" height="380"
                                            style="border:none;border-radius:4px;background:#000;display:block;"
                                            allowfullscreen scrolling="no" frameborder="0"></iframe>
                                        <a href="{{ $igUrl }}" target="_blank" class="btn btn-sm btn-ghost" style="margin-top:6px;">Buka di Instagram ↗</a>
                                    </div>
                                @elseif($igUrl)
                                    <div style="margin-bottom:12px;">
                                        <a href="{{ $igUrl }}" target="_blank" class="btn btn-sm btn-ghost">▶ View Video</a>
                                    </div>
                                @endif
                                <div class="flex gap-s" style="flex-wrap:wrap;align-items:flex-end;">
                                    <input type="text" wire:model="submissionFeedback" placeholder="Judge feedback (optional)" class="input-field" style="flex:1;min-width:200px;font-size:12px;" />
                                    <button wire:click="approveSubmission({{ $sub->id }})" class="btn btn-sm btn-lime">Approve</button>
                                    <button wire:click="rejectSubmission({{ $sub->id }})" class="btn btn-sm btn-ghost">Reject</button>
                                    <button wire:click="requestReupload({{ $sub->id }})" class="btn btn-sm btn-ghost">Need Re-upload</button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">▶</span>
                            <p class="dim">No pending video submissions.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── RANKINGS ── --}}
            @if($view === 'ranking_admin')
                <div class="col" style="gap:16px;">
                    <div class="between">
                        <span class="kicker">NATIONAL RANKINGS</span>
                        <button wire:click="recalculateRankings" class="btn btn-sm btn-ghost">↺ Recalculate</button>
                    </div>
                    @if(isset($rankings) && $rankings->count())
                        <div class="panel" style="overflow:hidden;overflow-x:auto;">
                            <div style="display:grid;grid-template-columns:60px 1fr 1fr 120px;padding:12px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);min-width:400px;">
                                @foreach(['RANK','RIDER','CITY','POINTS'] as $h)
                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $h }}</span>
                                @endforeach
                            </div>
                            @foreach($rankings as $rank)
                                <div style="display:grid;grid-template-columns:60px 1fr 1fr 120px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line);min-width:400px;">
                                    <span class="display tnum" style="font-size:22px;color:{{ $rank->national_rank <= 3 ? 'var(--lime)' : 'var(--ink-dim)' }};">#{{ $rank->national_rank }}</span>
                                    <div class="flex" style="align-items:center;gap:10px;">
                                        <x-avatar :initials="$rank->rider->initials" :size="32" />
                                        <span class="label" style="font-size:13px;">{{ $rank->rider->name }}</span>
                                    </div>
                                    <span class="dim" style="font-size:12px;">{{ $rank->rider->city }}</span>
                                    <span class="display tnum" style="font-size:18px;">{{ number_format($rank->total_points) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">▲</span>
                            <p class="dim">No ranking data yet. Rankings are generated automatically when a bracket is completed.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>

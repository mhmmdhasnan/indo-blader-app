<div class="admin-root">
    {{-- ── SIDEBAR ── --}}
    <aside class="admin-side" style="border-right:2px solid var(--ink);background:var(--bg-2);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;">
        <div style="padding:20px 18px;border-bottom:2px solid var(--ink);">
            <div style="display:flex;align-items:center;gap:10px;">
                <img src="{{ asset('images/logo.png') }}" alt="Indo Blader" style="width:36px;height:36px;flex-shrink:0;">
                <div class="col" style="line-height:0.9;">
                    <span class="display" style="font-size:16px;">Indo Blader</span>
                    <span class="mono" style="font-size:9px;letter-spacing:0.2em;color:var(--ink-dim);">AGGRESSIVE INLINE · ID</span>
                </div>
            </div>
            <span class="badge badge-lime" style="margin-top:12px;font-size:9px;">ADMIN CONSOLE</span>
        </div>

        {{-- ── GLOBAL EVENT SELECTOR ── --}}
        <div style="padding:12px 14px;border-bottom:2px solid var(--ink);background:var(--bg);">
            <span class="mono" style="font-size:9px;letter-spacing:0.16em;color:var(--ink-dim);display:block;margin-bottom:6px;">ACTIVE EVENT</span>
            <select wire:model.live="activeEventId" style="
                width:100%;padding:8px 10px;background:var(--bg-2);
                border:2px solid var(--lime);border-radius:3px;
                color:var(--ink);font-family:inherit;font-size:12px;outline:none;
            ">
                <option value="0">— pilih event —</option>
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                @endforeach
            </select>
            @if(isset($activeEvent) && $activeEvent)
                <div class="flex" style="align-items:center;gap:6px;margin-top:6px;">
                    <span class="badge badge-{{ $activeEvent->status === 'LIVE' ? 'lime' : ($activeEvent->status === 'DONE' ? 'out' : 'red') }}" style="font-size:8px;">
                        {{ $activeEvent->status }}
                    </span>
                    <span class="mono dim" style="font-size:9px;">{{ $activeEvent->date?->format('d M Y') }}</span>
                </div>
            @endif
        </div>

        <nav class="col" style="padding:10px 10px;gap:0;flex:1;overflow-y:auto;">
            @php
            $navGroups = [
                'MAIN' => [
                    ['overview', '◧', 'Overview'],
                ],
                'PENDAFTARAN' => [
                    ['registrations', '✓', 'Registrations'],
                    ['payments',      '₨', 'Payments'],
                    ['riders',        '◉', 'Riders'],
                ],
                'EVENT' => [
                    ['events', '◆', 'Events'],
                ],
                'KOMPETISI' => [
                    ['qualification', '⚡', 'Qualification'],
                    ['brackets',      '⊟', 'Brackets'],
                    ['judging',       '★', 'Judge Panel'],
                    ['submissions',   '▶', 'Submissions'],
                ],
                'KONFIGURASI' => [
                    ['categories',    '⊞', 'Categories'],
                    ['tricks',        '◈', 'Tricks'],
                    ['scoring',       '⊙', 'Scoring Setup'],
                    ['ranking_admin', '▲', 'Rankings'],
                    ['users',         '👤', 'Users'],
                ],
            ];
            @endphp

            @foreach($navGroups as $groupLabel => $items)
                <div style="margin-top:6px;">
                    <span class="mono" style="
                        display:block;padding:6px 10px 4px;
                        font-size:8px;letter-spacing:0.18em;
                        color:var(--ink-dim);opacity:0.55;
                    ">{{ $groupLabel }}</span>
                    @foreach($items as [$k, $ic, $lbl])
                        <button wire:click="$set('view','{{ $k }}')" class="flex label" style="
                            align-items:center;gap:10px;padding:9px 10px;border-radius:3px;
                            font-size:12.5px;text-align:left;width:100%;
                            background:{{ $view === $k ? 'var(--ink)' : 'transparent' }};
                            color:{{ $view === $k ? 'var(--bg)' : 'var(--ink-dim)' }};
                            transition:background .15s,color .15s;
                        "
                        onmouseover="if('{{ $view }}' !== '{{ $k }}') this.style.background='color-mix(in srgb,var(--ink) 10%,transparent)'"
                        onmouseout="if('{{ $view }}' !== '{{ $k }}') this.style.background='transparent'">
                            <span style="font-size:14px;width:16px;text-align:center;opacity:{{ $view === $k ? '1' : '0.7' }};">{{ $ic }}</span>
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
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
                <span class="kicker">{{ isset($activeEvent) && $activeEvent ? strtoupper($activeEvent->title) : 'INDO BLADER' }}</span>
                <h1 class="display" style="font-size:26px;">
                    {{ collect([
                        'overview' => 'Overview', 'registrations' => 'Registrations', 'payments' => 'Payments',
                        'riders' => 'Riders', 'events' => 'Events', 'judging' => 'Judge Panel', 'brackets' => 'Brackets',
                        'categories' => 'Categories', 'qualification' => 'Qualification', 'tricks' => 'Tricks',
                        'submissions' => 'Submissions', 'ranking_admin' => 'Rankings', 'users' => 'Users',
                    ])->get($view, 'Overview') }}
                </h1>
            </div>
            <div class="flex" style="align-items:center;gap:14px;">
                <span class="badge badge-out"><span class="live-dot"></span>SYSTEM LIVE</span>
                <div class="flex adm-head-user" style="align-items:center;gap:10px;">
                    <x-avatar :initials="collect(explode(' ', auth()->user()->name))->map(fn($w)=>strtoupper($w[0]))->take(2)->implode('')" :size="36" />
                    <div class="col">
                        <span class="label" style="font-size:13px;white-space:nowrap;">{{ auth()->user()->name }}</span>
                        <span class="mono dim" style="font-size:10px;">{{ strtoupper(auth()->user()->role) }}</span>
                    </div>
                </div>
            </div>
        </header>

        <div style="padding:26px;flex:1;">

            {{-- ── OVERVIEW ── --}}
            @if($view === 'overview')
                <div class="col" style="gap:20px;">
                    <div class="adm-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">
                        @php $eventLabel = isset($activeEvent) && $activeEvent ? $activeEvent->title : 'all events'; @endphp
                        @foreach([
                            ['TOTAL REGISTRATIONS', $registrations->count(), $eventLabel, 'var(--lime)'],
                            ['PENDING APPROVAL', $registrations->where('status','PENDING')->count(), 'needs review', 'var(--red)'],
                            ['REVENUE (EST IDR)', 'Rp ' . number_format($revenue,0,',','.'), $eventLabel, null],
                            ['RIDERS TERDAFTAR', $riders->count(), $eventLabel, null],
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
                                <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;">
                                    @if($reg->status === 'PENDING')
                                        @if($reg->payment_status !== 'VERIFIED')
                                            <span class="mono dim" style="font-size:10px;color:var(--red);">⚠ payment belum verify</span>
                                        @endif
                                        <button wire:click="approveRegistration({{ $reg->id }})"
                                            class="btn btn-sm btn-lime" style="padding:6px 12px;"
                                            @if($reg->payment_status !== 'VERIFIED') disabled title="Verify payment dulu" @endif>
                                            Approve
                                        </button>
                                        <button wire:click="rejectRegistration({{ $reg->id }})"
                                            wire:confirm="Reject registrasi {{ $reg->name }}?"
                                            class="btn btn-sm btn-ghost" style="padding:6px 12px;">Reject</button>
                                    @else
                                        <span class="badge badge-{{ $reg->status_variant }}" style="font-size:9px;">{{ $reg->status }}</span>
                                        <button wire:click="pendingRegistration({{ $reg->id }})" class="mono dim" style="font-size:11px;text-decoration:underline;">undo</button>
                                    @endif
                                    @error('registration_' . $reg->id)
                                        <span style="font-size:10px;color:var(--red);">{{ $message }}</span>
                                    @enderror
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
                            <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;">
                                @if($reg->payment_proof)
                                    <a href="{{ asset('storage/' . $reg->payment_proof) }}"
                                       target="_blank"
                                       x-data
                                       @click.prevent="
                                           const m = document.getElementById('proof-modal-{{ $reg->id }}');
                                           m.style.display = 'flex';
                                       "
                                       class="btn btn-sm btn-ghost" style="font-size:11px;">📷 Lihat Transfer</a>

                                    {{-- Proof modal --}}
                                    <div id="proof-modal-{{ $reg->id }}"
                                         style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);align-items:center;justify-content:center;padding:20px;"
                                         @click.self="$el.style.display='none'">
                                        <div style="position:relative;max-width:90vw;max-height:90vh;background:var(--bg);border:2px solid var(--ink);border-radius:4px;overflow:hidden;">
                                            <div class="between" style="padding:12px 16px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                                                <span class="label" style="font-size:13px;">Bukti Transfer — {{ $reg->name }}</span>
                                                <div class="flex gap-s">
                                                    <a href="{{ asset('storage/' . $reg->payment_proof) }}" target="_blank" class="btn btn-sm btn-ghost" style="font-size:11px;">Buka ↗</a>
                                                    <button @click="document.getElementById('proof-modal-{{ $reg->id }}').style.display='none'" class="btn btn-sm btn-ghost" style="font-size:11px;">✕ Tutup</button>
                                                </div>
                                            </div>
                                            <div style="padding:16px;overflow:auto;max-height:75vh;text-align:center;">
                                                <img src="{{ asset('storage/' . $reg->payment_proof) }}"
                                                     alt="Bukti transfer"
                                                     style="max-width:100%;max-height:65vh;object-fit:contain;border-radius:2px;">
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="mono dim" style="font-size:10px;">— belum upload</span>
                                @endif
                                @if($reg->payment_status === 'PENDING')
                                    <button wire:click="verifyPayment({{ $reg->id }})" class="btn btn-sm btn-lime" style="padding:6px 12px;">Verify</button>
                                    <button wire:click="rejectPayment({{ $reg->id }})"
                                        wire:confirm="Tolak payment {{ $reg->name }}? Status akan kembali ke UNPAID."
                                        class="btn btn-sm btn-ghost" style="padding:6px 12px;color:var(--red);">Reject</button>
                                @elseif($reg->payment_status === 'VERIFIED')
                                    <button wire:click="rejectPayment({{ $reg->id }})"
                                        wire:confirm="Batalkan verifikasi payment {{ $reg->name }}?"
                                        class="mono dim" style="font-size:11px;text-decoration:underline;">undo verify</button>
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
                    'events'           => $events,
                    'judgeEventId'     => $judgeEventId,
                    'scoringMode'      => $scoringMode,
                    'koMatchType'      => $koMatchType,
                    'koMatchId'        => $koMatchId,
                    'koCurrentMatch'   => $koCurrentMatch ?? null,
                    'koMatches'        => $koMatches ?? collect(),
                    'koApprovedSubmissions' => $koApprovedSubmissions ?? collect(),
                    'judgeRiders'      => $judgeRiders ?? collect(),
                    'liveRiderId'      => $liveRiderId,
                    'liveRunNumber'    => $liveRunNumber,
                    'criteriaScores'   => $criteriaScores,
                    'criteriaScoresB'  => $criteriaScoresB,
                    'scoreSubmitted'   => $scoreSubmitted,
                    'eventCriteria'         => $eventCriteria ?? collect(),
                    'judgeAssignment'       => $judgeAssignment ?? null,
                    'otherJudgeScores'      => $otherJudgeScores ?? collect(),
                    'koOtherJudgeScoresA'   => $koOtherJudgeScoresA ?? collect(),
                    'koOtherJudgeScoresB'   => $koOtherJudgeScoresB ?? collect(),
                ])
            @endif

            {{-- ── BRACKETS ── --}}
            @if($view === 'brackets')
                <div class="col" style="gap:20px;">
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="margin-bottom:14px;display:block;">SETUP BRACKET</span>

                        {{-- Row 1: event + type + mode --}}
                        <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;margin-bottom:14px;">
                            <div class="col" style="gap:6px;flex:1;min-width:200px;">
                                <span class="mono dim" style="font-size:10px;">EVENT</span>
                                <select wire:model.live="selectedEventId" class="input-field" style="width:100%;">
                                    <option value="0">— select event —</option>
                                    @foreach($events as $ev)
                                        <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:6px;min-width:180px;">
                                <span class="mono dim" style="font-size:10px;">TYPE</span>
                                <select wire:model.live="bracketType" class="input-field" style="width:100%;">
                                    <option value="SINGLE_ELIMINATION">Single Elimination</option>
                                    <option value="DOUBLE_ELIMINATION">Double Elimination</option>
                                </select>
                            </div>
                            <div class="col" style="gap:6px;">
                                <span class="mono dim" style="font-size:10px;">MODE</span>
                                <div class="flex gap-s">
                                    <button wire:click="$set('bracketMode','auto')" class="btn btn-sm {{ $bracketMode === 'auto' ? 'btn-lime' : 'btn-ghost' }}">Auto</button>
                                    <button wire:click="$set('bracketMode','manual')" class="btn btn-sm {{ $bracketMode === 'manual' ? 'btn-lime' : 'btn-ghost' }}">Manual</button>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: mode-specific options + action --}}
                        @if($bracketMode === 'auto')
                            <div class="flex gap-m" style="align-items:flex-end;flex-wrap:wrap;">
                                @if($selectedEventId)
                                    @php $approvedCount = \App\Models\Registration::where('event_id', $selectedEventId)->where('status', 'APPROVED')->count(); @endphp
                                    <span class="mono dim" style="font-size:10px;align-self:center;">
                                        {{ $approvedCount }} peserta approved
                                        @if($approvedCount < 2)<span style="color:var(--red);"> · min. 2</span>@endif
                                    </span>
                                @endif
                                <button wire:click="generateBracket({{ $selectedEventId }})" class="btn btn-lime"
                                    @if(!$selectedEventId || (isset($approvedCount) && $approvedCount < 2)) disabled @endif>
                                    Generate Otomatis
                                </button>
                            </div>
                        @else
                            <div class="flex gap-m" style="align-items:flex-end;flex-wrap:wrap;">
                                <div class="col" style="gap:6px;">
                                    @if($bracketType === 'DOUBLE_ELIMINATION')
                                        <span class="mono dim" style="font-size:10px;">JUMLAH MATCH UB_R1 (UPPER BRACKET R1)</span>
                                        <select wire:model.live="manualQfCount" class="input-field">
                                            <option value="2">2 match UB_R1 → UB_R2 + LB_R1 + LB_R2 + LB_F + GF</option>
                                            <option value="4">4 match UB_R1 → full bracket (8 slot)</option>
                                            <option value="8">8 match UB_R1 → full bracket (16 slot)</option>
                                        </select>
                                    @else
                                        <span class="mono dim" style="font-size:10px;">JUMLAH MATCH BABAK PERTAMA</span>
                                        <select wire:model.live="manualQfCount" class="input-field">
                                            <option value="1">1 match → Final langsung</option>
                                            <option value="2">2 match → Semi Final + Final</option>
                                            <option value="4">4 match → QF + SF + Final</option>
                                            <option value="8">8 match → QF + SF + F + Grand Final (16 slot)</option>
                                        </select>
                                    @endif
                                </div>
                                <button wire:click="generateManualBracket({{ $selectedEventId }})" class="btn btn-lime"
                                    @if(!$selectedEventId) disabled @endif>
                                    Buat Struktur Kosong
                                </button>
                            </div>
                            <p class="mono dim" style="font-size:10px;margin-top:8px;">Setelah dibuat, assign rider ke tiap slot di bawah.</p>
                        @endif

                        @error('bracket') <p style="color:var(--red);font-size:11px;margin-top:8px;">{{ $message }}</p> @enderror
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
                                            @php
                                                $eventRegs = isset($eventRegistrations[$bracket->event_id])
                                                    ? $eventRegistrations[$bracket->event_id]
                                                    : \App\Models\Registration::where('event_id', $bracket->event_id)->where('status', 'APPROVED')->get();
                                            @endphp
                                            <div class="panel" style="padding:12px;margin-bottom:6px;" x-data="{
                                                trickId: {{ $match->trick_id ?? 0 }},
                                                regA: {{ $match->rider_a_registration_id ?? 0 }},
                                                regB: {{ $match->rider_b_registration_id ?? 0 }},
                                                editA: {{ $match->rider_a_registration_id ? 'false' : 'true' }},
                                                editB: {{ $match->rider_b_registration_id ? 'false' : 'true' }},
                                                deadline: '{{ $match->submission_deadline ? $match->submission_deadline->format('Y-m-d\TH:i') : '' }}'
                                            }">
                                                <div class="col" style="gap:10px;">
                                                    <div class="between" style="flex-wrap:wrap;gap:8px;">

                                                        {{-- Riders display / slot assignment --}}
                                                        <div class="flex gap-m" style="align-items:center;flex-wrap:wrap;">
                                                            {{-- Slot A --}}
                                                            <div class="flex gap-s" style="align-items:center;">
                                                                <template x-if="!editA">
                                                                    <div class="flex gap-s" style="align-items:center;">
                                                                        <span class="label" style="font-size:13px;">{{ $match->riderA?->name ?? '— Rider A —' }}</span>
                                                                        <button @click="editA = true" class="btn btn-sm btn-ghost" style="font-size:10px;padding:2px 6px;">Edit</button>
                                                                    </div>
                                                                </template>
                                                                <template x-if="editA">
                                                                    <div class="flex gap-s" style="align-items:center;">
                                                                        <select x-model="regA" class="input-field" style="font-size:11px;padding:4px 8px;min-width:140px;">
                                                                            <option value="0">— Rider A —</option>
                                                                            @foreach($eventRegs as $reg)
                                                                                <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <button x-show="regA > 0" @click="$wire.assignBracketSlot({{ $match->id }}, 'a', regA); editA = false" class="btn btn-sm btn-ghost" style="font-size:11px;">Set</button>
                                                                        @if($match->rider_a_registration_id)
                                                                            <button @click="editA = false" class="btn btn-sm btn-ghost" style="font-size:10px;padding:2px 6px;">Cancel</button>
                                                                        @endif
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            <span class="mono dim" style="font-size:11px;">vs</span>

                                                            {{-- Slot B --}}
                                                            <div class="flex gap-s" style="align-items:center;">
                                                                <template x-if="!editB">
                                                                    <div class="flex gap-s" style="align-items:center;">
                                                                        <span class="label" style="font-size:13px;">{{ $match->riderB?->name ?? '— Rider B —' }}</span>
                                                                        <button @click="editB = true" class="btn btn-sm btn-ghost" style="font-size:10px;padding:2px 6px;">Edit</button>
                                                                    </div>
                                                                </template>
                                                                <template x-if="editB">
                                                                    <div class="flex gap-s" style="align-items:center;">
                                                                        <select x-model="regB" class="input-field" style="font-size:11px;padding:4px 8px;min-width:140px;">
                                                                            <option value="0">— Rider B —</option>
                                                                            @foreach($eventRegs as $reg)
                                                                                <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <button x-show="regB > 0" @click="$wire.assignBracketSlot({{ $match->id }}, 'b', regB); editB = false" class="btn btn-sm btn-ghost" style="font-size:11px;">Set</button>
                                                                        @if($match->rider_b_registration_id)
                                                                            <button @click="editB = false" class="btn btn-sm btn-ghost" style="font-size:10px;padding:2px 6px;">Cancel</button>
                                                                        @endif
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            @if($match->trick) <span class="badge badge-out" style="font-size:10px;">{{ $match->trick->name }}</span> @endif
                                                            @if($match->winner_registration_id)
                                                                <span class="badge badge-lime">Winner: {{ $match->winner?->name }}</span>
                                                            @endif
                                                        </div>

                                                        <div class="flex gap-s" style="flex-wrap:wrap;">
                                                            {{-- Assign trick --}}
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

                                                    {{-- Deadline row --}}
                                                    <div class="flex gap-s" style="align-items:center;">
                                                        <span class="mono dim" style="font-size:10px;min-width:110px;">BATAS SUBMIT:</span>
                                                        <input type="datetime-local" x-model="deadline" class="input-field" style="font-size:11px;padding:4px 8px;max-width:200px;" />
                                                        <button @click="$wire.setMatchDeadline({{ $match->id }}, deadline)" class="btn btn-sm btn-ghost" style="font-size:11px;">Simpan</button>
                                                        @if($match->submission_deadline)
                                                            <span class="mono" style="font-size:10px;color:var(--lime);">{{ $match->submission_deadline->format('d M Y H:i') }}</span>
                                                        @endif
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
                                    <div class="flex gap-s" x-data="{ notes: '', moveTo: 0 }" style="flex-wrap:wrap;align-items:center;">
                                        <button @click="$wire.approveCategoryAssignment({{ $rc->id }}, notes)" class="btn btn-sm btn-lime">Approve</button>
                                        <button @click="$wire.rejectCategoryAssignment({{ $rc->id }}, notes)" class="btn btn-sm btn-ghost">Reject</button>
                                        <select x-model="moveTo" class="input-field" style="font-size:12px;padding:6px 10px;">
                                            <option value="0">Move to…</option>
                                            @foreach($allCategories as $cat)
                                                @if($cat->id !== $rc->category_id)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button x-show="moveTo > 0" @click="$wire.moveCategoryAssignment({{ $rc->id }}, moveTo, notes)" class="btn btn-sm btn-ghost">Confirm Move</button>
                                        <input type="text" x-model="notes" placeholder="Notes (optional)" class="input-field" style="font-size:12px;flex:1;min-width:180px;" />
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
                                    <div x-data="{ riderA: 0, riderB: 0 }" style="padding:12px 18px;border-bottom:1px solid var(--line);background:color-mix(in srgb,var(--bg-2) 50%,transparent);">
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:8px;">+ MANUAL PAIRING</span>
                                        <div class="flex gap-s" style="flex-wrap:wrap;align-items:center;">
                                            <select x-model="riderA" class="input-field" style="font-size:12px;min-width:160px;">
                                                <option value="0">Rider A…</option>
                                                @foreach($approvedRegistrations as $reg)
                                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="mono dim" style="font-size:11px;">vs</span>
                                            <select x-model="riderB" class="input-field" style="font-size:12px;min-width:160px;">
                                                <option value="0">Rider B…</option>
                                                @foreach($approvedRegistrations as $reg)
                                                    <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                                                @endforeach
                                            </select>
                                            <button @click="$wire.addManualPairing({{ $round->id }}, riderA, riderB); riderA = 0; riderB = 0;"
                                                :disabled="!riderA || !riderB" class="btn btn-sm btn-lime">Add Pair</button>
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
                                <div class="flex gap-s" x-data="{ feedback: '' }" style="flex-wrap:wrap;align-items:flex-end;">
                                    <input type="text" x-model="feedback" placeholder="Judge feedback (optional)" class="input-field" style="flex:1;min-width:200px;font-size:12px;" />
                                    <button wire:click="approveSubmission({{ $sub->id }})" class="btn btn-sm btn-lime">Approve</button>
                                    <button @click="$wire.rejectSubmission({{ $sub->id }}, feedback)" class="btn btn-sm btn-ghost">Reject</button>
                                    <button @click="$wire.requestReupload({{ $sub->id }}, feedback)" class="btn btn-sm btn-ghost">Need Re-upload</button>
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

            {{-- ── USERS ── --}}
            @if($view === 'users')
                <div class="col" style="gap:20px;">

                    {{-- Form create / edit --}}
                    @if($userEditing)
                        <div class="panel" style="padding:22px;border-left:3px solid var(--lime);">
                            <span class="kicker" style="display:block;margin-bottom:16px;">{{ $userId ? 'EDIT USER' : 'TAMBAH USER BARU' }}</span>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="prof-grid">
                                <div class="col" style="gap:14px;">
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">NAMA</span>
                                        <input wire:model="userName" type="text" placeholder="Nama lengkap" class="input-field" style="width:100%;">
                                        @error('userName') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">EMAIL</span>
                                        <input wire:model="userEmail" type="email" placeholder="email@example.com" class="input-field" style="width:100%;">
                                        @error('userEmail') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div class="col" style="gap:14px;">
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">ROLE</span>
                                        <select wire:model="userRole" class="input-field" style="width:100%;">
                                            <option value="rider">Rider</option>
                                            <option value="judge">Judge</option>
                                            <option value="head_judge">Head Judge</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                        @error('userRole') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:5px;">
                                            PASSWORD {{ $userId ? '(kosongkan jika tidak diubah)' : '' }}
                                        </span>
                                        <input wire:model="userPassword" type="password" placeholder="Min. 8 karakter" class="input-field" style="width:100%;">
                                        @error('userPassword') <p style="color:var(--red);font-size:11px;margin-top:3px;">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-s" style="margin-top:18px;">
                                <button wire:click="userSave" class="btn btn-lime">{{ $userId ? '✓ Simpan Perubahan' : '+ Buat User' }}</button>
                                <button wire:click="userCancel" class="btn btn-ghost">Batal</button>
                            </div>
                        </div>
                    @endif

                    {{-- Search + Add button --}}
                    <div class="between" style="flex-wrap:wrap;gap:10px;">
                        <div style="position:relative;flex:1;min-width:200px;max-width:340px;">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ink-dim);font-size:13px;">⌕</span>
                            <input wire:model.live="userSearch" type="text" placeholder="Cari nama atau email..."
                                style="width:100%;padding:10px 14px 10px 34px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:13px;outline:none;"
                                onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'">
                        </div>
                        @if(!$userEditing)
                            <button wire:click="userNew" class="btn btn-lime">+ User Baru</button>
                        @endif
                    </div>

                    {{-- User table --}}
                    @if(isset($users) && $users->count())
                        <div class="panel" style="overflow:hidden;overflow-x:auto;">
                            {{-- Header --}}
                            <div style="display:grid;grid-template-columns:2fr 2fr 130px 100px 120px;padding:12px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);min-width:640px;">
                                @foreach(['NAMA', 'EMAIL', 'ROLE', 'BERGABUNG', ''] as $h)
                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.12em;">{{ $h }}</span>
                                @endforeach
                            </div>
                            {{-- Rows --}}
                            @foreach($users as $u)
                                <div style="display:grid;grid-template-columns:2fr 2fr 130px 100px 120px;align-items:center;padding:12px 18px;border-bottom:1px solid var(--line);min-width:640px;">
                                    <div class="flex" style="align-items:center;gap:10px;">
                                        <x-avatar
                                            :initials="collect(explode(' ', $u->name))->map(fn($w)=>strtoupper($w[0]))->take(2)->implode('')"
                                            :size="32"
                                        />
                                        <div class="col" style="gap:2px;">
                                            <span class="label" style="font-size:13px;">{{ $u->name }}</span>
                                            @if($u->id === auth()->id())
                                                <span class="mono" style="font-size:9px;color:var(--lime);">● kamu</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="mono dim" style="font-size:12px;word-break:break-all;">{{ $u->email }}</span>
                                    <span class="badge badge-{{
                                        match($u->role) {
                                            'admin'      => 'lime',
                                            'head_judge' => 'red',
                                            'judge'      => 'out',
                                            default      => 'solid',
                                        }
                                    }}" style="font-size:9px;justify-self:start;">{{ strtoupper(str_replace('_', ' ', $u->role)) }}</span>
                                    <span class="mono dim" style="font-size:11px;">{{ $u->created_at->format('d M Y') }}</span>
                                    <div class="flex gap-s" style="justify-content:flex-end;">
                                        <button wire:click="userEdit({{ $u->id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">Edit</button>
                                        @if($u->id !== auth()->id())
                                            <button wire:click="userDelete({{ $u->id }})"
                                                wire:confirm="Hapus user {{ $u->name }}? Tindakan ini tidak bisa dibatalkan."
                                                class="btn btn-sm btn-ghost" style="font-size:11px;color:var(--red);">Hapus</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Summary per role --}}
                        <div class="flex gap-s" style="flex-wrap:wrap;">
                            @foreach($users->groupBy('role') as $role => $group)
                                <div class="panel" style="padding:12px 18px;display:flex;align-items:center;gap:10px;">
                                    <span class="display tnum" style="font-size:24px;color:var(--lime);">{{ $group->count() }}</span>
                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.1em;">{{ strtoupper(str_replace('_', ' ', $role)) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">👤</span>
                            <p class="dim">{{ $userSearch ? 'Tidak ada user yang cocok dengan pencarian.' : 'Belum ada user.' }}</p>
                        </div>
                    @endif

                    @error('userDelete')
                        <div style="padding:12px 16px;border-left:3px solid var(--red);background:var(--bg-2);border-radius:2px;">
                            <span style="color:var(--red);font-size:13px;">{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            @endif

            {{-- ── SCORING SETUP ── --}}
            @if($view === 'scoring')
                <div class="col" style="gap:24px;">

                    {{-- Criteria Master --}}
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="display:block;margin-bottom:14px;">MASTER KRITERIA PENILAIAN</span>

                        {{-- Form tambah/edit --}}
                        <div class="flex gap-s" style="flex-wrap:wrap;align-items:flex-end;margin-bottom:14px;">
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">NAMA</span>
                                <input type="text" wire:model.live="criterionName" placeholder="Difficulty" class="input-field" style="width:160px;" />
                                @error('criterionName') <p style="color:var(--red);font-size:10px;">{{ $message }}</p> @enderror
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">KEY (slug)</span>
                                <input type="text" wire:model.live="criterionKey" placeholder="difficulty" class="input-field" style="width:140px;" />
                                @error('criterionKey') <p style="color:var(--red);font-size:10px;">{{ $message }}</p> @enderror
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">ORDER</span>
                                <input type="number" wire:model.live="criterionOrder" min="0" class="input-field" style="width:80px;" />
                            </div>
                            <button wire:click="saveCriterion" class="btn btn-sm btn-lime" @if(!$criterionName || !$criterionKey) disabled @endif>
                                {{ $editCriterionId ? 'Update' : '+ Tambah' }}
                            </button>
                        </div>

                        @if(isset($scoringCriteria) && $scoringCriteria->count())
                            <div style="overflow:hidden;border-radius:3px;border:1px solid var(--line);">
                                @foreach($scoringCriteria as $sc)
                                    <div class="between" style="padding:10px 14px;border-bottom:1px solid var(--line);{{ !$sc->is_active ? 'opacity:0.5;' : '' }}">
                                        <div class="flex gap-m" style="align-items:center;">
                                            <span class="mono dim" style="font-size:11px;width:24px;">{{ $sc->display_order }}</span>
                                            <span class="label" style="font-size:13px;">{{ $sc->name }}</span>
                                            <span class="mono dim" style="font-size:10px;">{{ $sc->key }}</span>
                                            @if(!$sc->is_active) <span class="badge badge-out" style="font-size:9px;">NONAKTIF</span> @endif
                                        </div>
                                        <div class="flex gap-s">
                                            <button wire:click="editCriterion({{ $sc->id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">Edit</button>
                                            <button wire:click="toggleCriterion({{ $sc->id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">{{ $sc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                            <button wire:click="deleteCriterion({{ $sc->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Hapus kriteria '{{ $sc->name }}'?">Hapus</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mono dim" style="font-size:12px;">Belum ada kriteria. Tambah di atas.</p>
                        @endif
                    </div>

                    {{-- Assign Criteria ke Event --}}
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="display:block;margin-bottom:14px;">ASSIGN KRITERIA KE EVENT</span>
                        <div class="flex gap-s" style="flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">EVENT</span>
                                <select wire:model.live="scEventId" class="input-field" style="min-width:200px;">
                                    <option value="0">— pilih event —</option>
                                    @foreach($events as $ev)
                                        <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">KRITERIA</span>
                                <select wire:model.live="scCriterionId" class="input-field" style="min-width:160px;">
                                    <option value="0">— pilih kriteria —</option>
                                    @foreach($allCriteria ?? [] as $ac)
                                        <option value="{{ $ac->id }}">{{ $ac->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">BERLAKU UNTUK</span>
                                <select wire:model.live="scAppliesTo" class="input-field" style="min-width:130px;">
                                    <option value="BOTH">Live + Knockout</option>
                                    <option value="LIVE">Live Only</option>
                                    <option value="KNOCKOUT">Knockout Only</option>
                                </select>
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">ORDER</span>
                                <input type="number" wire:model.live="scOrder" min="0" class="input-field" style="width:70px;" />
                            </div>
                            <button wire:click="assignCriterionToEvent" class="btn btn-sm btn-lime" @if(!$scEventId || !$scCriterionId) disabled @endif>Assign</button>
                        </div>

                        @if(isset($eventScoringList))
                            @foreach($eventScoringList as $ev)
                                @if($ev->scoringCriteria->count())
                                    <div style="margin-bottom:12px;">
                                        <span class="label" style="font-size:12px;display:block;margin-bottom:6px;">{{ $ev->title }}</span>
                                        <div class="flex gap-s" style="flex-wrap:wrap;">
                                            @foreach($ev->scoringCriteria as $sc)
                                                <div style="display:flex;align-items:center;gap:6px;padding:5px 10px;background:var(--bg-2);border-radius:3px;border:1px solid var(--line);">
                                                    <span class="label" style="font-size:11px;">{{ $sc->name }}</span>
                                                    <span class="mono dim" style="font-size:9px;">{{ $sc->pivot->applies_to }}</span>
                                                    <button wire:click="removeCriterionFromEvent({{ $ev->id }}, {{ $sc->id }})" class="mono dim" style="font-size:10px;border:none;background:none;cursor:pointer;color:var(--red);">×</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>

                    {{-- Assign Judge ke Event --}}
                    <div class="panel" style="padding:20px;">
                        <span class="kicker" style="display:block;margin-bottom:14px;">ASSIGN JUDGE KE EVENT</span>
                        <div class="flex gap-s" style="flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">EVENT</span>
                                <select wire:model.live="jaEventId" class="input-field" style="min-width:200px;">
                                    <option value="0">— pilih event —</option>
                                    @foreach($events as $ev)
                                        <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">JUDGE</span>
                                <select wire:model.live="jaJudgeUserId" class="input-field" style="min-width:180px;">
                                    <option value="0">— pilih judge —</option>
                                    @foreach($judgeUsers ?? [] as $ju)
                                        <option value="{{ $ju->id }}">{{ $ju->name }} ({{ $ju->role }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col" style="gap:4px;">
                                <span class="mono dim" style="font-size:10px;">TUGAS</span>
                                <select wire:model.live="jaScoringMode" class="input-field" style="min-width:150px;">
                                    <option value="BOTH">Live + Knockout</option>
                                    <option value="LIVE">Live Only</option>
                                    <option value="KNOCKOUT">Knockout Only</option>
                                </select>
                            </div>
                            <button wire:click="assignJudgeToEvent" class="btn btn-sm btn-lime" @if(!$jaEventId || !$jaJudgeUserId) disabled @endif>Assign</button>
                        </div>

                        @if(isset($judgeAssignments) && $judgeAssignments->count())
                            <div style="overflow:hidden;border-radius:3px;border:1px solid var(--line);">
                                @foreach($judgeAssignments as $ja)
                                    <div class="between" style="padding:10px 14px;border-bottom:1px solid var(--line);">
                                        <div class="flex gap-m" style="align-items:center;">
                                            <span class="label" style="font-size:12px;">{{ $ja->user->name }}</span>
                                            <span class="mono dim" style="font-size:10px;">{{ $ja->event->title }}</span>
                                            <span class="badge badge-out" style="font-size:9px;">{{ $ja->scoring_mode }}</span>
                                        </div>
                                        <button wire:click="removeJudgeFromEvent({{ $ja->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Hapus assignment judge ini?">Hapus</button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mono dim" style="font-size:12px;">Belum ada assignment judge.</p>
                        @endif
                    </div>

                </div>
            @endif

        </div>
    </div>
</div>

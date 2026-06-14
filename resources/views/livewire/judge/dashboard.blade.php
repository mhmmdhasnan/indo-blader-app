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
            <span class="badge badge-out" style="margin-top:12px;font-size:9px;">JUDGE PANEL</span>
        </div>
        <nav class="col" style="padding:12px;gap:3px;flex:1;">
            @foreach([
                ['judging',       '★', 'Judge Panel'],
                ['qualification', '⚡', 'Qualification'],
                ['brackets',      '⊟', 'Brackets'],
                ['submissions',   '▶', 'Submissions'],
                ['categories',    '⊞', 'Categories'],
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
            <div style="margin-bottom:8px;">
                <span class="label" style="font-size:12px;display:block;">{{ auth()->user()->name }}</span>
                <span class="mono dim" style="font-size:10px;">{{ strtoupper(auth()->user()->role) }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-bottom:8px;">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;">Logout</button>
            </form>
            <a href="{{ route('home') }}" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;">← Back to Site</a>
        </div>
    </aside>

    {{-- ── MAIN ── --}}
    <div style="min-width:0;display:flex;flex-direction:column;">
        <header class="between admin-topbar" style="padding:16px 26px;border-bottom:2px solid var(--ink);position:sticky;top:0;background:color-mix(in srgb,var(--bg) 88%,transparent);backdrop-filter:blur(8px);z-index:20;">
            <div class="col">
                <span class="kicker">INDO BLADER NATIONALS '26</span>
                <h1 class="display" style="font-size:26px;">
                    {{ collect([
                        'judging'      => 'Judge Panel',
                        'qualification'=> 'Qualification',
                        'brackets'     => 'Brackets',
                        'submissions'  => 'Submissions',
                        'categories'   => 'Categories',
                    ])->get($view, 'Judge Panel') }}
                </h1>
            </div>
            <div class="flex" style="align-items:center;gap:14px;">
                <span class="badge badge-out"><span class="live-dot"></span>SYSTEM LIVE</span>
                <div class="flex adm-head-user" style="align-items:center;gap:10px;">
                    <x-avatar :initials="collect(explode(' ', auth()->user()->name))->map(fn($w)=>strtoupper($w[0]))->take(2)->implode('')" :size="36" />
                    <div class="col">
                        <span class="label" style="font-size:13px;white-space:nowrap;">{{ auth()->user()->name }}</span>
                        <span class="mono dim" style="font-size:10px;">JUDGE</span>
                    </div>
                </div>
            </div>
        </header>

        <div style="padding:26px;flex:1;">

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

            {{-- ── QUALIFICATION ── --}}
            @if($view === 'qualification')
                <div class="col" style="gap:16px;">
                    <div class="panel" style="padding:16px;">
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:8px;">FILTER BY EVENT</span>
                        <select wire:model.live="selectedEventId" class="input-field" style="max-width:300px;">
                            <option value="0">— all events —</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}">{{ $ev->title }}</option>
                            @endforeach
                        </select>
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
                                                @if($match->status === 'PENDING' && $match->rider_a_registration_id && $match->rider_b_registration_id)
                                                    <button wire:click="setQualMatchWinner({{ $match->id }}, {{ $match->rider_a_registration_id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">{{ $match->riderA?->name }} Wins</button>
                                                    <button wire:click="setQualMatchWinner({{ $match->id }}, {{ $match->rider_b_registration_id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">{{ $match->riderB?->name }} Wins</button>
                                                @endif
                                                <button wire:click="deleteQualMatch({{ $match->id }})" class="btn btn-sm btn-ghost" style="color:var(--red);font-size:11px;" wire:confirm="Hapus match ini?">Hapus</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div style="padding:20px 18px;color:var(--ink-dim);font-size:13px;text-align:center;">No pairings yet — tambah manual di atas.</div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">⚡</span>
                            <p class="dim">No qualification rounds found.</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── BRACKETS ── --}}
            @if($view === 'brackets')
                <div class="col" style="gap:16px;">
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
                                                    <div class="flex gap-s" x-data="{ trickId: {{ $match->trick_id ?? 0 }} }">
                                                        @if(isset($tricks) && $tricks->count() && !$match->winner_registration_id)
                                                            <select x-model="trickId" class="input-field" style="font-size:11px;padding:4px 8px;">
                                                                <option value="0">Assign trick…</option>
                                                                @foreach($tricks as $t)
                                                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->difficulty }})</option>
                                                                @endforeach
                                                            </select>
                                                            <button x-show="trickId > 0" @click="$wire.assignTrickToBracketMatch({{ $match->id }}, trickId)" class="btn btn-sm btn-ghost" style="font-size:11px;">Assign</button>
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
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">⊟</span>
                            <p class="dim">No brackets found. Admin dapat generate bracket dari Admin Panel.</p>
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
                                    <input type="text" x-model="feedback" placeholder="Feedback (optional)" class="input-field" style="flex:1;min-width:200px;font-size:12px;" />
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

            {{-- ── CATEGORIES ── --}}
            @if($view === 'categories')
                <div class="col" style="gap:16px;">
                    @if(isset($pendingCategoryAssignments) && $pendingCategoryAssignments->count())
                        @foreach($pendingCategoryAssignments as $rc)
                            <div class="panel" style="padding:18px;">
                                <div class="between" style="margin-bottom:12px;">
                                    <div class="col" style="gap:4px;">
                                        <span class="label" style="font-size:14px;">{{ $rc->registration->name }}</span>
                                        <span class="mono dim" style="font-size:10px;">{{ $rc->registration->event?->title }} · {{ $rc->registration->email }}</span>
                                    </div>
                                    <span class="badge badge-{{ match($rc->category->name){ 'Beginner'=>'lime','Open'=>'out','Pro'=>'red',default=>'out' } }}">
                                        {{ $rc->category->name }}
                                    </span>
                                </div>
                                <div class="flex gap-s" x-data="{ notes: '', moveTo: 0 }" style="flex-wrap:wrap;align-items:center;">
                                    <button @click="$wire.approveCategoryAssignment({{ $rc->id }}, notes)" class="btn btn-sm btn-lime">Approve</button>
                                    <button @click="$wire.rejectCategoryAssignment({{ $rc->id }}, notes)" class="btn btn-sm btn-ghost">Reject</button>
                                    <select x-model="moveTo" class="input-field" style="font-size:12px;">
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
                    @else
                        <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                            <span style="font-size:40px;">⊞</span>
                            <p class="dim">No pending category assignments.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>

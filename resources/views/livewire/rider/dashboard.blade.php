<div style="max-width:900px;margin:0 auto;padding:clamp(24px,4vw,48px) clamp(16px,3vw,32px);">

    {{-- Header --}}
    <div class="between" style="margin-bottom:32px;flex-wrap:wrap;gap:12px;">
        <div class="col">
            <span class="kicker">RIDER DASHBOARD</span>
            <h1 class="display" style="font-size:clamp(28px,5vw,42px);">{{ auth()->user()->name }}</h1>
        </div>
        <div class="flex gap-s">
            @if($unreadCount > 0)
                <span class="badge badge-red">{{ $unreadCount }} notif baru</span>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
            </form>
        </div>
    </div>

    {{-- Tab Nav --}}
    <nav class="flex" style="gap:4px;border-bottom:2px solid var(--ink);margin-bottom:28px;overflow-x:auto;">
        @foreach([
            ['overview',       'Overview'],
            ['registrations',  'Registrasi'],
            ['notifications',  'Notifikasi' . ($unreadCount > 0 ? " ({$unreadCount})" : '')],
            ['upload',         'Upload Video'],
        ] as [$k,$lbl])
            <button wire:click="$set('view','{{ $k }}')" class="label" style="
                padding:10px 16px;font-size:12px;white-space:nowrap;border-bottom:2px solid {{ $view === $k ? 'var(--lime)' : 'transparent' }};
                color:{{ $view === $k ? 'var(--ink)' : 'var(--ink-dim)' }};margin-bottom:-2px;
                transition:color .15s,border-color .15s;
            ">{{ $lbl }}</button>
        @endforeach
    </nav>

    {{-- ── OVERVIEW ── --}}
    @if($view === 'overview')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;">
            <div class="panel halftone" style="padding:20px;">
                <span class="mono dim" style="font-size:10px;letter-spacing:0.14em;display:block;margin-bottom:8px;">TOTAL REGISTRASI</span>
                <span class="display tnum" style="font-size:40px;color:var(--lime);">{{ $registrations->count() }}</span>
            </div>
            <div class="panel halftone" style="padding:20px;">
                <span class="mono dim" style="font-size:10px;letter-spacing:0.14em;display:block;margin-bottom:8px;">NOTIF BELUM DIBACA</span>
                <span class="display tnum" style="font-size:40px;color:{{ $unreadCount > 0 ? 'var(--red)' : 'var(--ink)' }};">{{ $unreadCount }}</span>
            </div>
            <div class="panel halftone" style="padding:20px;">
                <span class="mono dim" style="font-size:10px;letter-spacing:0.14em;display:block;margin-bottom:8px;">STATUS TERAKHIR</span>
                @if($registrations->first())
                    @php $last = $registrations->first(); @endphp
                    <span class="badge badge-{{ match($last->status){ 'APPROVED'=>'lime','REJECTED'=>'out',default=>'red' } }}" style="font-size:13px;">
                        {{ $last->status }}
                    </span>
                @else
                    <span class="dim">—</span>
                @endif
            </div>
        </div>

        <div class="panel" style="padding:20px;">
            <span class="kicker" style="display:block;margin-bottom:12px;">AKSI CEPAT</span>
            <div class="flex gap-s" style="flex-wrap:wrap;">
                <a href="{{ route('register') }}" class="btn btn-lime">+ Daftar Event Baru</a>
                <button wire:click="$set('view','upload')" class="btn btn-ghost">▶ Upload Video</button>
                <button wire:click="$set('view','notifications')" class="btn btn-ghost">🔔 Notifikasi</button>
            </div>
        </div>
    @endif

    {{-- ── REGISTRASI ── --}}
    @if($view === 'registrations')
        @if($registrations->count())
            <div class="col" style="gap:12px;">
                @foreach($registrations as $reg)
                    <div class="panel" style="padding:18px;">
                        <div class="between" style="flex-wrap:wrap;gap:8px;">
                            <div class="col" style="gap:4px;">
                                <span class="label" style="font-size:15px;">{{ $reg->event?->title ?? 'Event' }}</span>
                                <span class="mono dim" style="font-size:10px;">{{ $reg->entry_code }} · {{ $reg->category }}</span>
                                @if($reg->competition_category)
                                    <span class="mono dim" style="font-size:10px;">Kategori: {{ $reg->competition_category }}</span>
                                @endif
                            </div>
                            <div class="flex gap-s" style="align-items:center;flex-wrap:wrap;">
                                <span class="badge badge-{{ match($reg->status){ 'APPROVED'=>'lime','REJECTED'=>'out',default=>'red' } }}">{{ $reg->status }}</span>
                                <span class="badge badge-{{ match($reg->payment_status){ 'VERIFIED'=>'lime','UNPAID'=>'out',default=>'red' } }}">{{ $reg->payment_status }}</span>
                            </div>
                        </div>
                        <div style="margin-top:10px;">
                            <a href="{{ route('notifications', $reg->entry_code) }}" class="btn btn-sm btn-ghost">Lihat Notifikasi →</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                <span style="font-size:40px;">📋</span>
                <p class="dim">Belum ada registrasi. Daftar event dulu!</p>
                <a href="{{ route('register') }}" class="btn btn-lime">Daftar Sekarang</a>
            </div>
        @endif
    @endif

    {{-- ── NOTIFIKASI ── --}}
    @if($view === 'notifications')
        @if($unreadCount > 0)
            <div style="margin-bottom:16px;">
                <button wire:click="markAllRead" class="btn btn-ghost btn-sm">✓ Tandai Semua Dibaca</button>
            </div>
        @endif

        @if(isset($notifications) && $notifications->count())
            <div class="col" style="gap:8px;">
                @foreach($notifications as $notif)
                    <div class="panel" style="padding:16px;opacity:{{ $notif->read_at ? '0.6' : '1' }};border-left:3px solid {{ $notif->read_at ? 'var(--line)' : 'var(--lime)' }};">
                        <div class="between" style="flex-wrap:wrap;gap:6px;">
                            <div class="col" style="gap:4px;">
                                <span class="label" style="font-size:14px;">{{ $notif->title }}</span>
                                <p style="font-size:13px;color:var(--ink-dim);margin:4px 0;">{{ $notif->body }}</p>
                                <span class="mono dim" style="font-size:10px;">{{ $notif->created_at->diffForHumans() }}</span>
                            </div>
                            @if(!$notif->read_at)
                                <button wire:click="markNotificationRead({{ $notif->id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">Tandai Dibaca</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="panel center col" style="padding:50px;gap:12px;text-align:center;">
                <span style="font-size:40px;">🔔</span>
                <p class="dim">Belum ada notifikasi.</p>
            </div>
        @endif
    @endif

    {{-- ── UPLOAD VIDEO ── --}}
    @if($view === 'upload')
        @if($uploadSuccess)
            <div class="panel" style="padding:20px;border-left:3px solid var(--lime);margin-bottom:20px;">
                <span class="label" style="color:var(--lime);">✓ Link video berhasil dikirim! Menunggu review dari judge.</span>
            </div>
        @endif
        @if($uploadError)
            <div class="panel" style="padding:20px;border-left:3px solid var(--red);margin-bottom:20px;">
                <span class="label" style="color:var(--red);">{{ $uploadError }}</span>
            </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="prof-grid">
            <div class="panel" style="padding:22px;">
                <span class="kicker" style="display:block;margin-bottom:4px;">SUBMIT LINK VIDEO</span>
                <p class="mono dim" style="font-size:11px;margin-bottom:16px;">Post video di Instagram, lalu paste link-nya di sini.</p>

                <div class="col" style="gap:14px;">
                    <div>
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:6px;">TIPE MATCH</span>
                        <select wire:model.live="selectedMatchType" class="input-field" style="width:100%;">
                            <option value="QUALIFICATION">Qualification</option>
                            <option value="PLAYOFF">Playoff (Bracket)</option>
                        </select>
                    </div>

                    <div>
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:6px;">PILIH MATCH</span>
                        <select wire:model.live="selectedMatchId" class="input-field" style="width:100%;">
                            <option value="0">— pilih match —</option>
                            @if($selectedMatchType === 'QUALIFICATION')
                                @forelse(isset($qualMatches) ? $qualMatches : [] as $m)
                                    <option value="{{ $m->id }}">
                                        {{ $m->qualificationRound?->event?->title }} — {{ $m->qualificationRound?->name }}
                                        ({{ $m->riderA?->name ?? '?' }} vs {{ $m->riderB?->name ?? '?' }})
                                    </option>
                                @empty
                                    <option disabled>Tidak ada qualification match</option>
                                @endforelse
                            @else
                                @forelse(isset($bracketMatches) ? $bracketMatches : [] as $m)
                                    <option value="{{ $m->id }}">
                                        {{ $m->bracket?->event?->title }} — {{ $m->round }}
                                        ({{ $m->riderA?->name ?? '?' }} vs {{ $m->riderB?->name ?? '?' }})
                                    </option>
                                @empty
                                    <option disabled>Tidak ada bracket match</option>
                                @endforelse
                            @endif
                        </select>
                    </div>

                    <div>
                        <span class="mono dim" style="font-size:10px;display:block;margin-bottom:6px;">LINK INSTAGRAM</span>
                        <input wire:model="videoUrl" type="url"
                            placeholder="https://www.instagram.com/reel/ABC123/"
                            style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:13px;outline:none;"
                            onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'">
                        @error('videoUrl') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
                        <p class="mono dim" style="font-size:10px;margin-top:6px;">
                            Format: <code>https://www.instagram.com/p/KODE/</code> atau <code>/reel/KODE/</code>
                        </p>
                    </div>

                    {{-- Preview link Instagram --}}
                    @if($videoUrl && preg_match('#instagram\.com/(p|reel)/([A-Za-z0-9_-]+)#', $videoUrl, $igm))
                        <div>
                            <span class="mono dim" style="font-size:10px;display:block;margin-bottom:6px;">PREVIEW</span>
                            <iframe
                                src="https://www.instagram.com/p/{{ $igm[2] }}/embed/"
                                width="100%" height="300"
                                style="border:none;border-radius:4px;background:#000;"
                                allowfullscreen scrolling="no" frameborder="0">
                            </iframe>
                        </div>
                    @endif

                    <button wire:click="submitVideo" class="btn btn-lime" wire:loading.attr="disabled" @if(!$selectedMatchId) disabled @endif>
                        <span wire:loading.remove wire:target="submitVideo">▶ Submit Link</span>
                        <span wire:loading wire:target="submitVideo">Mengirim...</span>
                    </button>
                </div>
            </div>

            <div class="panel" style="padding:22px;">
                <span class="kicker" style="display:block;margin-bottom:16px;">RIWAYAT SUBMISSION</span>
                @if(isset($submissions) && $submissions->count())
                    <div class="col" style="gap:10px;">
                        @foreach($submissions as $sub)
                            <div style="padding:12px;background:var(--bg-2);border-radius:3px;border:1px solid var(--line);">
                                <div class="between" style="margin-bottom:4px;">
                                    <span class="mono" style="font-size:10px;">{{ $sub->match_type }} #{{ $sub->match_id }}</span>
                                    <span class="badge badge-{{ match($sub->status){ 'APPROVED'=>'lime','REJECTED'=>'out','NEED_REUPLOAD'=>'red',default=>'red' } }}" style="font-size:9px;">{{ $sub->status }}</span>
                                </div>
                                @if($sub->judge_feedback)
                                    <p style="font-size:12px;color:var(--ink-dim);margin-top:4px;">{{ $sub->judge_feedback }}</p>
                                @endif
                                <span class="mono dim" style="font-size:10px;">{{ $sub->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="center col" style="padding:30px;gap:8px;text-align:center;">
                        <span style="font-size:32px;">▶</span>
                        <p class="dim" style="font-size:13px;">Belum ada submission.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>

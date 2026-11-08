<div wire:poll.60s x-data="liveFullscreen()">
    {{-- Hidden data bridge: Livewire updates these attrs; Alpine reads them via MutationObserver --}}
    <div id="live-data"
         data-phase="{{ $displayPhase ?? '' }}"
         data-started="{{ $liveStartedAt ?? 0 }}"
         data-duration="{{ $jamActive ? $jamDuration : $runDuration }}"
         data-jam="{{ $jamActive ? '1' : '' }}"
         data-score="{{ $revealScore ? number_format($revealScore, 1) : '' }}"
         data-accumulated="{{ ($revealAccumulatedTotal ?? null) !== null ? number_format($revealAccumulatedTotal, 1) : '' }}"
         data-rider="{{ $liveRider?->name ?? '' }}"
         data-initials="{{ $liveRider ? collect(preg_split('/\s+/', trim($liveRider->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('') : '' }}"
         data-avatar="{{ $liveRiderPhoto ?? '' }}"
         data-event="{{ $event?->title ?? '' }}"
         data-run="{{ $event?->live_run_number ?? '' }}"
         data-best="{{ $liveRiderBestScore !== null ? number_format($liveRiderBestScore, 1) : '' }}"
         data-division-label="{{ $liveDivisionLabel }}"
         data-is-best-trick="{{ ($isBestTrickPhase ?? false) ? '1' : '' }}"
         data-idle-title="{{ ($showingQualRecap ?? false) ? 'ADVANCING TO FINAL' : 'LIVE LEADERBOARD' }}"
         data-now="{{ $serverNow ?? 0 }}"
         data-live="{{ ($event && $event->status === 'LIVE') ? '1' : '' }}"
         style="display:none;">
    </div>

    @if($idleScreen ?? false)
        {{-- Idle screen: dipaksa Operator (misal pas jeda antar sesi / sebelum event
             mulai) — gak ada header/footer/nav situs maupun leaderboard sama sekali,
             cuma layar kosong buat sponsor. --}}
        <style>
            body > header, body > footer { display: none !important; }
        </style>
        <div style="position:relative;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);overflow:hidden;">
            {{-- Bumper logo loop — plays behind the sponsor banners while the
                 screen is idle (jeda antar sesi / sebelum event mulai). --}}
            <video autoplay loop muted playsinline
                style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                <source src="{{ asset('motion/bumper-logo.mp4') }}" type="video/mp4">
            </video>

            {{-- Timer heat Jamming Season — hanya muncul saat heat jamming sedang berjalan. --}}
            <div x-data="jamClock()" x-init="boot()" x-show="jam && phase === 'RUNNING'" x-cloak
                 style="position:absolute;left:50%;top:3%;transform:translateX(-50%);z-index:5;text-align:center;
                        padding:16px 44px;background:color-mix(in srgb,var(--bg) 78%,transparent);border:2px solid var(--lime);border-radius:6px;">
                <p class="kicker" style="margin-bottom:4px;font-size:13px;">JAMMING SEASON</p>
                <span class="display tnum text-glow-lime"
                      style="font-size:clamp(60px,9vw,150px);color:var(--lime);line-height:1;"
                      x-text="remainingFormatted"></span>
            </div>

            {{-- Sponsor banners — freely positioned by the Operator (drag & drop
                 in Admin → Sponsors). Absolute, percentage-based so it holds up
                 across screen sizes. --}}
            @foreach($idlePlacements as $p)
                <div style="position:absolute;left:{{ $p->x }}%;top:{{ $p->y }}%;transform:translate(-50%,-50%);
                    width:{{ round(200 * $p->size / 100) }}px;height:{{ round(100 * $p->size / 100) }}px;display:flex;align-items:center;justify-content:center;padding:14px;">
                    @if($p->sponsor->logo)
                        <img src="{{ $p->sponsor->logo_url }}" alt="{{ $p->sponsor->name }}" style="max-width:100%;max-height:100%;object-fit:contain;">
                    @else
                        <span class="display dim" style="font-size:15px;text-align:center;">{{ $p->sponsor->name }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @else

    <style>
        /* ── AudienceDisplay: stage 16:9. U(n) = n px pada kanvas 1920×1080, diskalakan ke layar. ── */
        .aud { --u: max(min(0.0520833vw, 0.0925926vh), 0.5px); display:flex; flex-direction:column; height:calc(100svh - 84px); background:var(--bg); overflow:hidden; }
        html.is-fullscreen .aud { height:100svh; }
        .aud-hd { position:relative; flex:none; display:flex; align-items:center; gap:calc(40*var(--u)); padding:calc(14*var(--u)) calc(80*var(--u)); min-height:calc(150*var(--u)); border-bottom:calc(4*var(--u)) solid var(--ink); background:var(--bg-2); }
        .aud-hd h1 { font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(88*var(--u)); line-height:calc(84*var(--u)); letter-spacing:.02em; }
        .aud-sub { font-family:var(--font-mono); font-weight:700; font-size:calc(26*var(--u)); line-height:calc(32*var(--u)); letter-spacing:.1em; text-transform:uppercase; color:var(--ink-dim); margin-top:calc(6*var(--u)); }
        .aud-chip { margin-left:auto; font-family:var(--font-mono); font-weight:700; font-size:calc(28*var(--u)); line-height:1; letter-spacing:.12em; padding:calc(14*var(--u)) calc(28*var(--u)); border-radius:999px; border:calc(4*var(--u)) solid var(--line-strong); white-space:nowrap; }
        .aud-bn { flex:none; display:flex; align-items:center; gap:calc(32*var(--u)); min-height:calc(88*var(--u)); padding:calc(8*var(--u)) calc(80*var(--u)); border-bottom:calc(2*var(--u)) solid var(--line); }
        .aud-bn b { font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(56*var(--u)); line-height:calc(56*var(--u)); letter-spacing:.04em; }
        .aud-bn span.s { font-family:var(--font-mono); font-weight:700; font-size:calc(26*var(--u)); letter-spacing:.1em; text-transform:uppercase; color:var(--ink-dim); }
        .aud-rail { margin-left:auto; display:flex; gap:calc(12*var(--u)); }
        .aud-rail i { font-style:normal; font-family:var(--font-mono); font-weight:700; font-size:calc(26*var(--u)); line-height:1; letter-spacing:.1em; padding:calc(12*var(--u)) calc(22*var(--u)); border:calc(4*var(--u)) solid var(--line-strong); color:var(--ink-dim); }
        .aud-rail i.d { border-color:var(--lime); color:var(--lime); }
        .aud-rail i.n { background:var(--red); border-color:var(--red); color:#fff; }
        .aud-mn { flex:1; min-height:0; position:relative; display:flex; flex-direction:column; overflow:hidden; }
        /* Isi zona konten otomatis diperkecil (lewat --s, diatur JS stageFit) kalau lebih tinggi dari ruang yang ada → tidak pernah ada scroll. */
        .aud-fit { --s:1; position:absolute; left:0; top:0; box-sizing:border-box; width:calc(100% / var(--s)); min-height:calc(100% / var(--s)); transform:scale(var(--s)); transform-origin:0 0; display:flex; flex-direction:column; gap:calc(24*var(--u)); padding:calc(32*var(--u)) calc(80*var(--u)); }
        html.is-fullscreen, html.is-fullscreen body { overflow:hidden; }
        html.is-fullscreen body > header { display:none !important; }
        .aud-ft { flex:none; position:relative; height:calc(120*var(--u)); min-height:60px; border-top:calc(2*var(--u)) solid var(--line); background:var(--bg-2); overflow:hidden; }
        .aud-ft .br { position:absolute; right:calc(80*var(--u)); top:50%; transform:translateY(-50%); font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(40*var(--u)); letter-spacing:.04em; pointer-events:none; }
                /* timer */
        .aud-tm { font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(400*var(--u)); line-height:calc(360*var(--u)); letter-spacing:.02em; text-align:center; color:var(--lime); font-variant-numeric:tabular-nums; }
        .aud-tm.out { font-size:calc(300*var(--u)); line-height:calc(280*var(--u)); color:var(--ink); }
        /* rider strip */
        .aud-rs { display:grid; grid-template-columns:repeat(var(--cols,4),1fr); gap:calc(20*var(--u)); margin-top:auto; }
        .aud-rs div { display:flex; align-items:center; gap:calc(20*var(--u)); min-width:0; border:calc(4*var(--u)) solid var(--ink); padding:calc(16*var(--u)) calc(24*var(--u)); background:var(--panel); }
        .aud-rs i, .at-av { flex:none; display:grid; place-items:center; border-radius:999px; border:calc(4*var(--u)) solid var(--line-strong); font-style:normal; font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; }
        .aud-rs i { width:calc(64*var(--u)); height:calc(64*var(--u)); font-size:calc(30*var(--u)); }
        .aud-rs b { font-weight:600; font-size:calc(36*var(--u)); line-height:calc(40*var(--u)); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .aud-rs.many b { font-size:calc(28*var(--u)); line-height:calc(32*var(--u)); }
        .aud-jg { display:flex; gap:calc(24*var(--u)); justify-content:center; flex-wrap:wrap; }
        .aud-jg span { font-family:var(--font-mono); font-weight:700; font-size:calc(36*var(--u)); line-height:1; letter-spacing:.1em; padding:calc(20*var(--u)) calc(36*var(--u)); border:calc(4*var(--u)) solid var(--line-strong); color:var(--ink-dim); }
        .aud-nx { display:flex; align-items:center; gap:calc(24*var(--u)); font-family:var(--font-mono); font-weight:700; font-size:calc(28*var(--u)); letter-spacing:.1em; color:var(--ink-dim); }
        .aud-nx b { font-family:var(--font-sans); font-weight:600; font-size:calc(32*var(--u)); letter-spacing:0; color:var(--ink); }
        .aud-nx .t { color:#ffb020; white-space:nowrap; }
        /* NEXT UP (standby): satu rider = spotlight + standings; heat grup = kartu per rider. Sponsor hanya di strip bawah. */
        .aud-rail i.x { border-color:#ffb020; color:#ffb020; }
        .aud-nu { flex:1; min-height:0; display:grid; grid-template-columns:calc(540*var(--u)) 1fr; gap:calc(48*var(--u)); }
        .aud-sp { position:relative; min-height:calc(420*var(--u)); overflow:hidden; border:calc(4*var(--u)) solid var(--ink); background:var(--panel); min-width:0; }
        .aud-sp .ph { position:absolute; inset:0; display:grid; place-items:center; background:color-mix(in srgb,var(--ink) 7%,var(--bg-2)); overflow:hidden; font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(300*var(--u)); line-height:calc(280*var(--u)); color:var(--line-strong); }
        .aud-sp .ph img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:top center; }
        /* Nama & kota ditumpuk di atas gradasi gelap di bawah foto: foto memenuhi seluruh kartu. Warna tetap (putih) supaya terbaca di tema terang maupun gelap. */
        .aud-sp .in { position:absolute; left:0; right:0; bottom:0; z-index:1; padding:calc(90*var(--u)) calc(26*var(--u)) calc(24*var(--u)); display:flex; flex-direction:column; gap:calc(10*var(--u)); min-width:0; background:linear-gradient(to top, rgba(0,0,0,.95) 0%, rgba(0,0,0,.85) 40%, rgba(0,0,0,0) 100%); }
        .aud-at.oc { border-color:#ff2d1f; color:#fff; background:#ff2d1f !important; }
        .aud-sp .ph .aud-at { position:absolute; left:calc(24*var(--u)); top:calc(24*var(--u)); z-index:2; background:var(--bg); }
        .aud-at { align-self:flex-start; font-family:var(--font-mono); font-weight:700; font-size:calc(26*var(--u)); line-height:1.2; letter-spacing:.12em; padding:calc(6*var(--u)) calc(18*var(--u)); border:calc(4*var(--u)) solid #ffb020; color:#ffb020; }
        .aud-sp .nmx { color:#fff; font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(56*var(--u)); line-height:calc(56*var(--u)); letter-spacing:.02em; overflow-wrap:anywhere; }
        .aud-sp .ct { font-family:var(--font-mono); font-weight:700; font-size:calc(22*var(--u)); letter-spacing:.1em; color:#d9d9d4; text-transform:uppercase; }
        .aud-cap { font-family:var(--font-mono); font-weight:700; font-size:calc(24*var(--u)); line-height:calc(30*var(--u)); letter-spacing:.12em; text-transform:uppercase; color:var(--ink-dim); margin-bottom:calc(12*var(--u)); }
        .aud-note { margin-top:calc(14*var(--u)); font-family:var(--font-mono); font-weight:700; font-size:calc(24*var(--u)); letter-spacing:.1em; color:var(--ink-dim); }
        .at-row.me { box-shadow:inset 0 0 0 calc(4*var(--u)) #ffb020; }
        /* Standings Next Up: kolom lebih ramping dari leaderboard besar supaya kolom nama tetap lega di sebelah kartu foto. */
        .at.d-next { --rh:88; --rk:64; --av:56; --nm:36; --ct:20; --rn:32; --bs:68; }
        .at.one-run .at-row { grid-template-columns:calc(150*var(--u)) 1fr calc(240*var(--u)) calc(280*var(--u)); }
        .at.d-next.one-run .at-row { grid-template-columns:calc(110*var(--u)) 1fr calc(140*var(--u)) calc(190*var(--u)); }
        .at.d-next .at-row { grid-template-columns:calc(110*var(--u)) 1fr calc(140*var(--u)) calc(140*var(--u)) calc(190*var(--u)); padding:0 calc(24*var(--u)); }
        .aud-gc { flex:1; min-height:0; display:grid; grid-template-columns:repeat(var(--c,2),1fr); grid-auto-rows:1fr; gap:calc(24*var(--u)); }
        .aud-gc > div { min-width:0; border:calc(4*var(--u)) solid var(--ink); background:var(--panel); display:flex; align-items:center; gap:calc(32*var(--u)); padding:0 calc(40*var(--u)); }
        .aud-gc i { flex:none; display:grid; place-items:center; width:calc(140*var(--u)); height:calc(140*var(--u)); border-radius:999px; border:calc(6*var(--u)) solid var(--line-strong); font-style:normal; font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(64*var(--u)); }
        .aud-gc b { display:block; font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(72*var(--u)); line-height:calc(68*var(--u)); letter-spacing:.02em; overflow-wrap:anywhere; }
        .aud-gc small { font-family:var(--font-mono); font-weight:700; font-size:calc(26*var(--u)); letter-spacing:.1em; color:var(--ink-dim); text-transform:uppercase; }
        .aud-gc.s i { width:calc(96*var(--u)); height:calc(96*var(--u)); font-size:calc(44*var(--u)); border-width:calc(5*var(--u)); }
        .aud-gc.s b { font-size:calc(52*var(--u)); line-height:calc(50*var(--u)); }
        .aud-gc.s small { font-size:calc(22*var(--u)); }
        .aud-gc.s > div { gap:calc(24*var(--u)); padding:0 calc(28*var(--u)); }
        /* tabel leaderboard besar */
        .at { display:flex; flex-direction:column; }
        .at.d-big   { --rh:148; --rk:104; --av:92; --nm:56; --ct:26; --rn:44; --bs:112; }
        .at.d-mid   { --rh:118; --rk:80;  --av:72; --nm:46; --ct:22; --rn:38; --bs:88; }
        .at.d-tight { --rh:84;  --rk:60;  --av:56; --nm:38; --ct:20; --rn:30; --bs:64; }
        .at.d-xs    { --rh:60;  --rk:42;  --av:42; --nm:28; --ct:16; --rn:22; --bs:42; }
        .at-row { display:grid; grid-template-columns:calc(150*var(--u)) 1fr calc(220*var(--u)) calc(220*var(--u)) calc(280*var(--u)); align-items:center; height:calc(var(--rh)*var(--u)); padding:0 calc(32*var(--u)); border-bottom:calc(2*var(--u)) solid var(--line); background:var(--panel); border-left:calc(8*var(--u)) solid transparent; }
        .at-row.h { height:calc(56*var(--u)); background:transparent; font-family:var(--font-mono); font-weight:700; font-size:calc(24*var(--u)); letter-spacing:.14em; color:var(--ink-dim); border-bottom:calc(2*var(--u)) solid var(--ink); }
        .at-row.h span:nth-child(n+3) { text-align:right; }
        .at-rk { font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(var(--rk)*var(--u)); line-height:1; }
        .at-rd { display:flex; align-items:center; gap:calc(28*var(--u)); min-width:0; }
        .at-av { width:calc(var(--av)*var(--u)); height:calc(var(--av)*var(--u)); font-size:calc(var(--av)*var(--u)*0.43); }
        .at-nm { font-weight:600; font-size:calc(var(--nm)*var(--u)); line-height:1.08; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .at-ct { font-family:var(--font-mono); font-size:calc(var(--ct)*var(--u)); letter-spacing:.04em; text-transform:uppercase; color:var(--ink-dim); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .at-r { font-family:var(--font-mono); font-size:calc(var(--rn)*var(--u)); text-align:right; color:var(--ink-dim); }
        .at-b { font-family:'Bebas Neue','Arial Black',Impact,sans-serif; font-weight:900; font-size:calc(var(--bs)*var(--u)); line-height:1; text-align:right; }
        .at-lolos { font-family:var(--font-mono); font-weight:700; font-size:calc(20*var(--u)); padding:2px calc(10*var(--u)); margin-left:calc(14*var(--u)); border:calc(3*var(--u)) solid var(--lime); color:var(--lime); vertical-align:middle; }
        .at-row.on { border-left-color:var(--red); background:color-mix(in srgb,var(--red) 10%,var(--panel)); }
        @media (max-width:700px) {
            .at-row { grid-template-columns:44px 1fr 90px; padding:0 12px; }
            .at-row .at-r { display:none; }
            .aud-hd, .aud-bn, .aud-fit { padding-left:16px; padding-right:16px; }
            /* HP: halaman boleh discroll, tidak diperkecil. */
            .aud { height:auto; min-height:calc(100svh - 84px); overflow:visible; }
            .aud-mn { overflow:visible; }
            .aud-fit { position:static; width:auto; min-height:0; transform:none; }
        }
        /* Baris pemenang: lime penuh, teks gelap. */
        .at-row.lsc-win, .score-row.lsc-win { background:var(--lime) !important; border-left-color:transparent !important; }
        .at-row.lsc-win, .at-row.lsc-win *, .score-row.lsc-win, .score-row.lsc-win * { color:#0a0a0b !important; }
        .at-row.lsc-win .at-av { border-color:#0a0a0b; }
    </style>


    @php
        $hdStage    = $division ? ($stage === 'FINAL' ? 'Final' : 'Qualification') : null;
        $hdGroup    = $groups->isNotEmpty()
            ? ($groups->firstWhere('id', $selectedGroupId)?->name ?? 'All heats') : null;
        // Final: "Final Heat 2" → "Heat 2"; hanya satu heat → cukup "Final".
        if ($stage === 'FINAL' && $hdGroup) {
            $hdGroup = $groups->count() <= 1 ? null : preg_replace('/^Final\s+/i', '', $hdGroup);
        }
        // Best Trick tidak mengenal heat: "DIVISI · Best Trick" saja.
        if ($isBestTrickPhase ?? false) { $hdStage = 'Best Trick'; $hdGroup = null; }
        $hdSubtitle = collect([$division?->name ?? 'All divisions', $hdStage, $hdGroup])->filter()->implode(' · ');
        $hdHeatIdx  = ($groups->isNotEmpty() && $selectedGroupId)
            ? $groups->search(fn ($g) => (int) $g->id === (int) $selectedGroupId) : false;
        $hdBannerSub = collect([
            ($displayPhase === 'NEXT' && !$jamActive && $liveRider)
                ? (($isBestTrickPhase ?? false) ? 'Best Trick' : 'Attempt ' . (int) ($event->live_run_number ?: 1)) : null,
            (!($isBestTrickPhase ?? false) && $hdHeatIdx !== false) ? 'Heat ' . ($hdHeatIdx + 1) . ' of ' . $groups->count() : null,
            ($displayPhase === 'NEXT' && $jamActive) ? $jamRows->count() . ' riders' : null,
            $jamActive ? (int) $jamDuration . ' seconds' : null,
        ])->filter()->implode(' · ');
        // Layar NEXT UP: satu rider (spotlight + standings) atau satu heat grup (kartu per rider).
        // Best Trick: saat trick berjalan layarnya tetap sama dengan STANDBY (spotlight + standings), hanya penanda "ON COURSE".
        $btRunning  = ($isBestTrickPhase ?? false) && $displayPhase === 'RUNNING';
        $nextSingle = ($displayPhase === 'NEXT' || $btRunning) && !$jamActive && $liveRider;
        $nextGroup  = $displayPhase === 'NEXT' && $jamActive;
        $initialsOf = fn ($name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
    @endphp

    <div class="aud" x-data="liveBanner()" x-init="boot()">
        <div class="aud-hd">
            <div style="min-width:0;">
                <h1>{{ $event?->title ?? 'Live Scoring' }}</h1>
                <div class="aud-sub">{{ $hdSubtitle }}</div>
            </div>
            <div style="margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:calc(14*var(--u));">
                <span class="aud-brand" style="display:flex;align-items:center;">
                    <img src="{{ asset('images/logo-wordmark-light.png') }}" alt="FRAMEBLADESCORE" class="logo-light" style="height:calc(44*var(--u));min-height:14px;width:auto;">
                    <img src="{{ asset('images/logo-wordmark-dark.png') }}" alt="FRAMEBLADESCORE" class="logo-dark" style="height:calc(44*var(--u));min-height:14px;width:auto;">
                </span>
                <div class="aud-chip" style="margin-left:0;" :style="chipStyle" x-text="chip.t"></div>
            </div>
            <button @click="toggle()" x-show="!isFullscreen" class="mono"
                style="position:absolute;right:8px;top:92px;padding:4px 8px;border:1px solid var(--line-strong);background:var(--bg);color:var(--ink-dim);font-size:10px;letter-spacing:0.1em;cursor:pointer;z-index:5;">⤢ FULLSCREEN</button>
        </div>

        <div class="aud-bn">
            <b x-text="title"></b>
            @if($hdBannerSub)<span class="s">{{ $hdBannerSub }}</span>@endif
            @if(count($heatRail ?? []) > 1 && !($isBestTrickPhase ?? false))
                <div class="aud-rail">
                    @foreach($heatRail as $h)
                        @php $isNextHeat = $h['state'] === 'current' && $displayPhase === 'NEXT'; @endphp
                        <i class="{{ $h['state'] === 'done' ? 'd' : ($isNextHeat ? 'x' : ($h['state'] === 'current' ? 'n' : '')) }}">{{ $h['state'] === 'done' ? '✓ ' : ($isNextHeat ? '→ ' : ($h['state'] === 'current' ? '● ' : '')) }}{{ $h['name'] }}</i>
                    @endforeach
                </div>
            @endif
        </div>


        {{-- ── LIVE PHASE OVERLAY ── --}}
    <div x-data="livePhaseOverlay()" x-init="boot()" x-show="phase !== 'IDLE' && phase !== 'NEXT' && !jam && !btHide" x-cloak
         style="flex:1;min-height:0;background:var(--bg);padding:0 32px;">

            {{-- ── ESPORTS CARD ── --}}
            <div style="position:relative;display:grid;grid-template-columns:3fr 3fr 6fr;height:100%;min-height:0;overflow:hidden;border-left:2px solid var(--ink);border-right:2px solid var(--ink);">

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
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <div class="mono dim" style="font-size:14px;letter-spacing:0.12em;" x-text="eventTitle"></div>
                        <div class="mono" style="font-size:14px;letter-spacing:0.12em;color:var(--lime);">FRAMEBLADESCORE</div>
                        <div class="mono dim" style="font-size:14px;letter-spacing:0.1em;"
                             x-text="runNumber ? divisionLabel + ' · ' + (isBestTrick ? 'PERCOBAAN ' : 'RUN ') + runNumber : divisionLabel"></div>
                    </div>
                </div>

                {{-- COL 3: timer / score --}}
                <div style="padding:40px 48px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:var(--bg);position:relative;overflow:hidden;">
                    <div x-show="phase === 'REVEALING'" class="halftone" style="position:absolute;inset:0;pointer-events:none;opacity:0.4;display:none;"></div>

                    {{-- NEXT (Best Trick): kolom kanan diisi leaderboard divisi --}}
                    @if(($isBestTrickPhase ?? false) && ($scores ?? collect())->isNotEmpty())
                    <div x-show="(phase === 'NEXT' || phase === 'RUNNING') && isBestTrick" style="display:none;width:100%;max-height:100%;overflow-y:auto;">
                        <div class="between" style="margin-bottom:14px;gap:10px;flex-wrap:wrap;">
                            <span class="kicker" style="font-size:13px;display:flex;align-items:center;gap:10px;">
                                <span x-show="phase === 'RUNNING'" class="live-dot" style="width:12px;height:12px;display:none;"></span>
                                <span x-text="'PERCOBAAN ' + (runNumber || '—') + (phase === 'RUNNING' ? ' · ON COURSE — SEDANG MELAKUKAN TRICK' : '')"></span>
                            </span>
                            <span class="mono dim" style="font-size:12px;letter-spacing:0.14em;" x-text="divisionLabel"></span>
                        </div>
                        <div class="panel" style="overflow:hidden;">
                            @include('livewire.partials.live-score-table', ['rows' => $scores, 'density' => 'd-next', 'markRiderId' => $liveRider->id ?? null])
                        </div>
                    </div>
                    @endif

                    {{-- NEXT --}}
                    <div x-show="phase === 'NEXT' && !isBestTrick" style="display:none;text-align:center;">
                        <p class="kicker" style="margin-bottom:8px;font-size:11px;" x-text="(isBestTrick ? 'PERCOBAAN ' : 'RUN ') + (runNumber || '—')"></p>
                        <p class="mono dim" style="font-size:12px;letter-spacing:0.14em;" x-text="divisionLabel"></p>
                        <template x-if="bestScore">
                            <p class="mono dim" style="font-size:16px;letter-spacing:0.1em;margin-top:26px;">
                                SKOR TERTINGGI SEBELUMNYA<br>
                                <span class="display tnum text-glow-lime" style="font-size:clamp(80px,11vw,160px);color:var(--lime);line-height:1.1;" x-text="bestScore"></span>
                            </p>
                        </template>
                    </div>

                    {{-- RUNNING (run bertimer) --}}
                    <div x-show="phase === 'RUNNING' && !isBestTrick" style="display:none;text-align:center;">
                        <p class="kicker" style="margin-bottom:8px;font-size:11px;">RUN TIMER</p>
                        <span class="display tnum text-glow-lime"
                              style="font-size:clamp(80px,11vw,180px);color:var(--lime);line-height:1;"
                              x-text="remainingFormatted"></span>
                    </div>

                    {{-- RUNNING (Best Trick — 1 percobaan, tanpa timer) --}}
                    <div x-show="phase === 'RUNNING' && isBestTrick && !{{ (($isBestTrickPhase ?? false) && ($scores ?? collect())->isNotEmpty()) ? 'true' : 'false' }}" style="display:none;text-align:center;">
                        <div style="display:flex;align-items:center;justify-content:center;gap:12px;">
                            <span class="live-dot" style="width:14px;height:14px;"></span>
                        </div>
                        <p class="kicker" style="margin-top:16px;font-size:14px;">SEDANG MELAKUKAN TRICK</p>
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
                        <p class="kicker" style="margin-bottom:8px;font-size:11px;" x-text="(isBestTrick ? 'PERCOBAAN ' : 'RUN ') + (runNumber || '—')"></p>
                        <div class="score-reveal-anim" style="display:flex;align-items:baseline;justify-content:center;gap:10px;">
                            <span class="display tnum text-glow-lime"
                                  style="font-size:clamp(80px,11vw,180px);color:var(--lime);line-height:1;"
                                  x-text="(accumulated || score) || '—'"></span>
                            <span class="mono dim" style="font-size:16px;letter-spacing:0.1em;" x-text="isBestTrick ? '/ 10' : '/ 100'"></span>
                        </div>
                        <p class="mono dim" style="font-size:12px;letter-spacing:0.1em;margin-top:10px;" x-show="accumulated">
                            TOTAL AKUMULASI RIDER — skor run ini: <span x-text="score || '—'"></span>
                        </p>
                        <template x-if="bestScore">
                            <p class="mono dim" style="font-size:16px;letter-spacing:0.08em;margin-top:20px;">
                                SKOR TERTINGGI SEBELUMNYA<br>
                                <span class="display tnum text-glow-lime" style="font-size:clamp(48px,7vw,90px);color:var(--lime);line-height:1.2;" x-text="bestScore"></span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- Sponsor banners — freely positioned by the Operator (drag & drop
                     in Admin → Sponsors). Only shown in the NEXT phase (before a run
                     starts) — the card is otherwise busy with the timer/reveal
                     animation, so a banner here would fight for attention with them. --}}
                <div x-show="phase === 'NEXT'" style="position:absolute;inset:0;pointer-events:none;display:none;">
                    @foreach($nextupPlacements as $p)
                        <div style="position:absolute;left:{{ $p->x }}%;top:{{ $p->y }}%;transform:translate(-50%,-50%);
                            width:{{ round(180 * $p->size / 100) }}px;height:{{ round(88 * $p->size / 100) }}px;display:flex;align-items:center;justify-content:center;padding:12px;z-index:2;">
                            @if($p->sponsor->logo)
                                <img src="{{ $p->sponsor->logo_url }}" alt="{{ $p->sponsor->name }}" style="max-width:100%;max-height:100%;object-fit:contain;">
                            @else
                                <span class="display dim" style="font-size:13px;text-align:center;">{{ $p->sponsor->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
    </div>


        {{-- ── JAMMING SEASON HEAT: seluruh rider heat tampil bersamaan ── --}}
        @if($jamActive ?? false)
        @php $jamCount = $jamRows->count(); @endphp
        <div x-data="jamClock()" x-init="boot()" x-show="active" x-cloak class="aud-mn" style="display:none;">
          <div class="aud-fit">
            <div x-show="phase === 'RUNNING'" style="display:none;">
                <div class="aud-tm" :style="{ color: remaining <= 10 ? 'var(--red)' : 'var(--lime)' }" x-text="remainingFormatted"></div>
            </div>
            <div x-show="phase === 'JUDGING'" style="display:none;">
                <div class="aud-tm out">00:00:00</div>
                @if(!empty($jamJudges))
                    <div class="aud-jg" style="margin-top:calc(28*var(--u));">
                        @foreach($jamJudges as $j)
                            <span style="border-color:{{ $j['done'] ? 'var(--lime)' : '#ffb020' }};color:{{ $j['done'] ? 'var(--lime)' : '#ffb020' }};">{{ $j['done'] ? '✓' : '…' }} JUDGE {{ $j['n'] }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div x-show="phase !== 'REVEALING'" style="display:none;margin-top:auto;">
                <div class="aud-rs {{ $jamCount > 5 ? 'many' : '' }}" style="--cols:{{ max(1, min($jamCount, $jamCount > 5 ? 4 : 5)) }};">
                    {{-- Urut nama (bukan skor) supaya peringkat tidak bocor sebelum reveal. --}}
                    @foreach($jamRows->sortBy(fn ($r) => mb_strtolower($r['rider']->name))->values() as $row)
                        <div><i>{{ $initialsOf($row['rider']->name) }}</i><b>{{ $row['rider']->name }}</b></div>
                    @endforeach
                </div>
                @if($nextHeat ?? null)
                    <div class="aud-nx" x-show="phase === 'RUNNING'" style="margin-top:calc(20*var(--u));display:none;">
                        <span class="t">UP NEXT · {{ $nextHeat['label'] }}</span><b>{{ $nextHeat['names'] }}</b>
                    </div>
                @endif
            </div>

            <div x-show="phase === 'REVEALING'" style="display:none;">
                @include('livewire.partials.live-score-table', ['rows' => $jamRows, 'winnerRow' => true])
            </div>
          </div>
        </div>
        @endif



        {{-- ── NEXT UP: standby antar run / heat (desain AudienceDisplay "Next up") ── --}}
        @if($nextGroup)
            @php
                $ngRows = $jamRows->sortBy(fn ($r) => mb_strtolower($r['rider']->name))->values();   // urut nama: peringkat tidak boleh bocor
                $ngCols = $ngRows->count() <= 6 ? 2 : ($ngRows->count() <= 12 ? 3 : 4);
            @endphp
            <div class="aud-mn">
              <div class="aud-fit">
                <div class="aud-gc {{ $ngRows->count() > 6 ? 's' : '' }}" style="--c:{{ $ngCols }};">
                    @foreach($ngRows as $row)
                        <div wire:key="ng-{{ $row['rider']->id ?? $loop->index }}">
                            <i>{{ $initialsOf($row['rider']->name) }}</i>
                            <span style="min-width:0;"><b>{{ $row['rider']->name }}</b><small>{{ $row['rider']->city ?? '—' }}</small></span>
                        </div>
                    @endforeach
                </div>
              </div>
            </div>
        @elseif($nextSingle)
            @php
                // "Semua heat" saat kualifikasi: datanya ada di $scoreSections → gabungkan jadi satu peringkat.
                $nxAll     = $scores->isNotEmpty()
                    ? $scores->values()
                    : $scoreSections->flatMap(fn ($sec) => $sec['leaderboard'])->sortByDesc(fn ($r) => (float) ($r['total'] ?? $r['best'] ?? 0))->values();
                $nxPages   = $nxAll->chunk(5);   // 5 baris per halaman: muat tanpa scroll
                $nxCount   = $nxAll->count();
                $nxAvatar  = $liveRiderPhoto ?? null;
            @endphp
            <div class="aud-mn">
              <div class="aud-fit">
                <div class="aud-nu">
                    <div class="aud-sp">
                        <div class="ph">
                            <span>{{ $initialsOf($liveRider->name) }}</span>
                            @if($nxAvatar)<img src="{{ $nxAvatar }}" alt="" onerror="this.style.display='none'">@endif
                            <span class="aud-at{{ $btRunning ? ' oc' : '' }}">{{ $btRunning ? 'ON COURSE' : 'ATTEMPT ' . (int) ($event->live_run_number ?: 1) }}</span>
                        </div>
                        <div class="in">
                            <div class="nmx">{{ $liveRider->name }}</div>
                            <div class="ct">{{ $liveRider->city ?: '—' }}</div>
                        </div>
                    </div>
                    <div x-data="{ page: 0, n: {{ max(1, $nxPages->count()) }}, per: 5, total: {{ $nxCount }}, t: null,
                                   init() { if (this.n > 1) this.t = setInterval(() => { this.page = (this.page + 1) % this.n }, 10000); },
                                   destroy() { clearInterval(this.t); } }">
                        <div class="aud-cap">CURRENT STANDINGS</div>
                        @foreach($nxPages as $pi => $pageRows)
                            <div x-show="page === {{ $pi }}" @if($pi > 0) style="display:none;" @endif>
                                @include('livewire.partials.live-score-table', ['rows' => $pageRows, 'allRows' => $nxAll, 'density' => 'd-next', 'markRiderId' => $liveRider->id])
                            </div>
                        @endforeach
                        @if($nxCount > 5)
                            <div class="aud-note" x-text="'ROWS ' + (page * per + 1) + '–' + Math.min((page + 1) * per, total) + ' OF ' + total + ' · ROTATES EVERY 10 SECONDS'"></div>
                        @endif
                    </div>
                </div>
              </div>
            </div>
        @elseif(!$event)
            <div class="aud-mn" style="align-items:center;justify-content:center;text-align:center;">
                <span class="display" style="font-size:calc(120*var(--u));color:var(--ink-faint);">—</span>
                <span class="kicker">PILIH EVENT</span>
            </div>
        @elseif($scores->isEmpty() && $scoreSections->isEmpty())
            <div class="aud-mn" style="align-items:center;justify-content:center;text-align:center;">
                <span class="display" style="font-size:calc(120*var(--u));color:var(--ink-faint);">—</span>
                <span class="kicker">BELUM ADA PESERTA</span>
                <p class="mono dim" style="font-size:13px;max-width:360px;">Belum ada peserta approved untuk <strong>{{ $event->title }}</strong>.</p>
            </div>
        @else
            @php
                // Best Trick: begitu REVEAL, layar publik langsung leaderboard penuh (tanpa kartu skor).
                // Overlay (livePhaseOverlay) menyembunyikan kartunya sendiri lewat getter btHide.
                $btLeaderboard = ($isBestTrickPhase ?? false) && $displayPhase === 'REVEALING';
                $rowCount      = $scores->count();
                $density       = $rowCount <= 4 ? 'd-big' : ($rowCount <= 5 ? 'd-mid' : ($rowCount <= 7 ? 'd-tight' : 'd-xs'));
            @endphp
            @if(!$displayPhase || $btLeaderboard)
            <div class="aud-mn">
              <div class="aud-fit">
                {{-- Dua banner ini SENGAJA independen dari $stage: begitu admin pilih finalis, live_stage
                     pindah ke FINAL, jadi pengumuman kualifikasi harus tetap tampil. --}}
                @if(($finalAnnounced ?? false) && ($podium ?? collect())->isNotEmpty())
                <div class="panel halftone" style="padding:16px 24px;text-align:center;border:2px solid var(--lime);flex:none;">
                    <span class="kicker" style="display:block;margin-bottom:10px;">🏆 PEMENANG FINAL — {{ strtoupper($division?->name ?? '') }}</span>
                    <div style="display:flex;justify-content:center;gap:24px;flex-wrap:wrap;">
                        @foreach($podium as $i => $row)
                            <div class="col center" style="gap:6px;">
                                <span class="display tnum" style="font-size:{{ $i === 0 ? '40px' : '28px' }};color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">#{{ $i + 1 }}</span>
                                <x-avatar :initials="$row['rider']->initials" :size="$i === 0 ? 56 : 44" :ring="$i === 0" />
                                <span class="label" style="font-size:14px;">{{ $row['rider']->name }}</span>
                                <span class="mono dim tnum" style="font-size:12px;">{{ number_format($row['total'], 1) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(($qualificationAnnounced ?? false) && !($showingQualRecap ?? false) && ($finalistRegIds ?? collect())->isNotEmpty())
                <div class="panel" style="padding:12px 20px;border:2px solid var(--lime);flex:none;">
                    <span class="mono" style="font-size:13px;color:var(--lime);display:block;margin-bottom:4px;">✅ HASIL KUALIFIKASI DIUMUMKAN — {{ $finalistRegIds->count() }} rider lolos ke FINAL</span>
                    <span class="mono dim" style="font-size:13px;">{{ ($finalistNames ?? collect())->implode(', ') }}</span>
                </div>
                @endif

                @if($scoreSections->isNotEmpty())
                    {{-- "Semua" group: 1 group = 1 tabel, berdampingan (maks 4 kolom per baris). Fitur ini dipertahankan. --}}
                    @php
                        $sectionCount = $scoreSections->count();
                        $sectionRows  = (int) ceil($sectionCount / 4);
                        $sectionCols  = $sectionRows > 0 ? (int) ceil($sectionCount / $sectionRows) : 1;
                        $sectionGap   = 20;
                    @endphp
                    <div style="display:flex;flex-wrap:wrap;gap:{{ $sectionGap }}px;align-items:flex-start;">
                        @foreach($scoreSections as $section)
                            <div class="panel" style="overflow:hidden;flex:1 1 calc((100% - {{ ($sectionCols - 1) * $sectionGap }}px)/{{ $sectionCols }});min-width:260px;">
                                <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                                    <span class="kicker">{{ strtoupper($section['group']?->name ?? 'BELUM ADA GROUP') }}</span>
                                </div>
                                @if($section['leaderboard']->isEmpty())
                                    <p class="mono dim" style="font-size:12px;padding:18px;">Belum ada rider (approved) di group ini.</p>
                                @else
                                    @include('livewire.partials.live-score-table', ['rows' => $section['leaderboard'], 'compact' => true])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('livewire.partials.live-score-table', ['rows' => $scores, 'density' => $density, 'markRiderId' => $btLeaderboard ? ($liveRider->id ?? null) : null])
                @endif


                {{-- Judge Cards disembunyikan di /live (nilai per juri tidak ditampilkan ke publik).
                         Ganti false jadi true untuk menampilkannya lagi setelah REVEAL. --}}
                    @if(false && $judgeScores->isNotEmpty() && $displayPhase === 'REVEALING')
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
            @endif {{-- !$displayPhase --}}
        @endif

        {{-- Sponsor strip: logo diposisikan bebas Operator (Admin → Sponsors, kanvas "leaderboard"). --}}
        <div class="aud-ft">
            @foreach($leaderboardPlacements as $p)
                @php
                    // Strip footer lebih pendek dari kanvas lama: jepit posisi supaya kotak logo selalu
                    // berada penuh di dalam strip (tidak terpotong di tepi).
                    $halfH = 33.4 * $p->size / 100;          // setengah tinggi logo, % tinggi strip (120u)
                    $halfW = (80 * $p->size / 100) / 19.2;   // setengah lebar logo, % lebar layar (1920u)
                    $px = min(max((float) $p->x, $halfW), 100 - $halfW);
                    $py = min(max((float) $p->y, $halfH), 100 - $halfH);
                @endphp
                <div style="position:absolute;left:{{ $px }}%;top:{{ $py }}%;transform:translate(-50%,-50%);
                    width:calc({{ round(160 * $p->size / 100) }} * var(--u));height:calc({{ round(80 * $p->size / 100) }} * var(--u));
                    display:flex;align-items:center;justify-content:center;padding:calc(10 * var(--u));">
                    @if($p->sponsor->logo)
                        <img src="{{ $p->sponsor->logo_url }}" alt="" style="max-width:100%;max-height:100%;object-fit:contain;" onerror="this.style.display='none'">
                    @else
                        {{-- Sponsor tanpa file logo: tampilkan namanya sebagai teks. --}}
                        <span class="display dim" style="font-size:max(calc(22*var(--u)),11px);text-align:center;line-height:1.1;">{{ $p->sponsor->name }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif {{-- idleScreen --}}

</div>

<script>
function liveBanner() {
    return {
        phase: 'IDLE',
        jam: false,
        bt: false,
        live: false,
        idleTitle: 'LIVE LEADERBOARD',
        _t: null,
        _obs: null,

        boot() {
            this.read();
            const el = document.getElementById('live-data');
            if (el) {
                this._obs = new MutationObserver(() => this.read());
                this._obs.observe(el, { attributes: true });
            }
            // RUNNING -> JUDGING terjadi di sisi klien saat timer habis, jadi cek tiap detik.
            this._t = setInterval(() => this.read(), 1000);
        },

        destroy() {
            clearInterval(this._t);
            this._obs?.disconnect();
        },

        read() {
            const el = document.getElementById('live-data');
            if (!el) return;
            liveClock.syncFrom(el);
            const server   = el.dataset.phase || '';
            const started  = parseInt(el.dataset.started) || 0;
            const duration = parseInt(el.dataset.duration) || 60;
            this.jam  = !!el.dataset.jam;
            this.bt   = !!el.dataset.isBestTrick;
            this.live = !!el.dataset.live;
            this.idleTitle = el.dataset.idleTitle || 'LIVE LEADERBOARD';

            if (server === 'RUNNING') {
                const over = !this.bt && started && (liveClock.now() / 1000 - started >= duration);
                this.phase = over ? 'JUDGING' : 'RUNNING';
            } else if (server === 'REVEALING') {
                this.phase = this.bt ? 'IDLE' : 'REVEALING'; // Best Trick: langsung leaderboard
            } else if (server === 'NEXT') {
                this.phase = 'NEXT';
            } else {
                this.phase = 'IDLE';
            }
        },

        get chip() {
            switch (this.phase) {
                case 'RUNNING':   return { t: this.bt ? '● TRICK IN PROGRESS' : (this.jam ? '● HEAT IN PROGRESS' : '● RUN IN PROGRESS'), k: 'live' };
                case 'JUDGING':   return { t: 'WAITING FOR JUDGES', k: 'warn' };
                case 'REVEALING': return { t: '✓ OFFICIAL RESULTS', k: 'ok' };
                case 'NEXT':      return { t: 'STANDBY', k: 'mut' };   // 'NEXT UP' cukup sekali, di banner
                default:          return this.live ? { t: '● LIVE', k: 'live' } : { t: 'OFFLINE', k: 'mut' };
            }
        },

        get chipStyle() {
            return {
                live: 'background:var(--red);border-color:var(--red);color:#fff;',
                warn: 'border-color:#ffb020;color:#ffb020;',
                ok:   'border-color:var(--lime);color:var(--lime);',
                mut:  'color:var(--ink-dim);',
            }[this.chip.k];
        },

        get title() {
            switch (this.phase) {
                case 'RUNNING':   return this.bt ? 'TRICK IN PROGRESS' : (this.jam ? 'HEAT IN PROGRESS' : 'RUN IN PROGRESS');
                case 'JUDGING':   return "TIME'S UP";
                case 'REVEALING': return this.jam ? 'HEAT RESULTS' : 'RUN SCORE';
                case 'NEXT':      return 'NEXT UP';
                default:          return this.idleTitle;
            }
        }
    };
}

window.liveClock = window.liveClock || {
    // Selisih jam server - klien. Latensi jaringan hanya membuat selisih terlihat lebih kecil,
    // jadi nilai terbesar yang pernah teramati = estimasi terbaik.
    offset: null,
    sync(serverMs) {
        const ms = parseInt(serverMs);
        if (!ms) return;
        const obs = ms - Date.now();
        if (this.offset === null || obs > this.offset) this.offset = obs;
    },
    syncFrom(el) {
        if (el && el.dataset.now && el.dataset.now !== this._lastNow) { this._lastNow = el.dataset.now; this.sync(el.dataset.now); }
    },
    now() { return Date.now() + (this.offset || 0); },
};

function stageFit(root) {
    // Perkecil isi zona konten (.aud-fit) hanya kalau lebih tinggi dari ruangnya; tidak ada scroll.
    let raf = null;
    const run = () => {
        raf = null;
        root.querySelectorAll('.aud-fit').forEach(fit => {
            const box = fit.parentElement;
            fit.style.setProperty('--s', '1');
            if (window.innerWidth < 700 || box.getClientRects().length === 0) return;
            const H = box.clientHeight, h = fit.offsetHeight;
            if (H > 0 && h > H + 1) fit.style.setProperty('--s', Math.max(0.3, H / h).toFixed(3));
        });
    };
    const sched = () => { if (!raf) raf = requestAnimationFrame(run); };
    new MutationObserver(recs => {
        // abaikan perubahan style yang kita buat sendiri di .aud-fit
        if (recs.every(r => r.type === 'attributes' && r.target.classList.contains('aud-fit'))) return;
        sched();
    }).observe(root, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['style', 'class'] });
    window.addEventListener('resize', sched);
    document.addEventListener('fullscreenchange', () => { sched(); setTimeout(sched, 300); });
    sched();
}

function liveFullscreen() {
    return {
        isFullscreen: false,

        init() {
            stageFit(this.$el);
            // Update realtime: pesan websocket membawa fase/waktu (diterapkan langsung ke chip & timer),
            // lalu satu refresh HTTP untuk data lengkap. Banyak pesan beruntun (mis. 5 juri submit
            // hampir bersamaan) digabung jadi satu refresh; jeda acak 0-150 ms cukup untuk
            // menyebar beban ke server tanpa terasa lambat (lama: sampai 600 ms).
            let pending = null;
            const refresh = () => {
                if (pending) return;
                pending = setTimeout(() => { pending = null; this.$wire.$refresh(); }, Math.random() * 150);
            };
            const applyEarly = (p) => {
                const el = document.getElementById('live-data');
                if (!el || !p) return;
                if (p.jam) {
                    el.dataset.jam = '1';
                    el.dataset.phase = p.phase || '';
                    el.dataset.started = p.started || 0;
                    el.dataset.duration = p.duration || 180;
                } else if (el.dataset.jam && !p.phase) {
                    el.dataset.phase = '';
                }
            };
            if (window.Echo) {
                let subscribed = null;
                const subscribe = () => {
                    const id = this.$wire.eventId;
                    if (!id || id === subscribed) return;
                    if (subscribed) window.Echo.leave(`live-scoring.${subscribed}`);
                    subscribed = id;
                    window.Echo.channel(`live-scoring.${id}`).listen('.updated', (p) => {
                        liveClock.sync(p?.t);
                        applyEarly(p);
                        refresh();
                    });
                };
                subscribe();
                // Event aktif diganti Admin saat halaman terbuka → pindah channel tanpa perlu reload.
                this.$wire.$watch('eventId', subscribe);

                // Ketahanan: sambung ulang setelah koneksi putus → sinkron ulang; selagi putus → poll tiap 5 detik.
                const conn = window.Echo.connector?.pusher?.connection;
                if (conn) {
                    let wasDown = false;
                    conn.bind('state_change', (st) => {
                        if (st.current === 'connected' && wasDown) { wasDown = false; refresh(); }
                        else if (st.current !== 'connected') wasDown = true;
                    });
                    this._fallback = setInterval(() => { if (conn.state !== 'connected') refresh(); }, 5000);
                }
                document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
            }

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

function jamClock() {
    return {
        jam: false,
        phase: 'IDLE',
        remaining: 0,
        _timer: null,
        _observer: null,
        _lastStartedAt: null,
        _sounds: null,

        get active() { return this.jam && this.phase !== 'IDLE'; },

        boot() {
            this._sounds = {
                start: new Audio('{{ asset('sounds/start.wav') }}'),
                tick: new Audio('{{ asset('sounds/tick.wav') }}'),
                end: new Audio('{{ asset('sounds/end.wav') }}'),
            };
            Object.values(this._sounds).forEach(a => { a.preload = 'auto'; a.load(); });
            const unlock = () => {
                Object.values(this._sounds).forEach(a => {
                    a.play().then(() => { a.pause(); a.currentTime = 0; }).catch(() => {});
                });
            };
            ['click', 'touchstart', 'keydown'].forEach(evt => {
                document.addEventListener(evt, unlock, { once: true });
            });

            this.readData();
            const el = document.getElementById('live-data');
            if (el) {
                this._observer = new MutationObserver(() => this.readData());
                this._observer.observe(el, { attributes: true });
            }
        },

        // jamClock() dipasang di dua tempat (timer layar idle + blok heat). Hanya satu instance yang
        // boleh bersuara, kalau tidak tick 10 detik terakhir dan bunyi habis terdengar dobel.
        _mayPlay() {
            this._sid = this._sid || Math.random().toString(36).slice(2);
            const o = window.__jamSoundOwner;
            if (!o || o.id === this._sid || !o.el || !o.el.isConnected) {
                window.__jamSoundOwner = { id: this._sid, el: this.$el };
                return true;
            }
            return false;
        },

        _playSound(name) {
            if (!this._mayPlay()) return;
            const audio = this._sounds?.[name];
            if (!audio) return;
            audio.currentTime = 0;
            audio.play().catch(err => console.warn('[live-scoring] sound blocked:', name, err));
        },

        readData() {
            const el = document.getElementById('live-data');
            if (!el) return;
            liveClock.syncFrom(el);
            this.jam = !!el.dataset.jam;
            const serverPhase = el.dataset.phase || '';
            const startedAt   = parseInt(el.dataset.started) || 0;
            const duration    = parseInt(el.dataset.duration) || 180;

            if (!this.jam) {
                clearTimeout(this._timer);
                this._timer = null;
                this.phase = 'IDLE';
            } else if (serverPhase === 'REVEALING') {
                clearTimeout(this._timer);
                this._timer = null;
                this.phase = 'REVEALING';
            } else if (serverPhase === 'RUNNING') {
                this._startCountdown(startedAt, duration);
            } else {
                clearTimeout(this._timer);
                this._timer = null;
                this.phase = 'IDLE';
            }
        },

        _startCountdown(startedAt, duration) {
            // Broadcast tiap judge submit memanggil readData() lagi — jangan restart
            // timer (dan bunyi start/tick) kalau heat yang sama sudah berjalan.
            if (startedAt === this._lastStartedAt && this._timer !== null) return;
            clearTimeout(this._timer);
            if (startedAt !== this._lastStartedAt) {
                this._lastStartedAt = startedAt;
                this._playSound('start');
            }
            let lastBeepedSecond = null;
            const tick = () => {
                const elapsed = Math.floor(liveClock.now() / 1000) - startedAt;
                this.remaining = Math.max(0, duration - elapsed);
                if (this.remaining > 0) {
                    this.phase = 'RUNNING';
                    if (this.remaining <= 10 && this.remaining !== lastBeepedSecond) {
                        lastBeepedSecond = this.remaining;
                        this._playSound('tick');
                    }
                    this._timer = setTimeout(tick, 1000);
                } else {
                    this.phase = 'JUDGING';
                    this._playSound('end');
                    this._timer = setTimeout(() => {}, 0); // tandai heat ini sudah selesai dihitung
                }
            };
            tick();
        },

        get remainingFormatted() {
            const h = Math.floor(this.remaining / 3600);
            const m = Math.floor((this.remaining % 3600) / 60);
            const s = this.remaining % 60;
            return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
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
        accumulated: '',
        bestScore: '',
        avatarSrc: '',
        eventTitle: '',
        runNumber: '',
        divisionLabel: '',
        isBestTrick: false,
        jam: false,
        _timer: null,
        _observer: null,
        _lastStartedAt: null,
        _sounds: null,

        boot() {
            this._sounds = {
                start: new Audio('{{ asset('sounds/start.wav') }}'),
                tick: new Audio('{{ asset('sounds/tick.wav') }}'),
                end: new Audio('{{ asset('sounds/end.wav') }}'),
            };
            Object.values(this._sounds).forEach(a => { a.preload = 'auto'; a.load(); });

            // Browsers block audio.play() until the page has received a real user
            // gesture. This screen is usually left open unattended (scoreboard/TV),
            // so the very first RUNNING phase can fire before any click happens —
            // prime (play+immediately pause) all sounds on the first interaction so
            // later autoplay-triggered plays are allowed.
            const unlock = () => {
                Object.values(this._sounds).forEach(a => {
                    a.play().then(() => { a.pause(); a.currentTime = 0; }).catch(() => {});
                });
            };
            ['click', 'touchstart', 'keydown'].forEach(evt => {
                document.addEventListener(evt, unlock, { once: true });
            });

            this.readData();
            const el = document.getElementById('live-data');
            if (el) {
                this._observer = new MutationObserver(() => this.readData());
                this._observer.observe(el, { attributes: true });
            }
        },

        _playSound(name) {
            const audio = this._sounds?.[name];
            if (!audio) return;
            audio.currentTime = 0;
            audio.play().catch(err => console.warn('[live-scoring] sound blocked:', name, err));
        },

        // Best Trick: begitu REVEAL, kartu rider/skor disembunyikan dan yang tampil
        // langsung leaderboard publik.
        get btHide() {
            return this.isBestTrick && (this.phase === 'REVEALING' || this.phase === 'RUNNING');
        },

        readData() {
            const el = document.getElementById('live-data');
            if (!el) return;
            liveClock.syncFrom(el);
            const serverPhase = el.dataset.phase || '';
            const startedAt   = parseInt(el.dataset.started) || 0;
            const duration    = parseInt(el.dataset.duration) || 60;
            this.riderName  = el.dataset.rider || '';
            this.initials   = el.dataset.initials || '';
            this.score      = el.dataset.score || '';
            this.accumulated = el.dataset.accumulated || '';
            this.bestScore  = el.dataset.best || '';
            this.avatarSrc  = el.dataset.avatar || '';
            this.eventTitle = el.dataset.event || '';
            this.runNumber  = el.dataset.run || '';
            this.divisionLabel = el.dataset.divisionLabel || '';
            this.isBestTrick = !!el.dataset.isBestTrick;
            this.jam = !!el.dataset.jam;

            if (this.jam) {
                // Heat Jamming ditangani jamClock() — kartu esports per-rider tidak dipakai.
                clearTimeout(this._timer);
                this._timer = null;
                this.phase = 'IDLE';
            } else if (serverPhase === 'REVEALING') {
                // Best Trick: bunyi 'end' saat percobaan selesai (RUNNING -> REVEALING)
                if (this.phase === 'RUNNING' && this.isBestTrick) this._playSound('end');
                this.phase = 'REVEALING';
                clearTimeout(this._timer);
            } else if (serverPhase === 'RUNNING' && this.isBestTrick) {
                // Best Trick itu cuma 1 trick per percobaan, bukan run bertimer —
                // gak ada hitung mundur, dan gak auto-pindah ke JUDGING kayak
                // _startCountdown; tetap di RUNNING sampai Head Judge klik Reveal.
                clearTimeout(this._timer);
                if (startedAt !== this._lastStartedAt) {
                    this._lastStartedAt = startedAt;
                    this._playSound('start');
                }
                this.phase = 'RUNNING';
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
            // readData() re-runs on every broadcast for this event — including
            // ones unrelated to this run, like a judge submitting their score
            // while this run is still counting down. If it's the same run and
            // the tick loop is already going, leave it alone: restarting it
            // would replay the last-10-seconds beep (or "start") for no reason.
            if (startedAt === this._lastStartedAt && this._timer !== null) {
                return;
            }
            clearTimeout(this._timer);
            if (startedAt !== this._lastStartedAt) {
                this._lastStartedAt = startedAt;
                this._playSound('start');
            }
            let lastBeepedSecond = null;
            const tick = () => {
                const elapsed = Math.floor(liveClock.now() / 1000) - startedAt;
                this.remaining = Math.max(0, duration - elapsed);
                if (this.remaining > 0) {
                    this.phase = 'RUNNING';
                    if (this.remaining <= 10 && this.remaining !== lastBeepedSecond) {
                        lastBeepedSecond = this.remaining;
                        this._playSound('tick');
                    }
                    this._timer = setTimeout(tick, 1000);
                } else {
                    this.phase = 'JUDGING';
                    this._playSound('end');
                }
            };
            tick();
        },

        get remainingFormatted() {
            const h = Math.floor(this.remaining / 3600);
            const m = Math.floor((this.remaining % 3600) / 60);
            const s = this.remaining % 60;
            return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
        }
    };
}
</script>

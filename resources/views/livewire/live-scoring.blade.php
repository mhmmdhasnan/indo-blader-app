<div wire:poll.30s x-data="liveFullscreen()">
    {{-- Hidden data bridge: Livewire updates these attrs; Alpine reads them via MutationObserver --}}
    <div id="live-data"
         data-phase="{{ $displayPhase ?? '' }}"
         data-started="{{ $liveStartedAt ?? 0 }}"
         data-duration="{{ $runDuration }}"
         data-score="{{ $revealScore ? number_format($revealScore, 1) : '' }}"
         data-accumulated="{{ ($revealAccumulatedTotal ?? null) !== null ? number_format($revealAccumulatedTotal, 1) : '' }}"
         data-rider="{{ $liveRider?->name ?? '' }}"
         data-initials="{{ $liveRider ? collect(explode(' ', $liveRider->name))->map(fn($w) => strtoupper($w[0]))->take(2)->join('') : '' }}"
         data-avatar="{{ $liveRider?->avatar ? asset('storage/' . $liveRider->avatar) : '' }}"
         data-event="{{ $event?->title ?? '' }}"
         data-run="{{ $event?->live_run_number ?? '' }}"
         data-best="{{ $liveRiderBestScore !== null ? number_format($liveRiderBestScore, 1) : '' }}"
         data-division-label="{{ $liveDivisionLabel }}"
         data-is-best-trick="{{ ($isBestTrickPhase ?? false) ? '1' : '' }}"
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

    {{-- Header --}}
    <div style="border-bottom:2px solid var(--ink);background:var(--bg-2);">
        <div class="wrap {{ $scoreSections->isNotEmpty() ? 'wrap-wide' : '' }} between" style="padding-block:20px;flex-wrap:wrap;gap:12px;">
            <div class="col">
                <div class="flex gap-s" style="margin-bottom:6px;">
                    @if($event && $event->status === 'LIVE')
                        <span class="badge badge-red"><span class="live-dot"></span>LIVE</span>
                    @else
                        <span class="badge badge-out">OFFLINE</span>
                    @endif
                    <span class="badge badge-out">{{ $division ? strtoupper($division->name) . ' · ' . ($stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI') : 'SEMUA DIVISI' }}</span>
                </div>
                <h1 class="display" style="font-size:clamp(22px,5vw,48px);">
                    {{ $event ? $event->title . ' — Live Score' : 'Live Scoring' }}
                </h1>
            </div>
            <div class="col" style="gap:8px;align-items:flex-end;">
                <div class="mono dim" style="font-size:10px;letter-spacing:0.1em;text-align:right;" x-show="!isFullscreen">
                    MENGIKUTI PILIHAN OPERATOR
                </div>
                @if($divisions->isNotEmpty())
                    <div class="flex gap-s" style="flex-wrap:wrap;justify-content:flex-end;" x-show="!isFullscreen">
                        <span class="mono" style="padding:8px 12px;border:2px solid var(--ink);background:var(--bg);color:var(--ink);font-size:12px;letter-spacing:0.08em;border-radius:3px;">
                            {{ $division?->name ?? '— Belum dipilih Operator —' }}
                        </span>
                        @if($division)
                            <span class="mono" style="padding:6px 10px;border:2px solid var(--ink);background:{{ $stage === 'FINAL' ? 'var(--lime)' : 'var(--bg)' }};color:{{ $stage === 'FINAL' ? '#0a0a0b' : 'var(--ink)' }};font-size:10px;letter-spacing:0.08em;border-radius:3px;">
                                {{ $stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI' }}
                            </span>
                        @endif
                        @if($stage === 'QUALIFICATION' && $groups->isNotEmpty())
                            <span class="mono" style="padding:6px 10px;border:2px solid var(--ink);background:var(--bg);color:var(--ink);font-size:11px;letter-spacing:0.08em;border-radius:3px;">
                                {{ $groups->firstWhere('id', $selectedGroupId)?->name ?? 'Semua Group' }}
                            </span>
                        @endif
                    </div>
                @endif
                <div class="mono dim" style="font-size:11px;letter-spacing:0.12em;text-align:right;">
                    <span style="color:var(--red);">● SYSTEM LIVE</span>
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
            <div style="position:relative;display:grid;grid-template-columns:3fr 3fr 6fr;height:calc(100vh - 130px);overflow:hidden;border-left:2px solid var(--ink);border-right:2px solid var(--ink);">

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

                    {{-- NEXT --}}
                    <div x-show="phase === 'NEXT'" style="display:none;text-align:center;">
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
                    <div x-show="phase === 'RUNNING' && isBestTrick" style="display:none;text-align:center;">
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

    @if(!$event)
        <div class="wrap section center col" style="padding-block:80px;gap:16px;">
            <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
            <span class="kicker">PILIH EVENT</span>
            <p class="mono dim" style="font-size:13px;text-align:center;max-width:320px;">
                Pilih event di atas untuk melihat live score.
            </p>
        </div>
    @elseif($scores->isEmpty() && $scoreSections->isEmpty())
        <div class="wrap section center col" style="padding-block:80px;gap:16px;">
            <span class="display" style="font-size:48px;color:var(--ink-faint);">—</span>
            <span class="kicker">BELUM ADA PESERTA</span>
            <p class="mono dim" style="font-size:13px;text-align:center;max-width:360px;">
                Belum ada peserta approved untuk <strong>{{ $event->title }}</strong>.
            </p>
        </div>
    @else
        <div class="wrap {{ $scoreSections->isNotEmpty() ? 'wrap-wide' : '' }} section" style="padding-top:30px;">
            <div class="col" style="gap:20px;">
                    @if(!$displayPhase)

                    {{-- Fixed-height freeform zone — selalu dapat ruang di atas
                         leaderboard/"ADVANCING TO FINAL" biar Operator punya tempat naruh
                         sponsor (Admin → Sponsors, drag logo ke canvas "leaderboard"),
                         gak perlu nunggu ada placement dulu baru ruangnya muncul.
                         Kalau belum ada sponsor yang dipasang, cuma jadi spacer polos
                         (gak ada background/border) biar gak keliatan kotak kosong aneh
                         buat penonton. --}}
                    <div class="{{ $leaderboardPlacements->isNotEmpty() ? 'panel' : '' }}" style="position:relative;height:110px;overflow:hidden;">
                        @foreach($leaderboardPlacements as $p)
                            <div style="position:absolute;left:{{ $p->x }}%;top:{{ $p->y }}%;transform:translate(-50%,-50%);
                                width:{{ round(160 * $p->size / 100) }}px;height:{{ round(80 * $p->size / 100) }}px;
                                display:flex;align-items:center;justify-content:center;padding:10px;">
                                @if($p->sponsor->logo)
                                    <img src="{{ $p->sponsor->logo_url }}" alt="{{ $p->sponsor->name }}" style="max-width:100%;max-height:100%;object-fit:contain;">
                                @else
                                    <span class="display dim" style="font-size:14px;text-align:center;">{{ $p->sponsor->name }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Dua banner ini SENGAJA gak digantung ke $stage saat ini — begitu
                         admin pilih finalis, live_stage divisi langsung pindah ke FINAL,
                         jadi kalau pengumuman kualifikasi cuma tampil selagi $stage masih
                         QUALIFICATION, operator gak akan pernah sempat lihat banner ini
                         (keburu ganti ke leaderboard FINAL). Keduanya independen dan bisa
                         tampil bersamaan sesuai tombol yang dipencet Operator. --}}
                    @if(($finalAnnounced ?? false) && ($podium ?? collect())->isNotEmpty())
                    <div class="panel halftone" style="padding:24px;text-align:center;border:2px solid var(--lime);">
                        <span class="kicker" style="display:block;margin-bottom:14px;">🏆 PEMENANG FINAL — {{ strtoupper($division?->name ?? '') }}</span>
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
                    {{-- Kalau tabel di bawah lagi nampilin recap skor kualifikasi para
                         finalis (lihat $showingQualRecap), judul tabelnya sendiri sudah
                         cukup menjelaskan ("LANJUT KE FINAL") — banner ini cuma perlu
                         tampil kalau tabelnya BUKAN recap tsb (misal masih di fase
                         kualifikasi, atau final-nya udah beneran mulai jalan). --}}
                    @if(($qualificationAnnounced ?? false) && !($showingQualRecap ?? false) && ($finalistRegIds ?? collect())->isNotEmpty())
                    <div class="panel" style="padding:16px 20px;border:2px solid var(--lime);">
                        <span class="mono" style="font-size:12px;color:var(--lime);display:block;margin-bottom:6px;">✅ HASIL KUALIFIKASI DIUMUMKAN — {{ $finalistRegIds->count() }} rider lolos ke FINAL</span>
                        <span class="mono dim" style="font-size:12px;">{{ ($finalistNames ?? collect())->implode(', ') }}</span>
                    </div>
                    @endif

                    @if($scoreSections->isNotEmpty())
                        {{-- "Semua" group dipilih & divisi ini punya group — 1 group = 1 tabel,
                             ditampilkan berdampingan (kiri-kanan), maksimal 4 kolom per baris
                             (mis. 8 group = 4 di atas, 4 di bawah; 6 group = 3 di atas, 3 di
                             bawah). Pakai flexbox + flex-grow supaya baris terakhir yang gak
                             penuh tetap melebar mengisi ruang — gak pernah nyisa kosong di
                             kanan/kiri. Tabelnya sendiri pakai container query (lihat
                             .score-scroll di app.css) supaya kalau kolomnya jadi sempit,
                             tabel otomatis ciutkan kolom RUN alih-alih nampilin scrollbar. --}}
                        @php
                            $sectionCount = $scoreSections->count();
                            $sectionRows  = (int) ceil($sectionCount / 4);
                            $sectionCols  = $sectionRows > 0 ? (int) ceil($sectionCount / $sectionRows) : 1;
                            $sectionGap   = 20;
                        @endphp
                        <div style="display:flex;flex-wrap:wrap;gap:{{ $sectionGap }}px;align-items:flex-start;">
                            @foreach($scoreSections as $section)
                                <div class="panel" style="overflow:hidden;flex:1 1 calc((100% - {{ ($sectionCols - 1) * $sectionGap }}px)/{{ $sectionCols }});min-width:260px;">
                                    <div style="padding:16px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                                        <span class="kicker">LIVE LEADERBOARD · {{ strtoupper($section['group']?->name ?? 'BELUM ADA GROUP') }}</span>
                                    </div>
                                    @if($section['leaderboard']->isEmpty())
                                        <p class="mono dim" style="font-size:12px;padding:18px;">Belum ada skor masuk untuk group ini.</p>
                                    @else
                                        @include('livewire.partials.live-score-table', ['rows' => $section['leaderboard'], 'compact' => true])
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                    <div class="panel" style="overflow:hidden;">
                        <div style="padding:16px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                            <span class="kicker">{{ ($showingQualRecap ?? false) ? 'ADVANCING TO FINAL' : 'LIVE LEADERBOARD' }}</span>
                        </div>
                        @include('livewire.partials.live-score-table', ['rows' => $scores])
                    </div>
                    @endif
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
    @endif {{-- idleScreen --}}
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
        accumulated: '',
        bestScore: '',
        avatarSrc: '',
        eventTitle: '',
        runNumber: '',
        divisionLabel: '',
        isBestTrick: false,
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

        readData() {
            const el = document.getElementById('live-data');
            if (!el) return;
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

            if (serverPhase === 'REVEALING') {
                this.phase = 'REVEALING';
                clearTimeout(this._timer);
            } else if (serverPhase === 'RUNNING' && this.isBestTrick) {
                // Best Trick itu cuma 1 trick per percobaan, bukan run bertimer —
                // gak ada hitung mundur, dan gak auto-pindah ke JUDGING kayak
                // _startCountdown; tetap di RUNNING sampai Head Judge klik Reveal.
                clearTimeout(this._timer);
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
                const elapsed = Math.floor(Date.now() / 1000) - startedAt;
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

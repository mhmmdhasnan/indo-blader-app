{{-- Leaderboard divisi/group aktif (Operator & Head Judge). Expects $activeDiv, $activeDivName, $operatorLeaderboard(Sections), $judgeDivisionId, $scoringMode --}}
    @if(($isOperator || $isHeadJudge) && $scoringMode === 'live' && $judgeDivisionId)
        @if(($operatorLeaderboardSections ?? collect())->isNotEmpty())
            {{-- "Semua" group dipilih & divisi ini punya group — 1 group = 1 tabel,
                 ditampilkan berdampingan (kiri-kanan), maksimal 4 kolom per baris (mis.
                 8 group = 4 di atas, 4 di bawah; 6 group = 3+3). Flexbox + flex-grow
                 supaya baris terakhir yang gak penuh tetap melebar, gak nyisa kosong.
                 Tabelnya pakai container query (.score-scroll di app.css) buat ciutkan
                 kolom RUN otomatis kalau ruangnya sempit, alih-alih nampilin scrollbar. --}}
            @php
                $opSectionCount = $operatorLeaderboardSections->count();
                $opSectionRows  = (int) ceil($opSectionCount / 4);
                $opSectionCols  = $opSectionRows > 0 ? (int) ceil($opSectionCount / $opSectionRows) : 1;
                $opSectionGap   = 16;
            @endphp
            <div style="display:flex;flex-wrap:wrap;gap:{{ $opSectionGap }}px;align-items:flex-start;">
                @foreach($operatorLeaderboardSections as $section)
                    <div class="panel" style="overflow:hidden;flex:1 1 calc((100% - {{ ($opSectionCols - 1) * $opSectionGap }}px)/{{ $opSectionCols }});min-width:240px;">
                        <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                            <span class="kicker">📊 LEADERBOARD — {{ strtoupper($activeDivName ?? '') }} · KUALIFIKASI · {{ strtoupper($section['group']?->name ?? 'BELUM ADA GROUP') }}</span>
                        </div>
                        @if($section['leaderboard']->isEmpty())
                            <p class="mono dim" style="font-size:12px;padding:18px;">Belum ada rider (approved) di group ini.</p>
                        @else
                            @include('livewire.partials.score-table', ['rows' => $section['leaderboard']])
                        @endif
                    </div>
                @endforeach
            </div>
        @else
        <div class="panel" style="overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                <span class="kicker">📊 LEADERBOARD — {{ strtoupper($activeDivName ?? '') }}{{ ($activeDiv ?? null)?->live_stage === 'FINAL' ? ' · FINAL' : ' · KUALIFIKASI' }}</span>
            </div>
            @if(($operatorLeaderboard ?? collect())->isEmpty())
                <p class="mono dim" style="font-size:12px;padding:18px;">Belum ada rider (approved) di divisi/group ini.</p>
            @else
                @include('livewire.partials.score-table', ['rows' => $operatorLeaderboard])
            @endif
        </div>
        @endif
    @endif

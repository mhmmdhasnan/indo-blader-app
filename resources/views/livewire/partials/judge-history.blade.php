{{-- Riwayat nilai yang sudah diberikan judge ini sendiri (Live + Best Trick), per divisi. Expects $myPastScoresByDivision --}}
@if(($myPastScoresByDivision ?? collect())->isNotEmpty())
            {{-- Riwayat nilai yang sudah diberikan judge ini sendiri (Live + Best Trick), per divisi --}}
            @foreach($myPastScoresByDivision as $divName => $rows)
                <div class="panel" style="overflow:hidden;margin-top:16px;">
                    <div class="between" style="padding:14px 18px;border-bottom:2px solid var(--ink);background:var(--bg-2);">
                        <span class="kicker">📝 NILAI SAYA SEBELUMNYA · {{ strtoupper($divName) }}</span>
                        <span class="mono dim" style="font-size:10px;">{{ $rows->count() }} NILAI</span>
                    </div>
                    @foreach($rows as $past)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;{{ !$loop->last ? 'border-bottom:1px solid var(--line);' : '' }}">
                            <div class="col" style="gap:2px;">
                                <span class="label" style="font-size:14px;">{{ $past->rider?->name ?? '—' }}</span>
                                <span class="mono dim" style="font-size:10px;letter-spacing:0.08em;">
                                    @if($past->scoring_mode === 'BEST_TRICK')
                                        BEST TRICK · PERCOBAAN {{ $past->run_number }}
                                    @else
                                        RUN {{ $past->run_number }} · {{ $past->live_stage === 'FINAL' ? 'FINAL' : 'KUALIFIKASI' }}
                                    @endif
                                </span>
                            </div>
                            <span class="display tnum" style="font-size:22px;">{{ number_format($past->total, 1) }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
@endif

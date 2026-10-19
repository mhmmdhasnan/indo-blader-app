{{-- Reusable public leaderboard table body — expects $rows (buildLeaderboard() rows).
     Inherits $displayPhase, $event, $stage, $qualificationAnnounced, $finalistRegIds
     from the including view's scope. Pass compact=>true when this table sits inside a
     narrower multi-group column (smaller avatar/type so names don't wrap). --}}
@php
    $compact     = $compact ?? false;
    $avatarSize  = $compact ? 36 : 64;
    $numSize     = $compact ? '26px' : 'clamp(28px,3.4vw,50px)';
    $nameSize    = $compact ? '14px' : 'clamp(16px,2vw,28px)';
    $valSize     = $compact ? '14px' : 'clamp(16px,1.8vw,26px)';
    $totalSize   = $compact ? '22px' : 'clamp(24px,3.4vw,50px)';
    // Narrow cards only have room for one RUN column: show the latest run that has scores
    // (RUN 1 until anyone in the table has a RUN 2), instead of always RUN 2.
    $latestLabel = collect($rows)->contains(fn ($r) => $r['run2'] !== null) ? 'RUN 2' : 'RUN 1';
@endphp
<div class="score-scroll {{ $compact ? '' : 'lsc-big' }}">
    <div class="score-header">
        @foreach([['#',''],['RIDER',''],['RUN 1','lsc-run1'],['RUN 2','lsc-run2'],[$latestLabel,'lsc-latest'],['BEST','']] as $i => [$h, $cls])
            <span class="mono {{ $cls }}"
                style="font-size:{{ $compact ? 10 : 16 }}px;letter-spacing:0.12em;color:var(--ink-dim);text-align:{{ $i >= 2 ? 'right' : 'left' }};">{{ $h }}</span>
        @endforeach
    </div>
    @foreach($rows as $i => $row)
        @php $isOnCourse = $displayPhase === 'RUNNING' && $event?->live_rider_id && ($row['rider']->id === $event->live_rider_id); @endphp
        <div class="score-row" style="
            border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};
            background:{{ $isOnCourse ? 'color-mix(in srgb,var(--red) 8%,transparent)' : 'transparent' }};
            border-left:{{ $isOnCourse ? '3px solid var(--red)' : '3px solid transparent' }};
        ">
            <span class="display tnum" style="font-size:{{ $numSize }};color:{{ $i === 0 ? 'var(--lime)' : ($i < 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
            <div class="flex" style="align-items:center;gap:{{ $compact ? 10 : 14 }}px;min-width:0;">
                <x-avatar :initials="$row['rider']->initials" :size="$avatarSize" :ring="$i === 0" />
                <div class="col" style="min-width:0;">
                    <span class="label" style="font-size:{{ $nameSize }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                        {{ $row['rider']->name }}
                        @if($stage === 'QUALIFICATION' && ($qualificationAnnounced ?? false) && ($finalistRegIds ?? collect())->contains($row['registration']->id ?? null))
                            <span class="badge badge-lime" style="font-size:9px;margin-left:6px;vertical-align:middle;">LOLOS</span>
                        @endif
                    </span>
                    <span class="mono dim" style="font-size:{{ $compact ? 10 : 15 }}px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $isOnCourse ? '🔴 ON COURSE' : ($row['rider']->city ?? '—') }}
                    </span>
                </div>
            </div>
            <span class="mono tnum lsc-run1" style="font-size:{{ $valSize }};text-align:right;color:var(--ink-dim);">{{ (!$isOnCourse && $row['run1'] !== null) ? number_format($row['run1'], 1) : '—' }}</span>
            <span class="mono tnum lsc-run2" style="font-size:{{ $valSize }};text-align:right;color:{{ $isOnCourse ? 'var(--red)' : 'var(--ink-dim)' }};">
                {{ $isOnCourse ? '...' : ($row['run2'] !== null ? number_format($row['run2'], 1) : '—') }}
            </span>
            @php $latest = $row['run2'] ?? $row['run1']; @endphp
            <span class="mono tnum lsc-latest" style="font-size:{{ $valSize }};text-align:right;color:{{ $isOnCourse ? 'var(--red)' : 'var(--ink-dim)' }};">
                {{ $isOnCourse ? '...' : ($latest !== null ? number_format($latest, 1) : '—') }}
            </span>
            @php $rowTotal = $row['total'] ?? $row['best']; @endphp
            {{-- Nominal bonus Best Trick sengaja gak ditampilkan di sini — publik cuma
                 lihat total akhir; breakdown-nya cuma buat Head Judge/Operator (lihat
                 partials/judging-panel.blade.php). --}}
            <span class="display tnum" style="font-size:{{ $totalSize }};text-align:right;color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">{{ (!$isOnCourse && $rowTotal > 0) ? number_format($rowTotal, 1) : '—' }}</span>
        </div>
    @endforeach
</div>

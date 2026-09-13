{{-- Reusable public leaderboard table body — expects $rows (buildLeaderboard() rows).
     Inherits $displayPhase, $event, $stage, $qualificationAnnounced, $finalistRegIds
     from the including view's scope. Pass compact=>true when this table sits inside a
     narrower multi-group column (smaller avatar/type so names don't wrap). --}}
@php
    $compact     = $compact ?? false;
    $avatarSize  = $compact ? 36 : 48;
    $numSize     = $compact ? 26 : 34;
    $nameSize    = $compact ? 14 : 18;
    $valSize     = $compact ? 14 : 18;
    $totalSize   = $compact ? 22 : 30;
@endphp
<div class="score-scroll">
    <div class="score-header">
        @foreach(['#','RIDER','RUN 1','RUN 2','BEST'] as $i => $h)
            <span class="mono {{ $h === 'RUN 1' ? 'score-hide' : ($h === 'RUN 2' ? 'score-hide-2' : '') }}"
                style="font-size:{{ $compact ? 10 : 13 }}px;letter-spacing:0.12em;color:var(--ink-dim);text-align:{{ $i >= 2 ? 'right' : 'left' }};">{{ $h }}</span>
        @endforeach
    </div>
    @foreach($rows as $i => $row)
        @php $isOnCourse = $displayPhase === 'RUNNING' && $event?->live_rider_id && ($row['rider']->id === $event->live_rider_id); @endphp
        <div class="score-row" style="
            border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};
            background:{{ $isOnCourse ? 'color-mix(in srgb,var(--red) 8%,transparent)' : 'transparent' }};
            border-left:{{ $isOnCourse ? '3px solid var(--red)' : '3px solid transparent' }};
        ">
            <span class="display tnum" style="font-size:{{ $numSize }}px;color:{{ $i === 0 ? 'var(--lime)' : ($i < 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
            <div class="flex" style="align-items:center;gap:{{ $compact ? 10 : 14 }}px;min-width:0;">
                <x-avatar :initials="$row['rider']->initials" :size="$avatarSize" :ring="$i === 0" />
                <div class="col" style="min-width:0;">
                    <span class="label" style="font-size:{{ $nameSize }}px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                        {{ $row['rider']->name }}
                        @if($stage === 'QUALIFICATION' && ($qualificationAnnounced ?? false) && ($finalistRegIds ?? collect())->contains($row['registration']->id ?? null))
                            <span class="badge badge-lime" style="font-size:9px;margin-left:6px;vertical-align:middle;">LOLOS</span>
                        @endif
                    </span>
                    <span class="mono dim" style="font-size:{{ $compact ? 10 : 12 }}px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $isOnCourse ? '🔴 ON COURSE' : ($row['rider']->city ?? '—') }}
                    </span>
                </div>
            </div>
            <span class="mono tnum score-hide" style="font-size:{{ $valSize }}px;text-align:right;color:var(--ink-dim);">{{ (!$isOnCourse && $row['run1'] !== null) ? number_format($row['run1'], 1) : '—' }}</span>
            <span class="mono tnum score-hide-2" style="font-size:{{ $valSize }}px;text-align:right;color:{{ $isOnCourse ? 'var(--red)' : 'var(--ink-dim)' }};">
                {{ $isOnCourse ? '...' : ($row['run2'] !== null ? number_format($row['run2'], 1) : '—') }}
            </span>
            @php $rowTotal = $row['total'] ?? $row['best']; @endphp
            {{-- Nominal bonus Best Trick sengaja gak ditampilkan di sini — publik cuma
                 lihat total akhir; breakdown-nya cuma buat Head Judge/Operator (lihat
                 partials/judging-panel.blade.php). --}}
            <span class="display tnum" style="font-size:{{ $totalSize }}px;text-align:right;color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">{{ (!$isOnCourse && $rowTotal > 0) ? number_format($rowTotal, 1) : '—' }}</span>
        </div>
    @endforeach
</div>

{{-- Reusable leaderboard table body — expects $rows (collection of buildLeaderboard() rows) --}}
<div class="score-scroll">
    <div class="score-header">
        @foreach(['#','RIDER','RUN 1','RUN 2','BEST'] as $i => $h)
            <span class="mono {{ $h === 'RUN 1' ? 'score-hide' : ($h === 'RUN 2' ? 'score-hide-2' : '') }}"
                style="font-size:10px;letter-spacing:0.12em;color:var(--ink-dim);text-align:{{ $i >= 2 ? 'right' : 'left' }};">{{ $h }}</span>
        @endforeach
    </div>
    @foreach($rows as $i => $row)
        <div class="score-row" style="border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};">
            <span class="display tnum" style="font-size:26px;color:{{ $i === 0 ? 'var(--lime)' : ($i < 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</span>
            <div class="flex" style="align-items:center;gap:12px;">
                <x-avatar :initials="$row['rider']->initials" :size="36" :ring="$i === 0" />
                <span class="label" style="font-size:14px;">{{ $row['rider']->name }}</span>
            </div>
            <span class="mono tnum score-hide" style="font-size:14px;text-align:right;color:var(--ink-dim);">{{ $row['run1'] !== null ? number_format($row['run1'], 1) : '—' }}</span>
            <span class="mono tnum score-hide-2" style="font-size:14px;text-align:right;color:var(--ink-dim);">{{ $row['run2'] !== null ? number_format($row['run2'], 1) : '—' }}</span>
            <span class="display tnum" style="font-size:22px;text-align:right;color:{{ $i === 0 ? 'var(--lime)' : 'var(--ink)' }};">
                {{ $row['total'] > 0 ? number_format($row['total'], 1) : '—' }}
                @if(($row['best_trick_bonus'] ?? 0) > 0)
                    <span class="mono dim" style="font-size:10px;">(+{{ number_format($row['best_trick_bonus'], 1) }})</span>
                @endif
            </span>
        </div>
    @endforeach
</div>

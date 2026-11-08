{{-- Tabel leaderboard publik /live.
     - compact=true  : kolom sempit dalam deretan group berdampingan (markup lama, kolom RUN menciut otomatis).
     - default       : baris besar AudienceDisplay; $density = d-big|d-mid|d-tight|d-xs menurut jumlah rider.
     Inherits $displayPhase, $event, $stage, $qualificationAnnounced, $finalistRegIds dari view pemanggil.
     $winnerRow=true : baris juara (peringkat 1) lime penuh — dipakai di hasil heat.
     $allRows        : (opsional) semua baris, dipakai menghitung peringkat kalau $rows hanya satu halaman.
     $markRiderId    : (opsional) id rider yang diberi bingkai kuning "berikutnya" (layar NEXT UP). --}}
@php
    $compact     = $compact ?? false;
    $winnerRow   = $winnerRow ?? false;
    $density     = $density ?? 'd-big';
    $totals      = collect($allRows ?? $rows)->map(fn ($r) => (float) ($r['total'] ?? $r['best'] ?? 0));
    $markRiderId = $markRiderId ?? null;
    $oneRun      = ($division?->format ?? null) === 'JAMMING';   // Jamming: 1 run saja → kolom Run 2 disembunyikan
    $latestLabel = collect($rows)->contains(fn ($r) => $r['run2'] !== null) ? 'RUN 2' : 'RUN 1';
    $avatarSize  = 36;
    $numSize     = '26px';
    $nameSize    = '14px';
    $valSize     = '14px';
    $totalSize   = '22px';
@endphp
@if(!$compact)
<div class="at {{ $density }} {{ $oneRun ? 'one-run' : '' }}">
    <div class="at-row h"><span>#</span><span>RIDER</span><span>{{ $oneRun ? 'RUN' : 'RUN 1' }}</span>@unless($oneRun)<span>RUN 2</span>@endunless<span>BEST</span></div>
    @foreach($rows as $i => $row)
        @php
            $isOnCourse = $displayPhase === 'RUNNING' && $event?->live_rider_id && ($row['rider']->id === $event->live_rider_id);
            $rowTotal   = (float) ($row['total'] ?? $row['best'] ?? 0);
            $rank       = $rowTotal > 0 ? 1 + $totals->filter(fn ($v) => $v > $rowTotal)->count() : $i + 1;   // seri berbagi nomor peringkat
            $isWinner   = $winnerRow && $rank === 1 && $rowTotal > 0;
            $isLast     = $loop->last && $loop->count > 3 && $rowTotal > 0;
            $isFinalist = $stage === 'QUALIFICATION' && ($qualificationAnnounced ?? false) && ($finalistRegIds ?? collect())->contains($row['registration']->id ?? null);
        @endphp
        <div class="at-row {{ $isWinner ? 'lsc-win' : '' }} {{ $isOnCourse ? 'on' : '' }} {{ ($markRiderId && $row['rider']->id === $markRiderId) ? 'me' : '' }}">
            <span class="at-rk" style="color:{{ $rank === 1 ? 'var(--lime)' : ($isLast ? 'var(--ink-faint)' : 'var(--ink)') }};">{{ str_pad($rank, 2, '0', STR_PAD_LEFT) }}</span>
            <div class="at-rd">
                <i class="at-av">{{ $row['rider']->initials }}</i>
                <div style="min-width:0;">
                    <div class="at-nm">{{ $row['rider']->name }}@if($isFinalist)<span class="at-lolos">LOLOS</span>@endif</div>
                    <div class="at-ct">{{ $isOnCourse ? '● ON COURSE' : ($row['rider']->city ?? '—') }}</div>
                </div>
            </div>
            @php
                // On course = run sedang berjalan & juri belum menilai, jadi skor yang ada adalah skor run sebelumnya:
                // tetap ditampilkan. Slot run yang sedang berjalan (belum ada nilainya) diberi "...".
                $curRun = $isOnCourse ? (int) ($event->live_run_number ?: 1) : 0;
            @endphp
            <span class="at-r" @if($curRun === 1 && $row['run1'] === null) style="color:var(--red);" @endif>{{ $row['run1'] !== null ? number_format($row['run1'], 1) : ($curRun === 1 ? '...' : '—') }}</span>
            @unless($oneRun)
            <span class="at-r" @if($curRun === 2 && $row['run2'] === null) style="color:var(--red);" @endif>{{ $row['run2'] !== null ? number_format($row['run2'], 1) : ($curRun === 2 ? '...' : '—') }}</span>
            @endunless
            {{-- Bonus Best Trick sengaja tidak ditampilkan ke publik — hanya total akhir. --}}
            <span class="at-b" style="color:{{ $rank === 1 ? 'var(--lime)' : 'var(--ink)' }};">{{ $rowTotal > 0 ? number_format($rowTotal, 1) : '—' }}</span>
        </div>
    @endforeach
</div>
@else
<div class="score-scroll {{ $compact ? '' : 'lsc-big' }}">
    <div class="score-header">
        @foreach([['#',''],['RIDER',''],['RUN 1','lsc-run1'],['RUN 2','lsc-run2'],[$latestLabel,'lsc-latest'],['BEST','']] as $i => [$h, $cls])
            <span class="mono {{ $cls }}"
                style="font-size:{{ $compact ? 10 : 16 }}px;letter-spacing:0.12em;color:var(--ink-dim);text-align:{{ $i >= 2 ? 'right' : 'left' }};">{{ $h }}</span>
        @endforeach
    </div>
    @foreach($rows as $i => $row)
        @php $isOnCourse = $displayPhase === 'RUNNING' && $event?->live_rider_id && ($row['rider']->id === $event->live_rider_id); @endphp
        @php
            $rowTotalVal = (float) ($row['total'] ?? $row['best'] ?? 0);
            $rank        = $rowTotalVal > 0 ? 1 + $totals->filter(fn ($v) => $v > $rowTotalVal)->count() : $i + 1;   // seri berbagi nomor peringkat
            $isWinner    = $winnerRow && $rank === 1 && $rowTotalVal > 0;
        @endphp
        <div class="score-row {{ $isWinner ? 'lsc-win' : '' }}" style="
            border-bottom:{{ !$loop->last ? '1px solid var(--line)' : 'none' }};
            background:{{ $isOnCourse ? 'color-mix(in srgb,var(--red) 8%,transparent)' : 'transparent' }};
            border-left:{{ $isOnCourse ? '3px solid var(--red)' : '3px solid transparent' }};
        ">
            <span class="display tnum" style="font-size:{{ $numSize }};color:{{ $rank === 1 ? 'var(--lime)' : ($rank <= 3 ? 'var(--ink)' : 'var(--ink-faint)') }};">{{ str_pad($rank,2,'0',STR_PAD_LEFT) }}</span>
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

@endif

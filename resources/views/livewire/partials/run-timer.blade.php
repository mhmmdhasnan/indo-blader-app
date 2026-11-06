{{-- Timer run. Expects $started (unix ts, 0 = belum jalan), $duration (detik), $running (bool) --}}
@if($duration > 0)
<div wire:key="oc-timer-{{ $started }}-{{ $duration }}"
     x-data="{ started: {{ $started }}, dur: {{ $duration }}, now: Math.floor(Date.now() / 1000),
               get left() { return this.started ? Math.max(0, this.dur - (this.now - this.started)) : this.dur; },
               get txt() { const s = this.left; return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); } }"
     x-init="if (started) { setInterval(() => now = Math.floor(Date.now() / 1000), 500) }">
    <span class="display tnum oc-timer" style="color:{{ $running ? 'var(--lime)' : 'inherit' }};" x-text="txt">{{ sprintf('%02d:%02d', intdiv($duration, 60), $duration % 60) }}</span>
</div>
@endif

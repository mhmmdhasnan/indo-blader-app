{{-- Input nilai: slider + isi manual (angka). Nama file lama "keypad" dipertahankan supaya semua pemanggil tetap jalan.
     Expects $model (mis. 'criteriaScores.all', 'jamScores.12.all', 'bestTrickScore'), $value (float|null), $max (100 atau 10),
     opsional $label. Nilai langsung tersimpan ke properti Livewire $model (debounce); kosong = belum diisi. --}}
@once
<style>
.kp{display:flex;flex-direction:column;gap:12px}
.kp-hd{display:flex;align-items:center;justify-content:space-between;gap:8px}
.kp-lbl{font:700 12px/16px var(--font-mono,monospace);letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3,#a3a39b)}
.kp-tag{font:700 12px var(--font-mono,monospace);letter-spacing:.08em;padding:2px 8px;border:2px solid}
.kp-tag.saved{color:var(--lime);border-color:var(--lime)}.kp-tag.empty{color:var(--ink-3,#a3a39b);border-color:var(--ink-3,#8a8a90)}
.kp-row{display:flex;align-items:center;gap:14px}
.kp-range{flex:1;min-width:0;height:36px;accent-color:var(--lime);cursor:pointer}
.kp-num{width:96px;min-height:48px;text-align:center;font:700 22px var(--font-mono,monospace);background:var(--bg-2);color:inherit;border:2px solid var(--ink-3,#8a8a90)}
.kp-num:focus-visible,.kp-range:focus-visible{outline:3px solid var(--lime);outline-offset:2px}
</style>
@endonce
@php
    $kpHas = $value !== null && $value !== '' && is_numeric($value);
    $kpMax = rtrim(rtrim(number_format((float) $max, 1, '.', ''), '0'), '.');
@endphp
<div class="kp" wire:key="kp-{{ $model }}">
    <div class="kp-hd">
        <span class="kp-lbl">{{ $label ?? '' }}</span>
        <span class="kp-tag {{ $kpHas ? 'saved' : 'empty' }}">{{ $kpHas ? 'TERISI' : 'BELUM DIISI' }}</span>
    </div>
    <div class="kp-row">
        <input type="range" class="kp-range" min="0" max="{{ $kpMax }}" step="0.1"
            wire:model.live.debounce.300ms="{{ $model }}" aria-label="Geser nilai {{ $label ?? '' }}">
        <input type="number" inputmode="decimal" class="kp-num" min="0" max="{{ $kpMax }}" step="0.1" placeholder="0–{{ $kpMax }}"
            wire:model.live.debounce.500ms="{{ $model }}" aria-label="Isi nilai {{ $label ?? '' }} manual">
    </div>
</div>

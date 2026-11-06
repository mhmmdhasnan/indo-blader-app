{{-- Keypad angka (design system FrameBladeScore / ScoreInput pola B).
     Ketikan disimpan di browser sebagai DRAFT; tombol OK mengirim ke properti Livewire $model.
     Expects $model (mis. 'criteriaScores.all', 'jamScores.12.all', 'bestTrickScore'), $value (float|null), $max (100 atau 10),
     opsional $label. --}}
@once
<style>
.kp{display:flex;flex-direction:column;gap:12px}
.kp-hd{display:flex;align-items:center;justify-content:space-between;gap:8px}
.kp-lbl{font:700 12px/16px var(--font-mono,monospace);letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3,#a3a39b)}
.kp-tag{font:700 12px var(--font-mono,monospace);letter-spacing:.08em;padding:2px 8px;border:2px solid}
.kp-tag.draft{color:#ffb020;border-color:#ffb020}.kp-tag.saved{color:var(--lime);border-color:var(--lime)}.kp-tag.empty{color:var(--ink-3,#a3a39b);border-color:var(--ink-3,#8a8a90)}
.kp-row{display:flex;align-items:flex-end;gap:12px}
.kp-val{flex:1;font-size:72px;line-height:64px;letter-spacing:.02em}
.kp-val small{font-size:24px;color:var(--ink-3,#a3a39b);margin-left:6px}
.kp-val.empty{color:var(--ink-3,#a3a39b)}
.kp-back{min-width:48px;min-height:48px;background:transparent;border:2px solid var(--ink-3,#8a8a90);color:inherit;font:700 18px var(--font-mono,monospace);cursor:pointer}
.kp-pad{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.kp-key{min-height:48px;background:var(--bg-2);border:2px solid var(--ink-3,#8a8a90);color:inherit;font:700 18px var(--font-mono,monospace);cursor:pointer}
.kp-key:active{background:var(--ink);color:var(--bg)}
.kp-key.ok{background:var(--lime);border-color:var(--lime);color:#0a0a0a}
.kp-key:disabled{opacity:.45;cursor:not-allowed}
.kp button:focus-visible{outline:3px solid var(--lime);outline-offset:2px}
</style>
@endonce
@php
    $kpInit = $value !== null && $value !== '' && is_numeric($value)
        ? rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.') : '';
@endphp
<div class="kp" wire:key="kp-{{ $model }}-{{ $kpInit === '' ? 'x' : $kpInit }}"
     x-data="{
        buf: '{{ $kpInit }}', saved: '{{ $kpInit }}', max: {{ (float) $max }}, model: '{{ $model }}',
        press(k) {
            let c = this.buf;
            if (k === '.') { if (c.includes('.')) return; c = c === '' ? '0.' : c + '.'; }
            else { c = (c === '0' ? '' : c) + k; }
            if (!/^\d{1,3}(\.\d?)?$/.test(c) && c !== '0.') return;
            if (parseFloat(c) > this.max) return;
            this.buf = c;
        },
        back() { this.buf = this.buf.slice(0, -1); },
        ok() { const v = parseFloat(this.buf); if (isNaN(v) || v > this.max) return; this.saved = String(v); this.buf = String(v); this.$wire.set(this.model, v, true); },
        get dirty() { return this.buf !== this.saved; }
     }">
    <div class="kp-hd">
        <span class="kp-lbl">{{ $label ?? '' }}</span>
        <span class="kp-tag" :class="buf === '' ? 'empty' : (dirty ? 'draft' : 'saved')" x-text="buf === '' ? 'BELUM DIISI' : (dirty ? 'DRAFT' : 'TERSIMPAN')">BELUM DIISI</span>
    </div>
    <div class="kp-row">
        <div class="display tnum kp-val" :class="buf === '' ? 'empty' : ''"><span x-text="buf === '' ? '—' : buf"></span><small>/{{ rtrim(rtrim(number_format((float) $max, 1, '.', ''), '0'), '.') }}</small></div>
        <button type="button" class="kp-back" @click="back()" aria-label="Hapus angka terakhir">⌫</button>
    </div>
    <div class="kp-pad" role="group" aria-label="Keypad angka">
        @foreach(['1','2','3','4','5','6','7','8','9','.','0'] as $k)
            <button type="button" class="kp-key" @click="press('{{ $k }}')">{{ $k }}</button>
        @endforeach
        <button type="button" class="kp-key ok" @click="ok()" :disabled="buf === '' || !dirty">OK</button>
    </div>
</div>

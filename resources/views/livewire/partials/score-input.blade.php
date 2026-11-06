{{-- Input nilai per rider: satu keypad per kriteria. Kalau kriteria > 1, ada tab untuk memilih kriteria yang sedang diisi.
     Expects $criteria (collection: key, name), $modelPrefix ('criteriaScores' | 'jamScores.<regId>'), $scores (array key => nilai|null), $max (default 100) --}}
@php $siMax = $max ?? 100; $siFirst = $criteria->first()?->key; @endphp
<div x-data="{ sel: '{{ $siFirst }}' }" style="display:flex;flex-direction:column;gap:12px;">
    @if($criteria->count() > 1)
        <div style="display:flex;flex-wrap:wrap;gap:6px;" role="tablist" aria-label="Kriteria">
            @foreach($criteria as $crit)
                @php $cv = $scores[$crit->key] ?? null; @endphp
                <button type="button" role="tab" @click="sel = '{{ $crit->key }}'" :aria-selected="sel === '{{ $crit->key }}'"
                    :style="sel === '{{ $crit->key }}' ? 'background:var(--ink);color:var(--bg);border-color:var(--ink)' : ''"
                    style="flex:1;min-width:84px;min-height:48px;background:transparent;border:2px solid var(--ink-3,#8a8a90);color:inherit;font:700 12px var(--font-mono,monospace);letter-spacing:.06em;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;">
                    <span>{{ strtoupper($crit->name) }}</span>
                    <span style="font-size:14px;{{ is_numeric($cv) ? 'color:var(--lime);' : 'opacity:.6;' }}">{{ is_numeric($cv) ? number_format((float) $cv, 1) : '—' }}</span>
                </button>
            @endforeach
        </div>
    @endif
    @foreach($criteria as $crit)
        <div x-show="sel === '{{ $crit->key }}'" @if(!$loop->first) x-cloak style="display:none;" @endif>
            @include('livewire.partials.score-keypad', [
                'model' => $modelPrefix . '.' . $crit->key,
                'value' => $scores[$crit->key] ?? null,
                'max'   => $siMax,
                'label' => $criteria->count() > 1 ? $crit->name : '',
            ])
        </div>
    @endforeach
</div>

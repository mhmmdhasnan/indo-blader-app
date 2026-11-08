{{-- ── EVENT LIST (acuan: design system — AdminEvents) ── --}}
@include('livewire.admin.partials.ad-styles')
@php
    $evChip = ['OPEN' => ['ok', 'REG OPEN'], 'CLOSING' => ['no', 'CLOSING SOON'], 'SOON' => ['', 'COMING SOON'], 'FULL' => ['', 'FULL'], 'LIVE' => ['no', 'LIVE NOW']];
@endphp
<div class="col ad-body" style="gap:16px;">
@foreach($events as $ev)
    @php
        $isOpen  = $divManageEventId === $ev->id;
        $chip    = $evChip[$ev->status] ?? $evChip['SOON'];
        $isLive  = $ev->type === 'LIVE_SCORE';
    @endphp
    <section wire:key="ev-card-{{ $ev->id }}" class="ad-card" style="{{ $ev->featured ? 'border-color:var(--lime);' : '' }}">
        {{-- Kartu event --}}
        <div style="padding:16px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:260px;">
                <div style="display:flex;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <span class="ad-chip {{ $chip[0] }}">{{ $chip[1] }}</span>
                    <span class="ad-chip">{{ str_replace('_', ' ', $ev->type) }}</span>
                    @if($ev->edition)<span class="ad-chip">{{ strtoupper($ev->edition) }}</span>@endif
                    @if($ev->featured)<span class="ad-chip ok">FEATURED</span>@endif
                </div>
                <div class="display" style="font-size:40px;line-height:40px;letter-spacing:.02em;">{{ strtoupper($ev->title) }}</div>
                <div class="mono" style="font-size:13px;color:var(--ink-3,#a3a39b);margin-top:4px;">
                    {{ $ev->date_label }} · {{ $ev->venue }}, {{ $ev->city }} · {{ $ev->prize_formatted }} prize · {{ $ev->filled }}{{ $ev->slots !== null ? ' / ' . $ev->slots : '' }} terdaftar
                </div>
            </div>
            <a href="{{ route('events.show', $ev->slug) }}" class="ad-btn" style="display:inline-flex;align-items:center;text-decoration:none;color:inherit;">LIHAT SITUS →</a>
            <button type="button" wire:click="openEditEvent({{ $ev->id }})" class="ad-btn">EDIT EVENT</button>
            <button type="button" wire:click="manageDivisions({{ $ev->id }})" class="ad-btn {{ $isOpen ? 'pri' : '' }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">DIVISI ({{ $ev->divisions->count() }})</button>
            <x-ad-menu label="Aksi event {{ $ev->title }}">
                <span class="ad-ml">Zona berbahaya</span>
                <button type="button" class="ad-mi d" wire:click="deleteEvent({{ $ev->id }})"
                    wire:confirm="Yakin hapus event '{{ $ev->title }}'? Semua registrasi terkait akan terpengaruh.">HAPUS EVENT…</button>
            </x-ad-menu>
        </div>

        {{-- Divisi --}}
        @if($isOpen)
            @php
                $dvSearch = trim(mb_strtolower($divSearch));
                $dvAll    = $eventDivisions;
                $dvRows   = $dvAll
                    ->when($divPhaseFilter === 'OPEN', fn ($c) => $c->filter(fn ($d) => !$d->live_final_completed_at))
                    ->when($divPhaseFilter === 'DONE', fn ($c) => $c->filter(fn ($d) => (bool) $d->live_final_completed_at))
                    ->when($dvSearch !== '', fn ($c) => $c->filter(fn ($d) => str_contains(mb_strtolower($d->name), $dvSearch)));
            @endphp
            <div style="border-top:2px solid var(--lime);padding:16px;display:flex;flex-direction:column;gap:12px;">
                <div class="ad-tool">
                    <div class="ad-lbl" style="color:var(--ink);">{{ $dvAll->count() }} DIVISI</div>
                    <input type="search" wire:model.live.debounce.300ms="divSearch" class="ad-inp" placeholder="Cari divisi…" aria-label="Cari divisi">
                    @if($isLive)
                    <div class="ad-tabs" role="tablist" aria-label="Filter divisi">
                        @foreach(['ALL' => 'SEMUA', 'OPEN' => 'BELUM SELESAI', 'DONE' => 'RANKING SELESAI'] as $k => $lb)
                            <button type="button" wire:click="$set('divPhaseFilter','{{ $k }}')" class="{{ $divPhaseFilter === $k ? 'on' : '' }}" aria-pressed="{{ $divPhaseFilter === $k ? 'true' : 'false' }}">{{ $lb }}</button>
                        @endforeach
                    </div>
                    @endif
                    <div class="ad-sp"></div>
                    <button type="button" wire:click="openCreateDivision({{ $ev->id }})" class="ad-btn pri">+ TAMBAH DIVISI</button>
                </div>

                                    {{-- Create/Edit form --}}
                                    @if($divEditing && $divManageEventId === $ev->id)
                                        <div style="padding:14px 18px;border-bottom:1px solid var(--line);background:var(--bg);">
                                            <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                                                @if($ev->type === 'LIVE_SCORE')
                                                <div class="col" style="gap:4px;min-width:120px;">
                                                    <span class="mono dim" style="font-size:9px;">DISCIPLINE *</span>
                                                    <select wire:model.live="divDiscipline" class="input-field" style="font-size:12px;">
                                                        <option value="">— pilih —</option>
                                                        @foreach($ev->categories ?? ['STREET','PARK','VERT','FLAT','MINIRAMP'] as $d)
                                                            <option value="{{ $d }}">{{ $d }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('divDiscipline') <span style="color:var(--red);font-size:10px;">{{ $message }}</span> @enderror
                                                </div>
                                                @endif
                                                <div class="col" style="gap:4px;min-width:120px;">
                                                    <span class="mono dim" style="font-size:9px;">LEVEL *</span>
                                                    <select wire:model.live="divLevel" class="input-field" style="font-size:12px;">
                                                        <option value="">— pilih —</option>
                                                        @foreach($competitionLevels->where('is_active', true) as $lvl)
                                                            <option value="{{ $lvl->name }}">{{ $lvl->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('divLevel') <span style="color:var(--red);font-size:10px;">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col" style="gap:6px;">
                                                    <span class="mono dim" style="font-size:9px;">SLOTS</span>
                                                    <label class="flex label" style="gap:6px;align-items:center;font-size:11px;cursor:pointer;">
                                                        <input type="checkbox" wire:model.live="divUnlimited" style="accent-color:var(--lime);">
                                                        Tidak terbatas
                                                    </label>
                                                    @if(!$divUnlimited)
                                                        <input type="number" wire:model.live="divSlots" min="1" class="input-field" style="width:80px;" placeholder="32" />
                                                    @endif
                                                </div>
                                                <div class="col" style="gap:4px;min-width:140px;">
                                                    <span class="mono dim" style="font-size:9px;">FORMAT</span>
                                                    <select wire:model.live="divFormat" class="input-field" style="font-size:12px;">
                                                        <option value="STANDARD">Standar (rider per rider)</option>
                                                        <option value="JAMMING">Jamming Season (1 heat serentak)</option>
                                                    </select>
                                                </div>
                                                @if($divFormat === 'JAMMING')
                                                <div class="col" style="gap:4px;">
                                                    <span class="mono dim" style="font-size:9px;">DURASI JAM (DETIK)</span>
                                                    <input type="number" wire:model.live="divJamDuration" min="10" max="3600" class="input-field" style="width:100px;font-size:12px;" />
                                                    @error('divJamDuration') <span style="color:var(--red);font-size:10px;">{{ $message }}</span> @enderror
                                                </div>
                                                @endif
                                                {{-- Preview nama --}}
                                                @if($divLevel)
                                                <div class="col" style="gap:2px;align-self:flex-end;padding-bottom:6px;">
                                                    <span class="mono dim" style="font-size:9px;">PREVIEW NAMA</span>
                                                    <span class="mono" style="font-size:12px;font-weight:700;">
                                                        {{ $ev->type === 'LIVE_SCORE' && $divDiscipline ? ucfirst(strtolower($divDiscipline)) . ' ' . $divLevel : $divLevel }}
                                                    </span>
                                                </div>
                                                @endif
                                                <div class="flex gap-s">
                                                    <button wire:click="saveDivision" class="btn btn-sm btn-lime" @if(!$divLevel || ($ev->type === 'LIVE_SCORE' && !$divDiscipline)) disabled @endif>
                                                        {{ $divId ? 'Update' : 'Simpan' }}
                                                    </button>
                                                    <button wire:click="cancelDivision" class="btn btn-sm btn-ghost">Batal</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                <div class="ad-card" style="overflow:hidden;">
                @if($dvRows->isEmpty())
                    <div style="padding:32px;text-align:center;"><span class="ad-note">{{ $dvAll->isEmpty() ? 'Belum ada divisi. Klik "+ TAMBAH DIVISI".' : 'Tidak ada divisi yang cocok dengan filter.' }}</span></div>
                @else
                <div class="ad-scroll">
                <table class="ad-tbl" style="min-width:{{ $isLive ? 860 : 520 }}px;">
                    <thead><tr><th>Divisi</th><th>Slot</th>@if($isLive)<th>Fase</th><th>Ranking</th>@endif<th></th><th></th></tr></thead>
                    <tbody>
                    @foreach($dvRows as $div)
                        @php
                            $isFinal  = $div->live_stage === 'FINAL';
                            $isDone   = (bool) $div->live_final_completed_at;
                            $nameLow  = mb_strtolower($div->name);
                            $tags     = collect([$div->discipline, $div->level])->filter(fn ($t) => $t && !str_contains($nameLow, mb_strtolower($t)));
                        @endphp
                        <tr wire:key="dv-row-{{ $div->id }}">
                            <td>
                                <b style="font:700 16px var(--font-sans,sans-serif);">{{ $div->name }}</b>
                                @if($div->format === 'JAMMING')<span class="ad-chip ok" style="margin-left:6px;">JAMMING · {{ $div->jam_duration }}s</span>@endif
                                @foreach($tags as $t)<span class="ad-chip" style="margin-left:6px;">{{ $t }}</span>@endforeach
                                @if(!$div->is_active)<span class="ad-chip" style="margin-left:6px;">NONAKTIF</span>@endif
                                @error('divDelete_' . $div->id)<div class="ad-err">{{ $message }}</div>@enderror
                            </td>
                            <td class="mono" style="font-size:13px;white-space:nowrap;">{{ $div->filled }} / {{ $div->slots ?? '∞' }}</td>
                            @if($isLive)
                            <td style="white-space:nowrap;">
                                <span class="ad-chip {{ $isFinal ? 'ok' : 'wt' }}">{{ $isFinal ? '✓ KUALIFIKASI' : 'KUALIFIKASI' }}</span>
                                <span class="ad-chip {{ $isDone ? 'ok' : ($isFinal ? 'wt' : '') }}">{{ $isDone ? '✓ FINAL' : 'FINAL' }}</span>
                                @if($div->best_trick_active)<span class="ad-chip ok">BEST TRICK AKTIF</span>@endif
                            </td>
                            <td>@if($isDone)<span class="ad-chip ok">RANKING SELESAI</span>@else<span class="ad-lbl" style="text-transform:none;">—</span>@endif</td>
                            @endif
                            <td style="text-align:right;white-space:nowrap;">
                                @if($isLive)
                                    @if($isDone)
                                        <a href="{{ route('events.show', $ev->slug) }}" class="ad-btn sm" style="display:inline-flex;align-items:center;text-decoration:none;color:inherit;">LIHAT HASIL</a>
                                    @elseif($isFinal)
                                        <button type="button" wire:click="completeLiveFinal({{ $div->id }})" class="ad-btn pri sm"
                                            wire:confirm="{{ $div->best_trick_active ? 'Fase Best Trick masih AKTIF. Yakin mau selesaikan final & hitung ranking sekarang?' : 'Selesaikan final dan hitung ranking? Poin akan ditambahkan ke rider.' }}">SELESAIKAN &amp; HITUNG</button>
                                    @else
                                        <button type="button" wire:click="openFinalistPicker({{ $div->id }})" class="ad-btn pri sm">PILIH FINALIS</button>
                                    @endif
                                @endif
                            </td>
                            <td style="width:72px;text-align:right;">
                                <x-ad-menu label="Aksi {{ $div->name }}">
                                    <span class="ad-ml">Divisi</span>
                                    <button type="button" class="ad-mi" wire:click="openEditDivision({{ $div->id }})">EDIT DIVISI</button>
                                    @if($isLive)
                                        @if(!$isFinal)
                                            <button type="button" class="ad-mi" wire:click="openGroupManager({{ $div->id }})">KELOLA GROUP</button>
                                            <button type="button" class="ad-mi" wire:click="openFinalistPicker({{ $div->id }})">PILIH FINALIS</button>
                                        @endif
                                        @if($div->best_trick_active)
                                            <button type="button" class="ad-mi" wire:click="endBestTrickPhase({{ $div->id }})">SELESAI BEST TRICK</button>
                                        @elseif($isFinal && !$isDone)
                                            <button type="button" class="ad-mi" wire:click="startBestTrickPhase({{ $div->id }})">MULAI BEST TRICK</button>
                                        @endif
                                        @if($isFinal && !$isDone)
                                            <button type="button" class="ad-mi" wire:click="reopenQualification({{ $div->id }})" wire:confirm="Kembali ke fase kualifikasi?">KEMBALI KE KUALIFIKASI</button>
                                        @endif
                                        <span class="ad-ml">Zona berbahaya</span>
                                        @if($isDone)
                                            <button type="button" class="ad-mi d" wire:click="reopenCompletedFinal({{ $div->id }})"
                                                wire:confirm="Divisi ini sudah SELESAI — poin/wins/podium yang sudah ditambahkan ke rider akan DIBATALKAN, lalu divisi kembali ke fase kualifikasi. Yakin?">BUKA ULANG (BATALKAN POIN)…</button>
                                        @endif
                                        <button type="button" class="ad-mi d" wire:click="resetDivisionScores({{ $div->id }})"
                                            wire:confirm="Reset SEMUA nilai rider di divisi '{{ $div->name }}'? Skor kualifikasi & final akan dihapus, finalis & pengumuman dibatalkan, divisi balik ke fase kualifikasi. Kalau final-nya sudah selesai, poin/wins/podium yang sudah dikasih ke rider juga ikut dibatalkan. Tindakan ini tidak bisa dibatalkan.">RESET NILAI…</button>
                                    @else
                                        <span class="ad-ml">Zona berbahaya</span>
                                    @endif
                                    <button type="button" class="ad-mi d" wire:click="deleteDivision({{ $div->id }})" wire:confirm="Hapus divisi '{{ $div->name }}'?">HAPUS DIVISI…</button>
                                </x-ad-menu>
                            </td>
                        </tr>
                        @if($groupManageDivisionId === $div->id || $finalistPickerDivisionId === $div->id)
                        <tr wire:key="dv-sub-{{ $div->id }}"><td colspan="{{ $isLive ? 6 : 4 }}" style="padding:0;">
                                            {{-- Group manager --}}
                                            @if($groupManageDivisionId === $div->id)
                                                <div style="padding:14px 18px 16px;background:var(--bg-2);border-bottom:1px solid var(--line);">
                                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.1em;display:block;margin-bottom:10px;">
                                                        KELOLA GROUP KUALIFIKASI — {{ strtoupper($div->name) }}
                                                    </span>

                                                    <div class="flex gap-s" style="margin-bottom:10px;align-items:flex-end;flex-wrap:wrap;">
                                                        <div class="col" style="gap:4px;">
                                                            <span class="mono dim" style="font-size:9px;">NAMA GROUP BARU</span>
                                                            <input wire:model="newGroupName" wire:keydown.enter="createGroup" type="text" class="input-field" style="font-size:12px;" placeholder="Heat A">
                                                        </div>
                                                        <button wire:click="createGroup" class="btn btn-sm btn-lime">+ Tambah Group</button>
                                                        @error('newGroupName') <span style="color:var(--red);font-size:10px;">{{ $message }}</span> @enderror
                                                    </div>

                                                    @if($groupManageGroups->isNotEmpty())
                                                        <div class="flex gap-s" style="margin-bottom:14px;flex-wrap:wrap;align-items:center;">
                                                            @foreach($groupManageGroups as $g)
                                                                <span class="badge badge-out" style="font-size:10px;">
                                                                    {{ $g->name }} ({{ $g->registrations_count }})
                                                                    <button wire:click="deleteGroup({{ $g->id }})" wire:confirm="Hapus group '{{ $g->name }}'? Rider di dalamnya jadi belum ter-group."
                                                                        style="background:none;border:none;color:var(--red);cursor:pointer;margin-left:4px;">✕</button>
                                                                </span>
                                                            @endforeach
                                                            <button wire:click="randomizeGroups({{ $div->id }})" class="btn btn-sm btn-ghost" style="font-size:11px;">🎲 Acak ke Group</button>
                                                        </div>
                                                        @error('groupRandomize') <p style="color:var(--red);font-size:11px;margin-bottom:10px;">{{ $message }}</p> @enderror

                                                        <div class="col" style="gap:4px;margin-bottom:14px;max-height:260px;overflow-y:auto;">
                                                            @foreach($groupManageRegistrations as $reg)
                                                                <div class="between" style="padding:6px 10px;background:var(--bg);border-radius:3px;">
                                                                    <span class="label" style="font-size:12px;">{{ $reg->name }}</span>
                                                                    <select wire:change="assignToGroup({{ $reg->id }}, $event.target.value)" class="input-field" style="font-size:11px;width:auto;">
                                                                        <option value="" @selected(!$reg->division_group_id)>— belum ada group —</option>
                                                                        @foreach($groupManageGroups as $g)
                                                                            <option value="{{ $g->id }}" @selected($reg->division_group_id === $g->id)>{{ $g->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="mono dim" style="font-size:12px;margin-bottom:14px;">Belum ada group. Tambah group dulu, baru bisa acak/assign rider.</p>
                                                    @endif

                                                    <button wire:click="closeGroupManager" class="btn btn-sm btn-ghost">Tutup</button>
                                                </div>
                                            @endif

                                            {{-- Finalist picker --}}
                                            @if($finalistPickerDivisionId === $div->id)
                                                <div style="padding:14px 18px 16px;background:var(--bg-2);border-bottom:1px solid var(--line);">
                                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.1em;display:block;margin-bottom:10px;">
                                                        PILIH FINALIS — {{ strtoupper($div->name) }} (DARI LEADERBOARD KUALIFIKASI)
                                                    </span>

                                                    {{-- Ranking gabungan semua group — finalis dipilih dari skor tertinggi lintas group --}}
                                                    <div style="margin-bottom:18px;padding:12px;background:var(--bg);border:2px solid var(--lime);border-radius:4px;">
                                                        <div class="between" style="margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                                                            <span class="mono" style="font-size:10px;color:var(--lime);">🎯 RANKING GABUNGAN (SEMUA GROUP)</span>
                                                            <div class="flex gap-s" style="align-items:center;">
                                                                <span class="mono dim" style="font-size:10px;">TOP</span>
                                                                <input type="number" min="1" wire:model="finalistTopN" class="input-field" style="width:60px;font-size:12px;padding:4px 8px;">
                                                                <button wire:click="autoSelectTopFinalists({{ $div->id }})" class="btn btn-sm btn-lime">Auto-pilih Top N</button>
                                                            </div>
                                                        </div>
                                                        @if(($finalistCombined ?? collect())->isEmpty())
                                                            <p class="mono dim" style="font-size:11px;">Belum ada skor kualifikasi.</p>
                                                        @else
                                                            <div class="col" style="gap:6px;max-height:220px;overflow-y:auto;">
                                                                @foreach($finalistCombined as $i => $row)
                                                                    <label class="flex gap-s" style="align-items:center;padding:6px 10px;background:var(--bg-2);border-radius:3px;cursor:pointer;">
                                                                        <input type="checkbox" wire:model="selectedFinalistRegIds" value="{{ $row['registration']->id }}" style="accent-color:var(--lime);">
                                                                        <span class="mono dim" style="font-size:11px;width:24px;">#{{ $i + 1 }}</span>
                                                                        <span class="label" style="font-size:13px;flex:1;">{{ $row['rider']->name }}</span>
                                                                        <span class="mono dim" style="font-size:10px;">{{ $row['registration']->division_group_id ? optional(\App\Models\DivisionGroup::find($row['registration']->division_group_id))->name : '—' }}</span>
                                                                        <span class="mono tnum" style="font-size:12px;color:var(--lime);">{{ number_format($row['total'], 1) }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <span class="mono dim" style="font-size:10px;letter-spacing:0.1em;display:block;margin-bottom:10px;">BREAKDOWN PER GROUP (REFERENSI)</span>
                                                    @forelse($finalistSections as $section)
                                                        <div style="margin-bottom:16px;">
                                                            <span class="mono" style="font-size:10px;color:var(--lime);display:block;margin-bottom:6px;">
                                                                {{ $section['group']?->name ?? 'BELUM ADA GROUP' }}
                                                            </span>
                                                            @if($section['leaderboard']->isEmpty())
                                                                <p class="mono dim" style="font-size:11px;">Belum ada skor kualifikasi.</p>
                                                            @else
                                                                <div class="col" style="gap:6px;max-height:220px;overflow-y:auto;">
                                                                    @foreach($section['leaderboard'] as $i => $row)
                                                                        <label class="flex gap-s" style="align-items:center;padding:6px 10px;background:var(--bg);border-radius:3px;cursor:pointer;">
                                                                            <input type="checkbox" wire:model="selectedFinalistRegIds" value="{{ $row['registration']->id }}" style="accent-color:var(--lime);">
                                                                            <span class="mono dim" style="font-size:11px;width:24px;">#{{ $i + 1 }}</span>
                                                                            <span class="label" style="font-size:13px;flex:1;">{{ $row['rider']->name }}</span>
                                                                            <span class="mono tnum" style="font-size:12px;color:var(--lime);">{{ number_format($row['total'], 1) }}</span>
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <p class="mono dim" style="font-size:12px;">Belum ada skor kualifikasi untuk divisi ini.</p>
                                                    @endforelse
                                                    <div class="flex gap-s">
                                                        <label class="flex gap-s" style="align-items:center;" title="1 = satu kolam gabungan. Lebih dari 1 = finalis dibagi rata ke heat (pola snake menurut peringkat kualifikasi). Peringkat akhir tetap gabungan semua heat.">
                                                            <span class="mono dim" style="font-size:10px;">HEAT FINAL</span>
                                                            <input type="number" min="1" max="8" wire:model="finalistHeatCount" class="input-field" style="width:60px;font-size:12px;padding:4px 8px;">
                                                        </label>
                                                        <button wire:click="openFinalPhase({{ $div->id }})" class="btn btn-sm btn-lime">Simpan &amp; Buka Final</button>
                                                        <button wire:click="cancelFinalistPicker" class="btn btn-sm btn-ghost">Batal</button>
                                                    </div>
                                                    @error('finalist') <p style="color:var(--red);font-size:11px;margin-top:8px;">{{ $message }}</p> @enderror
                                                </div>
                                            @endif
                        </td></tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
                </div>
                @endif
                </div>
            </div>
        @endif
    </section>
@endforeach
</div>

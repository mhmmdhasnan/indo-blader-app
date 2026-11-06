{{-- ── REGISTRATIONS (acuan: design system — AdminRegistrations) ── --}}
@include('livewire.admin.partials.ad-styles')
@php
    $rgAll      = $registrations;
    $rgCounts   = ['ALL' => $rgAll->count(), 'PENDING' => $rgAll->where('status','PENDING')->count(), 'APPROVED' => $rgAll->where('status','APPROVED')->count(), 'REJECTED' => $rgAll->where('status','REJECTED')->count()];
    $rgSearch   = trim(mb_strtolower($regSearch));
    $rgFiltered = $rgAll
        ->when($regStatusFilter !== 'ALL', fn ($c) => $c->where('status', $regStatusFilter))
        ->when($regDivisionFilter, fn ($c) => $c->where('division_id', $regDivisionFilter))
        ->when($rgSearch !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r->name . ' ' . $r->city), $rgSearch)));
    $rgGroups   = $rgFiltered->groupBy(fn ($r) => $r->division?->name ?? 'Tanpa Divisi')->sortKeys();
    $rgDivs     = $activeEvent?->divisions ?? collect();
    $rgSel      = array_map('intval', $selectedRegIds);
@endphp
<div class="col ad-body" style="gap:12px;">

    {{-- Toolbar: tab status · import · approve semua · menu halaman --}}
    <div class="ad-tool">
        <div class="ad-tabs" role="tablist" aria-label="Filter status">
            @foreach(['ALL' => 'SEMUA', 'PENDING' => 'PENDING', 'APPROVED' => 'APPROVED', 'REJECTED' => 'REJECTED'] as $k => $lb)
                <button type="button" wire:click="$set('regStatusFilter','{{ $k }}')" class="{{ $regStatusFilter === $k ? 'on' : '' }}" aria-pressed="{{ $regStatusFilter === $k ? 'true' : 'false' }}">{{ $lb }} <b>{{ $rgCounts[$k] }}</b></button>
            @endforeach
        </div>
        <div class="ad-sp"></div>
        <button type="button" wire:click="openImport" class="ad-btn" @if(!$activeEventId) disabled title="Pilih event dulu" @endif>IMPORT DARI EXCEL</button>
        <button type="button" wire:click="approveAllRegistrations"
            wire:confirm="Approve semua registrasi PENDING yang payment-nya sudah VERIFIED?"
            class="ad-btn pri {{ ($rgCounts['PENDING'] === 0 || !$activeEventId) ? 'dis' : '' }}" @if($rgCounts['PENDING'] === 0 || !$activeEventId) disabled title="{{ !$activeEventId ? 'Pilih event dulu' : 'Tidak ada yang pending' }}" @endif>APPROVE SEMUA PENDING</button>
        <x-ad-menu label="Aksi halaman">
            <span class="ad-ml">Zona berbahaya</span>
            <button type="button" class="ad-mi d" wire:click="deleteAllRegistrations"
                wire:confirm="HAPUS SEMUA {{ $rgCounts['ALL'] }} registrasi di event ini? Tidak bisa dibatalkan."
                @if(!$activeEventId || $rgAll->isEmpty()) disabled @endif>HAPUS SEMUA PENDAFTARAN…</button>
        </x-ad-menu>
    </div>

    {{-- Cari + filter divisi --}}
    <div class="ad-tool">
        <input type="search" wire:model.live.debounce.300ms="regSearch" class="ad-inp" placeholder="Cari nama atau kota…" aria-label="Cari rider">
        <select wire:model.live="regDivisionFilter" class="ad-inp" style="min-width:300px;" aria-label="Filter divisi">
            <option value="0">Semua divisi ({{ $rgDivs->count() }})</option>
            @foreach($rgDivs as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
        </select>
        @if(!$activeEventId)<span class="ad-lbl">Pilih event aktif di sidebar untuk import / aksi massal</span>@endif
    </div>

    @if(session('bulkRegistration'))<span class="ad-ok">{{ session('bulkRegistration') }}</span>@endif
    @error('bulkRegistration')<span class="ad-err">{{ $message }}</span>@enderror

    {{-- Import dari Excel (logika tidak berubah) --}}
                    @if($importOpen)
                    <div class="panel" style="padding:16px 18px;border:2px solid var(--ink);">
                        <div class="between" style="margin-bottom:12px;">
                            <span class="label" style="font-size:13px;">Import Registrasi — {{ $activeEvent?->title }}</span>
                            <button wire:click="closeImport" class="btn btn-sm btn-ghost" style="font-size:11px;">✕ Tutup</button>
                        </div>

                        @if($importResult)
                            <div class="col" style="gap:8px;">
                                <span style="color:var(--lime);font-size:13px;">✓ {{ $importResult['created'] }} registrasi dibuat (status PENDING) · {{ $importResult['divisions_created'] }} divisi baru · {{ $importResult['heats_created'] }} heat dibuat.</span>
                                @if(!empty($importResult['photos_saved']) || !empty($importResult['photos_failed']))
                                    <span style="color:var(--lime);font-size:13px;">📷 {{ $importResult['photos_saved'] }} foto rider tersimpan.</span>
                                    @if(count($importResult['photos_failed']))
                                        <span class="mono dim" style="font-size:11px;">Foto gagal diunduh (cek akses Drive "Siapa saja yang memiliki link"): {{ implode(', ', $importResult['photos_failed']) }}</span>
                                    @endif
                                @endif
                                @if(count($importResult['skipped']))
                                    <span class="mono dim" style="font-size:11px;">{{ count($importResult['skipped']) }} dilewati:</span>
                                    @foreach($importResult['skipped'] as $sk)
                                        <span class="mono dim" style="font-size:11px;">· {{ $sk['name'] }} — {{ $sk['reason'] }}</span>
                                    @endforeach
                                @endif
                            </div>
                        @elseif(empty($importRows))
                            <div class="col" style="gap:6px;">
                                <span class="dim" style="font-size:12px;">Upload file .xlsx (sheet pertama). Kolom dibaca dari nama header: Nama (wajib), Usia, Domisili, Kategori, Kategori Usia. Opsional: Heat (nama heat), Gender, No Handphone, Bukti Transfer.</span>
                                <input type="file" wire:model="importFile" accept=".xlsx" class="input-field" style="font-size:11px;max-width:320px;">
                                <span wire:loading wire:target="importFile" class="mono dim" style="font-size:11px;">membaca file…</span>
                                @error('importFile') <span style="font-size:11px;color:var(--red);">{{ $message }}</span> @enderror
                            </div>
                        @else
                            @php $unmapped = collect($importRows)->filter(fn($r) => empty($r['division_id']) && !($importAutoDivisions && !empty($r['new_division'])))->count(); @endphp
                            <div class="flex gap-m" style="flex-wrap:wrap;align-items:center;margin-bottom:10px;font-size:12px;">
                                <label class="flex gap-s" style="align-items:center;"><input type="checkbox" wire:model.live="importSplitGender"> Pisah divisi Men / Women</label>
                                <label class="flex gap-s" style="align-items:center;"><input type="checkbox" wire:model.live="importAutoDivisions"> Buat divisi otomatis kalau belum ada</label>
                                <label class="flex gap-s" style="align-items:center;">Heat otomatis, rider per heat:
                                    <input type="number" min="0" max="20" wire:model.live="importHeatSize" class="input-field" style="width:60px;font-size:12px;"> <span class="mono dim" style="font-size:10px;">(0 = tanpa heat)</span>
                                </label>
                            </div>
                            <div class="between" style="margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                                <span class="mono dim" style="font-size:11px;">{{ count($importRows) }} rider ditemukan · <span style="color:{{ $unmapped ? 'var(--red)' : 'var(--lime)' }};">{{ $unmapped }} belum ada divisi (akan dilewati)</span></span>
                                <button wire:click="confirmImport" wire:confirm="Import {{ count($importRows) - $unmapped }} registrasi?" class="btn btn-sm btn-lime">Import {{ count($importRows) - $unmapped }} rider</button>
                            </div>
                            <div style="max-height:420px;overflow:auto;border:1px solid var(--line);">
                                @foreach($importRows as $i => $r)
                                    <div style="display:grid;grid-template-columns:1.4fr 40px 1fr 1.2fr 1.2fr;gap:10px;align-items:center;padding:8px 12px;border-bottom:1px solid var(--line);min-width:700px;">
                                        <span class="label" style="font-size:12px;">{{ $r['name'] }}</span>
                                        <span class="mono dim" style="font-size:11px;">{{ $r['age'] }}</span>
                                        <span class="dim" style="font-size:11px;">{{ $r['city'] }}</span>
                                        <span class="mono dim" style="font-size:10px;">{{ $r['kategori_usia'] ?: '—' }}@if(!empty($r['heat'])) · <b>{{ strtoupper($r['heat']) }}</b>@endif</span>
                                        <select wire:model="importRows.{{ $i }}.division_id" class="input-field" style="font-size:11px;">
                                            <option value="">{{ ($importAutoDivisions && !empty($r['new_division'])) ? '+ buat: ' . $r['new_division'] : '— lewati —' }}</option>
                                            @foreach($activeEvent?->divisions ?? [] as $div)
                                                <option value="{{ $div->id }}">{{ $div->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif
    @forelse($rgGroups as $divName => $divRegs)
        @php $groupIds = $divRegs->pluck('id')->all(); $allSel = count($groupIds) > 0 && count(array_diff($groupIds, $rgSel)) === 0; @endphp
        <section class="ad-card" wire:key="rg-group-{{ \Illuminate\Support\Str::slug($divName) }}">
            <div class="ad-gh">
                <b style="color:{{ $divName === 'Tanpa Divisi' ? '#ff6b5e' : 'var(--lime)' }};">{{ strtoupper($divName) }}</b>
                <span class="ad-lbl">{{ $divRegs->count() }} rider · {{ $divRegs->where('status','APPROVED')->count() }} approved</span>
            </div>
            <div class="ad-scroll">
            <table class="ad-tbl">
                <thead><tr>
                    <th class="c"><input type="checkbox" class="ad-check" @checked($allSel) wire:click="toggleRegSelection({{ json_encode($groupIds) }})" aria-label="Pilih semua di {{ $divName }}"></th>
                    <th>Rider</th><th>Status</th><th class="r" style="text-align:right;">Aksi</th>
                </tr></thead>
                <tbody>
                @foreach($divRegs as $reg)
                    @php $isEditingReg = $regEditId === $reg->id; $hasScore = in_array($reg->id, $scoredRegIds ?? [], true); @endphp
                    <tr wire:key="rg-row-{{ $reg->id }}">
                        <td class="c"><input type="checkbox" class="ad-check" wire:model.live="selectedRegIds" value="{{ $reg->id }}" aria-label="Pilih {{ $reg->name }}"></td>
                        <td>
                            <div class="ad-nm">
                                <x-avatar :initials="collect(preg_split('/\s+/', trim($reg->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$reg->photo_url" :size="44" />
                                <div style="min-width:0;">
                                    <b>{{ $reg->name }}</b>
                                    <small>@if($reg->dob){{ $reg->dob->age }} th · @endif{{ $reg->city }}</small>
                                </div>
                            </div>
                            @error('registration_' . $reg->id)<span class="ad-err">{{ $message }}</span>@enderror
                        </td>
                        <td>
                            @if($reg->status === 'APPROVED')<span class="ad-chip ok">✓ APPROVED</span>
                            @elseif($reg->status === 'REJECTED')<span class="ad-chip no">DITOLAK</span>
                            @else<span class="ad-chip wt">MENUNGGU</span>@endif
                            @if($reg->status === 'PENDING' && $reg->payment_status !== 'VERIFIED')<span class="ad-chip no" title="Verifikasi payment dulu">PAYMENT BELUM OK</span>@endif
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            @if($reg->status === 'PENDING')
                                <button type="button" wire:click="approveRegistration({{ $reg->id }})" class="ad-btn pri sm {{ $reg->payment_status !== 'VERIFIED' ? 'dis' : '' }}"
                                    @if($reg->payment_status !== 'VERIFIED') disabled title="Verifikasi payment dulu" @endif>APPROVE</button>
                            @endif
                            <x-ad-menu label="Aksi {{ $reg->name }}">
                                <span class="ad-ml">Rider</span>
                                <button type="button" class="ad-mi" wire:click="openEditRegistration({{ $reg->id }})">EDIT DATA</button>
                                @if($reg->status === 'PENDING')
                                    <button type="button" class="ad-mi" wire:click="rejectRegistration({{ $reg->id }})" wire:confirm="Tolak registrasi {{ $reg->name }}?">TOLAK PENDAFTARAN</button>
                                @else
                                    <button type="button" class="ad-mi" wire:click="pendingRegistration({{ $reg->id }})">KEMBALIKAN KE PENDING</button>
                                @endif
                                <span class="ad-ml">Pindah divisi</span>
                                <div style="padding:0 12px 10px;" @click.stop>
                                    <select x-data class="ad-inp" style="min-width:0;width:100%;{{ $hasScore ? 'border-color:#ff6b5e;' : '' }}"
                                        @change="if (confirm('Pindahkan {{ addslashes($reg->name) }} ke divisi ' + $el.options[$el.selectedIndex].text + '?{{ $hasScore ? ' ⚠ RIDER INI SUDAH PUNYA NILAI di divisi lama.' : '' }}')) { $wire.moveRegistrationDivision({{ $reg->id }}, parseInt($el.value)); } $el.value = '';">
                                        <option value="">Pilih divisi tujuan…</option>
                                        @foreach(($reg->event?->divisions ?? collect())->where('id', '!=', $reg->division_id) as $div)
                                            <option value="{{ $div->id }}">{{ $div->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <span class="ad-ml">Zona berbahaya</span>
                                <button type="button" class="ad-mi d" wire:click="deleteRegistration({{ $reg->id }})" wire:confirm="Hapus registrasi {{ $reg->name }}? Tidak bisa dibatalkan.">HAPUS RIDER…</button>
                            </x-ad-menu>
                        </td>
                    </tr>
                    @if($isEditingReg)
                    <tr wire:key="rg-edit-{{ $reg->id }}"><td colspan="4" style="padding:0;">
                                {{-- Inline edit form --}}
                                @if($isEditingReg)
                                <div style="padding:14px 18px 16px;background:var(--bg-2);border-top:1px solid var(--line);">
                                    <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;">
                                        <div class="col" style="gap:4px;">
                                            <span class="mono dim" style="font-size:10px;">FOTO RIDER</span>
                                            <div class="flex gap-s" style="align-items:center;">
                                                @if($regEditPhoto)
                                                    <img src="{{ $regEditPhoto->temporaryUrl() }}" style="width:48px;height:48px;object-fit:cover;border-radius:50%;border:2px solid var(--lime);">
                                                @elseif($regEditAvatarUrl)
                                                    <img src="{{ $regEditAvatarUrl }}" style="width:48px;height:48px;object-fit:cover;border-radius:50%;border:2px solid var(--line);">
                                                @else
                                                    <x-avatar :initials="collect(preg_split('/\s+/', trim($reg->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$reg->photo_url" :size="48" />
                                                @endif
                                                <input type="file" wire:model="regEditPhoto" accept="image/*" class="input-field" style="font-size:11px;max-width:220px;">
                                            </div>
                                            <span class="mono dim" style="font-size:9px;">JPG/PNG, maks 2MB. Otomatis buat profil Rider kalau belum ada.</span>
                                            @error('regEditPhoto') <span style="font-size:10px;color:var(--red);">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                                        <div class="col" style="gap:4px;min-width:150px;flex:1;">
                                            <span class="mono dim" style="font-size:10px;">NAMA</span>
                                            <input wire:model="regEditName" class="input-field" style="font-size:12px;" />
                                        </div>
                                        <div class="col" style="gap:4px;min-width:150px;flex:1;">
                                            <span class="mono dim" style="font-size:10px;">EMAIL</span>
                                            <input wire:model="regEditEmail" type="email" class="input-field" style="font-size:12px;" />
                                        </div>
                                        <div class="col" style="gap:4px;min-width:120px;">
                                            <span class="mono dim" style="font-size:10px;">TELEPON</span>
                                            <input wire:model="regEditPhone" class="input-field" style="font-size:12px;" />
                                        </div>
                                        <div class="col" style="gap:4px;min-width:120px;">
                                            <span class="mono dim" style="font-size:10px;">KOTA</span>
                                            <input wire:model="regEditCity" class="input-field" style="font-size:12px;" />
                                        </div>
                                        <div class="col" style="gap:4px;min-width:160px;">
                                            <span class="mono dim" style="font-size:10px;">DIVISI</span>
                                            <select wire:model="regEditDivisionId" class="input-field" style="font-size:12px;">
                                                <option value="0">— pilih divisi —</option>
                                                @foreach($reg->event?->divisions ?? [] as $div)
                                                    <option value="{{ $div->id }}">{{ $div->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="flex gap-s">
                                            <button wire:click="saveRegistration" class="btn btn-sm btn-lime">Simpan</button>
                                            <button wire:click="$set('regEditId', 0)" class="btn btn-sm btn-ghost">Batal</button>
                                        </div>
                                    </div>
                                </div>
                                @endif
                    </td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
            </div>
        </section>
    @empty
        <div class="ad-card" style="padding:40px;text-align:center;"><span class="ad-note">{{ $rgAll->isEmpty() ? 'Belum ada pendaftaran.' : 'Tidak ada pendaftaran yang cocok dengan filter.' }}</span></div>
    @endforelse

    {{-- Bulk bar: muncul saat ada baris dicentang --}}
    @if(count($rgSel))
        <div class="ad-bulk" role="region" aria-label="Aksi massal">
            <b>{{ count($rgSel) }} rider dipilih</b>
            <select class="ad-inp" style="min-width:220px;" aria-label="Pindah divisi massal"
                x-data @change="if ($el.value && confirm('Pindahkan {{ count($rgSel) }} rider ke divisi ' + $el.options[$el.selectedIndex].text + '?')) { $wire.moveSelectedRegistrations(parseInt($el.value)); } $el.value = '';">
                <option value="">PINDAH DIVISI…</option>
                @foreach($rgDivs as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
            <button type="button" wire:click="approveSelectedRegistrations" class="ad-btn pri">APPROVE</button>
            <button type="button" wire:click="deleteSelectedRegistrations" wire:confirm="Hapus {{ count($rgSel) }} registrasi terpilih? Tidak bisa dibatalkan." class="ad-btn dng">HAPUS</button>
            <button type="button" wire:click="clearRegSelection" class="ad-btn">BATAL PILIH</button>
        </div>
    @endif
</div>

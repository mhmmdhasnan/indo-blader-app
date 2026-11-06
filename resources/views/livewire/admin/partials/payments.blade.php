{{-- ── PAYMENTS (acuan: design system — AdminPayments) ── --}}
@include('livewire.admin.partials.ad-styles')
@php
    $pyAll    = $registrations;
    $pyFee    = fn ($r) => (int) ($r->event?->entry_fee ?? 0);
    $pyIsFree = $activeEvent ? ((int) ($activeEvent->entry_fee ?? 0) === 0) : ($pyAll->isNotEmpty() && $pyAll->every(fn ($r) => $pyFee($r) === 0));
    $pyCounts = ['ALL' => $pyAll->count(), 'PENDING' => $pyAll->where('payment_status','PENDING')->count(), 'VERIFIED' => $pyAll->where('payment_status','VERIFIED')->count(), 'UNPAID' => $pyAll->whereNotIn('payment_status',['PENDING','VERIFIED'])->count()];
    $pyCollected = $pyAll->where('payment_status','VERIFIED')->sum(fn ($r) => $pyFee($r));
    $pyFiltered = $pyAll->when($payStatusFilter === 'UNPAID', fn ($c) => $c->whereNotIn('payment_status',['PENDING','VERIFIED']))
                        ->when(!in_array($payStatusFilter, ['ALL','UNPAID'], true), fn ($c) => $c->where('payment_status', $payStatusFilter));
    $pyGroups = $pyFiltered->groupBy(fn ($r) => $r->division?->name ?? 'Tanpa Divisi')->sortKeys();
@endphp
<div class="col ad-body" style="gap:12px;">

    @if($pyIsFree)
        <div class="ad-card" style="padding:16px;border-color:var(--lime);"><p class="ad-note" style="margin:0;"><b>Event ini gratis.</b> Tidak ada pembayaran yang perlu dicek; kolom Bukti berisi "Tidak perlu".</p></div>
    @else
        <div class="ad-sum">
            <div><span class="ad-lbl">Terkumpul</span><span class="v" style="color:var(--lime);">Rp {{ number_format($pyCollected, 0, ',', '.') }}</span></div>
            <div><span class="ad-lbl">Verified</span><span class="v">{{ $pyCounts['VERIFIED'] }} <small style="font-size:20px;color:var(--ink-3,#a3a39b);">/ {{ $pyCounts['ALL'] }}</small></span></div>
            <div style="{{ $pyCounts['PENDING'] ? 'border-color:#ffb020;' : '' }}"><span class="ad-lbl">Perlu dicek</span><span class="v" style="{{ $pyCounts['PENDING'] ? 'color:#ffb020;' : '' }}">{{ $pyCounts['PENDING'] }}</span></div>
        </div>
    @endif

    <div class="ad-tool">
        <div class="ad-tabs" role="tablist" aria-label="Filter status payment">
            @foreach(['ALL' => 'SEMUA', 'PENDING' => 'MENUNGGU CEK', 'VERIFIED' => 'VERIFIED', 'UNPAID' => 'BELUM BAYAR'] as $k => $lb)
                <button type="button" wire:click="$set('payStatusFilter','{{ $k }}')" class="{{ $payStatusFilter === $k ? 'on' : '' }}" aria-pressed="{{ $payStatusFilter === $k ? 'true' : 'false' }}">{{ $lb }} <b>{{ $pyCounts[$k] }}</b></button>
            @endforeach
        </div>
        <div class="ad-sp"></div>
        <button type="button" wire:click="verifyAllPayments" wire:confirm="Verifikasi semua payment MENUNGGU CEK di event ini?"
            class="ad-btn pri {{ ($pyCounts['PENDING'] === 0 || !$activeEventId) ? 'dis' : '' }}"
            @if($pyCounts['PENDING'] === 0 || !$activeEventId) disabled title="{{ !$activeEventId ? 'Pilih event dulu' : 'Tidak ada yang perlu diverifikasi' }}" @endif>VERIFIKASI SEMUA</button>
    </div>
    @if(session('bulkPayment'))<span class="ad-ok">{{ session('bulkPayment') }}</span>@endif
    @error('bulkPayment')<span class="ad-err">{{ $message }}</span>@enderror

    @forelse($pyGroups as $divName => $divRegs)
        <section class="ad-card" wire:key="py-group-{{ \Illuminate\Support\Str::slug($divName) }}">
            <div class="ad-gh">
                <b style="color:{{ $divName === 'Tanpa Divisi' ? '#ff6b5e' : 'var(--lime)' }};">{{ strtoupper($divName) }}</b>
                <span class="ad-lbl">{{ $divRegs->count() }} rider · {{ $divRegs->where('payment_status','VERIFIED')->count() }} verified</span>
            </div>
            <div class="ad-scroll">
            <table class="ad-tbl" style="min-width:820px;">
                <thead><tr><th>Rider</th><th>Jumlah</th><th>Metode</th><th>Bukti</th><th>Status</th><th style="text-align:right;">Aksi</th></tr></thead>
                <tbody>
                @foreach($divRegs as $reg)
                    @php $fee = $pyFee($reg); $ps = $reg->payment_status; @endphp
                    <tr wire:key="py-row-{{ $reg->id }}">
                        <td><div class="ad-nm"><x-avatar :initials="collect(preg_split('/\s+/', trim($reg->name), -1, PREG_SPLIT_NO_EMPTY))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('')" :src="$reg->photo_url" :size="40" /><b style="font-size:15px;">{{ $reg->name }}</b></div></td>
                        <td class="mono" style="font-size:13px;white-space:nowrap;">{{ $fee > 0 ? 'Rp ' . number_format($fee, 0, ',', '.') : 'Gratis' }}</td>
                        <td class="mono" style="font-size:12px;">{{ $fee > 0 ? ($reg->payment_method ?: '—') : '—' }}</td>
                        <td>
                            @if($fee === 0)
                                <span class="ad-lbl" style="text-transform:none;letter-spacing:.02em;">Tidak perlu</span>
                            @elseif($reg->payment_proof)
                                <a href="{{ $reg->payment_proof_url }}" target="_blank" rel="noopener"
                                   @if(!$reg->payment_proof_is_external) x-data @click.prevent="document.getElementById('proof-modal-{{ $reg->id }}').style.display = 'flex'" @endif
                                   class="ad-btn sm" style="display:inline-flex;align-items:center;text-decoration:none;color:inherit;">LIHAT BUKTI</a>
                                @if(!$reg->payment_proof_is_external)
                                <div id="proof-modal-{{ $reg->id }}" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);align-items:center;justify-content:center;padding:20px;" @click.self="$el.style.display='none'" x-data>
                                    <div style="position:relative;max-width:90vw;max-height:90vh;background:var(--bg);border:2px solid var(--ink);overflow:hidden;">
                                        <div class="between" style="padding:12px 16px;border-bottom:2px solid var(--ink);background:var(--bg-2);gap:12px;">
                                            <span class="label" style="font-size:14px;">Bukti Transfer — {{ $reg->name }}</span>
                                            <div class="flex gap-s">
                                                <a href="{{ $reg->payment_proof_url }}" target="_blank" rel="noopener" class="ad-btn sm" style="display:inline-flex;align-items:center;text-decoration:none;color:inherit;">BUKA ↗</a>
                                                <button type="button" @click="document.getElementById('proof-modal-{{ $reg->id }}').style.display='none'" class="ad-btn sm">TUTUP</button>
                                            </div>
                                        </div>
                                        <div style="padding:16px;overflow:auto;max-height:75vh;text-align:center;">
                                            <img src="{{ $reg->payment_proof_url }}" alt="Bukti transfer {{ $reg->name }}" loading="lazy" style="max-width:100%;max-height:65vh;object-fit:contain;">
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @else
                                <span class="ad-lbl" style="text-transform:none;letter-spacing:.02em;">Belum upload</span>
                            @endif
                        </td>
                        <td>
                            @if($ps === 'VERIFIED')<span class="ad-chip ok">✓ VERIFIED</span>
                            @elseif($ps === 'PENDING')<span class="ad-chip wt">MENUNGGU CEK</span>
                            @elseif($ps === 'REJECTED')<span class="ad-chip no">DITOLAK</span>
                            @else<span class="ad-chip">BELUM BAYAR</span>@endif
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            @if($ps === 'PENDING')
                                <button type="button" wire:click="verifyPayment({{ $reg->id }})" class="ad-btn pri sm">VERIFIKASI</button>
                            @endif
                            <x-ad-menu label="Aksi pembayaran {{ $reg->name }}">
                                <span class="ad-ml">Pembayaran</span>
                                <button type="button" class="ad-mi" wire:click="openEditPayment({{ $reg->id }})">EDIT PEMBAYARAN</button>
                                @if($ps === 'PENDING')
                                    <button type="button" class="ad-mi" wire:click="rejectPayment({{ $reg->id }})" wire:confirm="Tolak payment {{ $reg->name }}?">TOLAK BUKTI</button>
                                @elseif($ps === 'VERIFIED')
                                    <button type="button" class="ad-mi" wire:click="rejectPayment({{ $reg->id }})" wire:confirm="Batalkan verifikasi payment {{ $reg->name }}?">RESET STATUS</button>
                                @endif
                                <span class="ad-ml">Zona berbahaya</span>
                                <button type="button" class="ad-mi d" wire:click="deletePayment({{ $reg->id }})" wire:confirm="Reset data payment {{ $reg->name }}? Bukti transfer akan dihapus.">HAPUS DATA PAYMENT…</button>
                                <button type="button" class="ad-mi d" wire:click="deleteRegistration({{ $reg->id }})" wire:confirm="Hapus registrasi {{ $reg->name }} beserta pembayarannya? Tidak bisa dibatalkan.">HAPUS REGISTRASI…</button>
                            </x-ad-menu>
                        </td>
                    </tr>
                    @if($payEditId === $reg->id)
                    <tr wire:key="py-edit-{{ $reg->id }}"><td colspan="6" style="padding:0;">
                        {{-- Inline edit payment --}}
                        @if($payEditId === $reg->id)
                        <div style="padding:14px 18px 16px;background:var(--bg-2);border-top:1px solid var(--line);">
                            <div class="flex gap-m" style="flex-wrap:wrap;align-items:flex-end;">
                                <div class="col" style="gap:4px;">
                                    <span class="mono dim" style="font-size:10px;">METODE</span>
                                    <select wire:model="payEditMethod" class="input-field" style="font-size:12px;">
                                        <option>Transfer</option>
                                        <option>QRIS</option>
                                        <option>Cash</option>
                                    </select>
                                </div>
                                <div class="col" style="gap:4px;">
                                    <span class="mono dim" style="font-size:10px;">STATUS</span>
                                    <select wire:model="payEditStatus" class="input-field" style="font-size:12px;">
                                        <option value="PENDING">PENDING</option>
                                        <option value="VERIFIED">VERIFIED</option>
                                        <option value="REJECTED">REJECTED</option>
                                    </select>
                                </div>
                                <div class="col" style="gap:4px;">
                                    <span class="mono dim" style="font-size:10px;">BUKTI TRANSFER (opsional)</span>
                                    <input type="file" wire:model="payEditProof" accept="image/*" class="input-field" style="font-size:11px;" />
                                </div>
                                <div class="flex gap-s">
                                    <button wire:click="savePayment" class="btn btn-sm btn-lime">Simpan</button>
                                    <button wire:click="$set('payEditId', 0)" class="btn btn-sm btn-ghost">Batal</button>
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
        <div class="ad-card" style="padding:40px;text-align:center;"><span class="ad-note">{{ $pyAll->isEmpty() ? 'Belum ada pembayaran.' : 'Tidak ada pembayaran yang cocok dengan filter.' }}</span></div>
    @endforelse
</div>

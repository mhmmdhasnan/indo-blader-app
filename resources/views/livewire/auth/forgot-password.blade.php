<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:32px;">
            <x-logo :size="16" />
            <h1 class="display" style="font-size:36px;margin-top:16px;">LUPA PASSWORD</h1>
            <p class="label" style="color:var(--ink-dim);margin-top:4px;">Masukkan email akunmu, kami kirim link reset</p>
        </div>

        @if($sent)
            <div style="padding:14px 16px;background:var(--bg-2);border:2px solid var(--lime);border-radius:3px;">
                <span class="label" style="font-size:13px;color:var(--lime);">Kalau email itu terdaftar, link reset password sudah dikirim. Cek inbox (dan folder spam). Link berlaku 60 menit.</span>
            </div>
        @else
        <form wire:submit="send" style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="label" style="font-size:11px;color:var(--ink-dim);display:block;margin-bottom:6px;">EMAIL</label>
                <input wire:model="email" type="email" autocomplete="email" placeholder="email@kamu.com"
                    style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:14px;outline:none;"
                    onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'">
                @error('email') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-lime" wire:loading.attr="disabled">
                <span wire:loading.remove>KIRIM LINK RESET</span>
                <span wire:loading>...</span>
            </button>
        </form>
        @endif

        <p class="label" style="text-align:center;margin-top:24px;font-size:13px;color:var(--ink-dim);">
            <a href="{{ route('login') }}" class="label" style="color:var(--lime);">← Kembali ke login</a>
        </p>
    </div>
</div>

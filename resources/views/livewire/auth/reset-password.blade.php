<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:32px;">
            <x-logo :size="16" />
            <h1 class="display" style="font-size:36px;margin-top:16px;">RESET PASSWORD</h1>
            <p class="label" style="color:var(--ink-dim);margin-top:4px;">Buat password baru untuk akunmu</p>
        </div>

        <form wire:submit="resetPassword" style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="label" style="font-size:11px;color:var(--ink-dim);display:block;margin-bottom:6px;">EMAIL</label>
                <input wire:model="email" type="email" autocomplete="email"
                    style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:14px;outline:none;"
                    onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'">
                @error('email') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" style="font-size:11px;color:var(--ink-dim);display:block;margin-bottom:6px;">PASSWORD BARU</label>
                <input wire:model="password" type="password" autocomplete="new-password" placeholder="min. 8 karakter"
                    style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:14px;outline:none;"
                    onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'">
                @error('password') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" style="font-size:11px;color:var(--ink-dim);display:block;margin-bottom:6px;">ULANGI PASSWORD</label>
                <input wire:model="password_confirmation" type="password" autocomplete="new-password"
                    style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:14px;outline:none;"
                    onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'">
                @error('password_confirmation') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-lime" wire:loading.attr="disabled">
                <span wire:loading.remove>UBAH PASSWORD</span>
                <span wire:loading>...</span>
            </button>
        </form>

        <p class="label" style="text-align:center;margin-top:24px;font-size:13px;color:var(--ink-dim);">
            <a href="{{ route('login') }}" class="label" style="color:var(--lime);">← Kembali ke login</a>
        </p>
    </div>
</div>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:32px;">
            <x-logo :size="48" />
            <h1 class="display" style="font-size:36px;margin-top:16px;">LOGIN</h1>
            <p class="label" style="color:var(--ink-dim);margin-top:4px;">Masuk ke akun Indo Blader kamu</p>
        </div>

        <form wire:submit="login" style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="label" style="font-size:11px;color:var(--ink-dim);display:block;margin-bottom:6px;">EMAIL</label>
                <input wire:model="email" type="email" autocomplete="email"
                    style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:14px;outline:none;"
                    onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'"
                    placeholder="rider@email.com">
                @error('email') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="label" style="font-size:11px;color:var(--ink-dim);display:block;margin-bottom:6px;">PASSWORD</label>
                <input wire:model="password" type="password" autocomplete="current-password"
                    style="width:100%;padding:12px 14px;background:var(--surface);border:2px solid var(--line);border-radius:3px;color:var(--ink);font-family:inherit;font-size:14px;outline:none;"
                    onfocus="this.style.borderColor='var(--lime)'" onblur="this.style.borderColor='var(--line)'"
                    placeholder="••••••••">
                @error('password') <p style="color:var(--red);font-size:12px;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <label class="flex label" style="gap:8px;align-items:center;font-size:12px;cursor:pointer;">
                <input wire:model="remember" type="checkbox" style="accent-color:var(--lime);">
                Ingat saya
            </label>

            <button type="submit" class="btn btn-lime" style="margin-top:4px;" wire:loading.attr="disabled">
                <span wire:loading.remove>MASUK</span>
                <span wire:loading>...</span>
            </button>
        </form>

        <p class="label" style="text-align:center;margin-top:24px;font-size:13px;color:var(--ink-dim);">
            Belum punya akun?
            <a href="{{ route('register.account') }}" class="label" style="color:var(--lime);">Daftar di sini</a>
        </p>
    </div>
</div>

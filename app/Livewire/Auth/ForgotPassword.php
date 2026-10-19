<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Lupa Password — FRAMEBLADESCORE')]
class ForgotPassword extends Component
{
    public string $email = '';
    public bool $sent = false;

    public function send(): void
    {
        $this->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_THROTTLED) {
            $this->addError('email', 'Terlalu sering mencoba. Tunggu sebentar lalu coba lagi.');
            return;
        }

        // Pesan sama baik email terdaftar maupun tidak, supaya email user tidak bisa ditebak.
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}

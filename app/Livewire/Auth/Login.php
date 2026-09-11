<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Login — FRAMEBLADESCORE')]
class Login extends Component
{
    public string $username = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        if (!Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
            $this->addError('username', 'Username atau password salah.');
            return;
        }

        session()->regenerate();

        $redirect = match (auth()->user()->role) {
            'admin'      => route('admin'),
            'judge'      => route('judge'),
            'head_judge' => route('judge'),
            default      => route('rider.dashboard'),
        };

        $this->redirect($redirect, navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

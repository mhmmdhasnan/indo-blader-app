<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Login — Indo Blader')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Email atau password salah.');
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

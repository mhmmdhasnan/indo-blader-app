<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Daftar Akun — FRAMEBLADESCORE')]
class Register extends Component
{
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:30|alpha_dash|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $this->name,
            'username' => $this->username,
            'email'    => $this->email,
            'password' => $this->password,
            'role'     => 'rider',
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('rider.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}

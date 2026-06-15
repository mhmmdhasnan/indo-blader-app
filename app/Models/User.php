<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isJudge(): bool
    {
        return in_array($this->role, ['judge', 'head_judge']);
    }

    public function isHeadJudge(): bool
    {
        return $this->role === 'head_judge';
    }

    public function isRider(): bool
    {
        return $this->role === 'rider';
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function rider(): HasOne
    {
        return $this->hasOne(Rider::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventDivision extends Model
{
    protected $fillable = [
        'event_id', 'name', 'discipline', 'level', 'slots', 'filled', 'is_active',
        'live_stage', 'live_final_completed_at', 'best_trick_active',
        'qualification_announced_at', 'final_announced_at',
    ];

    protected $casts = [
        'is_active'                   => 'boolean',
        'live_final_completed_at'     => 'datetime',
        'best_trick_active'           => 'boolean',
        'qualification_announced_at'  => 'datetime',
        'final_announced_at'          => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'division_id');
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(Bracket::class, 'division_id');
    }

    public function finalists(): HasMany
    {
        return $this->hasMany(DivisionFinalist::class, 'event_division_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(DivisionGroup::class, 'event_division_id');
    }

    public function getIsFinalStageAttribute(): bool
    {
        return $this->live_stage === 'FINAL';
    }

    public function getIsUnlimitedAttribute(): bool
    {
        return $this->slots === null;
    }

    public function getFillPctAttribute(): int
    {
        if ($this->slots === null || $this->slots === 0) return 0;
        return (int) round($this->filled / $this->slots * 100);
    }

    public function getLabelAttribute(): string
    {
        return $this->name;
    }
}

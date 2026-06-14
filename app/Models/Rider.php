<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    protected $fillable = [
        'slug', 'name', 'nick', 'city', 'age', 'category', 'stance',
        'points', 'sponsor', 'wins', 'podiums', 'comps', 'best_score',
        'bio', 'achievements', 'ig', 'yt', 'tt',
    ];

    protected $casts = [
        'achievements' => 'array',
        'best_score' => 'float',
    ];

    public function judgeScores(): HasMany
    {
        return $this->hasMany(JudgeScore::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'name', 'name');
    }

    public function getRankAttribute(): int
    {
        return static::orderByDesc('points')->pluck('id')->search($this->id) + 1;
    }

    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'STREET' => 'lime',
            'PARK'   => 'solid',
            'VERT'   => 'red',
            'FLAT'   => 'out',
            default  => 'out',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'STREET' => 'Street',
            'PARK'   => 'Park',
            'VERT'   => 'Vert',
            'FLAT'   => 'Flatland',
            default  => $this->category,
        };
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn ($w) => strtoupper($w[0]))
            ->take(2)
            ->implode('');
    }
}

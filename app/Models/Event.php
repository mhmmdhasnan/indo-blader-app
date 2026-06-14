<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'slug', 'title', 'edition', 'city', 'venue', 'date', 'date_label',
        'status', 'categories', 'prize', 'slots', 'filled', 'featured', 'blurb',
    ];

    protected $casts = [
        'categories' => 'array',
        'date'       => 'date',
        'featured'   => 'boolean',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function judgeScores(): HasMany
    {
        return $this->hasMany(JudgeScore::class);
    }

    public function getFillPctAttribute(): int
    {
        return $this->slots > 0 ? (int) round($this->filled / $this->slots * 100) : 0;
    }

    public function getPrizeFormattedAttribute(): string
    {
        if ($this->prize >= 1_000_000_000) {
            return 'Rp ' . number_format($this->prize / 1_000_000_000, 1) . ' M';
        }
        return 'Rp ' . number_format($this->prize / 1_000_000, 0) . ' jt';
    }

    public function getPrizeLongAttribute(): string
    {
        return 'Rp ' . number_format($this->prize, 0, ',', '.');
    }

    public function getStatusBadgeVariantAttribute(): string
    {
        return match ($this->status) {
            'OPEN'    => 'lime',
            'CLOSING' => 'red',
            'LIVE'    => 'red',
            default   => 'out',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'OPEN'    => 'REG OPEN',
            'CLOSING' => 'CLOSING SOON',
            'SOON'    => 'COMING SOON',
            'FULL'    => 'FULL',
            'LIVE'    => 'LIVE NOW',
            default   => $this->status,
        };
    }
}

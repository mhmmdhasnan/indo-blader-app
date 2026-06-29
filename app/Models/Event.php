<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'slug', 'title', 'edition', 'city', 'venue', 'date', 'date_label',
        'status', 'type', 'categories', 'prize', 'featured', 'blurb', 'banner',
        'run_duration', 'live_rider_id', 'live_run_number', 'live_phase', 'live_started_at',
    ];

    protected $casts = [
        'categories'     => 'array',
        'date'           => 'datetime',
        'featured'       => 'boolean',
        'live_started_at'=> 'datetime',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function judgeScores(): HasMany
    {
        return $this->hasMany(JudgeScore::class);
    }

    public function qualificationRounds(): HasMany
    {
        return $this->hasMany(QualificationRound::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(Bracket::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(EventDivision::class);
    }

    public function rankingHistories(): HasMany
    {
        return $this->hasMany(RankingHistory::class);
    }

    public function scoringCriteria(): BelongsToMany
    {
        return $this->belongsToMany(ScoringCriterion::class, 'event_scoring_criteria')
            ->withPivot('applies_to', 'display_order')
            ->orderByPivot('display_order')
            ->withTimestamps();
    }

    public function judgeAssignments(): HasMany
    {
        return $this->hasMany(EventJudgeAssignment::class);
    }

    public function criteriaFor(string $mode): \Illuminate\Database\Eloquent\Collection
    {
        return $this->scoringCriteria()
            ->wherePivotIn('applies_to', [$mode, 'BOTH'])
            ->orderByPivot('display_order')
            ->get();
    }

    public function getFilledAttribute(): int
    {
        if ($this->relationLoaded('divisions')) {
            return (int) $this->divisions->sum('filled');
        }
        return (int) $this->divisions()->sum('filled');
    }

    public function getSlotsAttribute(): ?int
    {
        if ($this->relationLoaded('divisions')) {
            if ($this->divisions->contains(fn ($d) => $d->slots === null)) return null;
            return (int) $this->divisions->sum('slots');
        }
        if ($this->divisions()->whereNull('slots')->exists()) return null;
        return (int) $this->divisions()->sum('slots');
    }

    public function getFillPctAttribute(): int
    {
        $slots = $this->slots;
        return $slots > 0 ? (int) round($this->filled / $slots * 100) : 0;
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

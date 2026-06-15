<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScoringCriterion extends Model
{
    protected $table = 'scoring_criteria';

    protected $fillable = ['name', 'key', 'display_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_scoring_criteria')
            ->withPivot('applies_to', 'display_order')
            ->withTimestamps();
    }
}

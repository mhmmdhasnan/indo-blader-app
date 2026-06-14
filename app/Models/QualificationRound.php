<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualificationRound extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'name', 'round_number', 'pairing_type', 'status'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function qualificationMatches(): HasMany
    {
        return $this->hasMany(QualificationMatch::class);
    }
}

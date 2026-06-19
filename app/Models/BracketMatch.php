<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BracketMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'bracket_id',
        'round',
        'match_number',
        'rider_a_registration_id',
        'rider_b_registration_id',
        'trick_id',
        'winner_registration_id',
        'score_a',
        'score_b',
        'status',
        'submission_deadline',
    ];

    protected $casts = [
        'score_a'             => 'float',
        'score_b'             => 'float',
        'submission_deadline' => 'datetime',
    ];

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(Bracket::class);
    }

    public function riderA(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'rider_a_registration_id');
    }

    public function riderB(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'rider_b_registration_id');
    }

    public function trick(): BelongsTo
    {
        return $this->belongsTo(Trick::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'winner_registration_id');
    }
}

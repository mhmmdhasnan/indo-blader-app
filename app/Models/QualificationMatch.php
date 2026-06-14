<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'qualification_round_id',
        'rider_a_registration_id',
        'rider_b_registration_id',
        'trick_id',
        'status',
        'winner_registration_id',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(QualificationRound::class, 'qualification_round_id');
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

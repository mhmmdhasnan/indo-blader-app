<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingHistory extends Model
{
    use HasFactory;

    protected $fillable = ['rider_id', 'event_id', 'placement', 'points_earned'];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

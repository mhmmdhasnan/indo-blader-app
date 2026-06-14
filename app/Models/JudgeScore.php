<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeScore extends Model
{
    protected $fillable = [
        'event_id', 'rider_id', 'run_number',
        'execution', 'style', 'creativity', 'difficulty', 'total', 'status',
    ];

    protected $casts = [
        'execution'  => 'float',
        'style'      => 'float',
        'creativity' => 'float',
        'difficulty' => 'float',
        'total'      => 'float',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function getBestRunAttribute(): float
    {
        return $this->total;
    }
}

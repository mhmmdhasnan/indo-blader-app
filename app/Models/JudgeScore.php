<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JudgeScore extends Model
{
    protected $fillable = [
        'judge_user_id', 'event_id', 'rider_id', 'run_number', 'scoring_mode',
        'execution', 'style', 'creativity', 'difficulty', 'consistency', 'total', 'status',
    ];

    protected $casts = [
        'execution'   => 'float',
        'style'       => 'float',
        'creativity'  => 'float',
        'difficulty'  => 'float',
        'consistency' => 'float',
        'total'       => 'float',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_user_id');
    }

    public function scoreDetails(): HasMany
    {
        return $this->hasMany(ScoreDetail::class);
    }

    public function getBestRunAttribute(): float
    {
        return $this->total;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreDetail extends Model
{
    use HasFactory;

    protected $fillable = ['judge_score_id', 'criteria', 'score'];

    public function criterion(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ScoringCriterion::class, 'criteria', 'key');
    }

    protected $casts = [
        'score' => 'float',
    ];

    public function judgeScore(): BelongsTo
    {
        return $this->belongsTo(JudgeScore::class);
    }
}

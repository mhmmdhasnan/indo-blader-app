<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_type',
        'match_id',
        'registration_id',
        'video_path',
        'status',
        'judge_feedback',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolveMatch(): QualificationMatch|BracketMatch|null
    {
        return match ($this->match_type) {
            'QUALIFICATION' => QualificationMatch::find($this->match_id),
            'PLAYOFF'       => BracketMatch::find($this->match_id),
            default         => null,
        };
    }
}

<?php

namespace App\Observers;

use App\Events\LiveScoreUpdated;
use App\Models\JudgeScore;

class JudgeScoreObserver
{
    /**
     * A score flipping to/from DONE drives the REVEALING overlay and the
     * leaderboard; a total correction on an already-DONE score should also
     * push, everything else (judge still filling the form) shouldn't.
     */
    private const LIVE_FIELDS = ['status', 'total'];

    public function saved(JudgeScore $score): void
    {
        if ($score->wasChanged(self::LIVE_FIELDS)) {
            LiveScoreUpdated::dispatch($score->event_id);
        }
    }
}

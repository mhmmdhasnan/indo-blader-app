<?php

namespace App\Observers;

use App\Events\LiveScoreUpdated;
use App\Models\EventDivision;

class EventDivisionObserver
{
    private const LIVE_FIELDS = [
        'live_stage', 'best_trick_active', 'qualification_announced_at', 'final_announced_at',
    ];

    public function saved(EventDivision $division): void
    {
        if ($division->wasChanged(self::LIVE_FIELDS)) {
            LiveScoreUpdated::dispatch($division->event_id);
        }
    }
}

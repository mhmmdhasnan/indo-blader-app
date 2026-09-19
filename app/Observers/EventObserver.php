<?php

namespace App\Observers;

use App\Events\LiveScoreUpdated;
use App\Models\Event;

class EventObserver
{
    /**
     * Fields that feed the public /live display. Any other change on Event
     * (title, prize, schedule, ...) shouldn't spam a broadcast.
     */
    private const LIVE_FIELDS = [
        'live_phase', 'live_started_at', 'live_rider_id', 'live_run_number',
        'active_division_id', 'active_group_id', 'idle_screen',
    ];

    public function saved(Event $event): void
    {
        if ($event->wasChanged(self::LIVE_FIELDS)) {
            LiveScoreUpdated::dispatch($event->id);
        }
    }
}

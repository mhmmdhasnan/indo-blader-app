<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;

class LiveScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private const VERSION_KEY = 'live-scoring:version';

    /**
     * Bump the cache version (invalidating the cached /live payload) and then
     * broadcast — in that order, so a viewer reacting to the broadcast can never
     * be served the pre-change payload.
     */
    public static function push(int $eventId): void
    {
        Cache::store('file')->forever(self::VERSION_KEY, hrtime(true));
        static::dispatch($eventId);
    }

    public static function version(): string
    {
        return (string) Cache::store('file')->get(self::VERSION_KEY, 0);
    }

    public function __construct(public int $eventId)
    {
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("live-scoring.{$this->eventId}")];
    }

    public function broadcastAs(): string
    {
        return 'updated';
    }
}

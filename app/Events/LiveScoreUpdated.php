<?php

namespace App\Events;

use App\Models\Event;
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

    /**
     * Bawa state waktu-kritis langsung di pesan websocket, supaya klien bisa memperbarui
     * chip/timer tanpa menunggu refresh HTTP, dan menyinkronkan jamnya dengan server ('t', ms).
     */
    public function broadcastWith(): array
    {
        $e = Event::query()->whereKey($this->eventId)
            ->first(['id', 'live_phase', 'live_jam', 'live_started_at', 'live_duration', 'run_duration']);

        return [
            'phase'    => $e?->live_phase,
            'jam'      => (bool) $e?->live_jam,
            'started'  => $e?->live_started_at?->timestamp,
            'duration' => $e?->live_jam ? ($e->live_duration ?? 180) : ($e?->run_duration ?? 60),
            't'        => (int) round(microtime(true) * 1000),
        ];
    }

    public function broadcastAs(): string
    {
        return 'updated';
    }
}

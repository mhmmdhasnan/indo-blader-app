<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Operator baru memilih run / rider. Layar Head Judge & Judge mendengarkan channel ini dan langsung
 * menyinkronkan diri (tanpa menunggu poll 5 detik). Sengaja terpisah dari LiveScoreUpdated supaya
 * tidak membuat ratusan layar penonton /live ikut refresh tiap Operator mengklik.
 */
class OperatorPickUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $eventId)
    {
    }

    /** @return array<int, \Illuminate\Broadcasting\Channel> */
    public function broadcastOn(): array
    {
        return [new Channel("operator-pick.{$this->eventId}")];
    }

    public function broadcastAs(): string
    {
        return 'picked';
    }
}

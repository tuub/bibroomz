<?php

namespace App\Events;

use App\Models\Happening;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class HappeningBroadcastEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public Happening $happening,
        public User $user,
        private array $payload = [],
    ) {
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('happenings.' . $this->user->id);
    }
}

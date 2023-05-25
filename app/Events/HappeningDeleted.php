<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HappeningDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        private $id,
        private $user1,
        private $user2 = null,
    ) {
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['id' => $this->id];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $user1 = $this->user1;
        $user2 = $this->user2;

        $channel1 = new PrivateChannel('happenings.' . $user1->id);

        if (is_null($user2) or $user1->is($user2)) {
            return $channel1;
        }

        $channel2 = new PrivateChannel('happenings.' . $user2->id);

        return [$channel1, $channel2];
    }
}

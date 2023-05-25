<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HappeningUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $happening;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($happening)
    {
        $this->happening = $happening;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $user1 = $this->happening->user1;
        $user2 = $this->happening->user2;

        $channel1 = new PrivateChannel('happenings.' . $user1->id);

        if (is_null($user2) or $user1->is($user2)) {
            return $channel1;
        }

        $channel2 = new PrivateChannel('happenings.' . $user2->id);

        return [$channel1, $channel2];
    }

}

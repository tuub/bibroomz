<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HappeningCreated implements ShouldBroadcast
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
        return array_merge(
            [new PrivateChannel('happenings.' . $this->happening->user1->id)],
            $this->happening->user2
                ? [new PrivateChannel('happenings.' . $this->happening->user2->id)]
                : [],
        );
    }
}

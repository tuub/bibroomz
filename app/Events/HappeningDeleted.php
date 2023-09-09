<?php

namespace App\Events;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HappeningDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $happening;
    public User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Happening $happening, User $user)
    {
        $this->happening = $happening->only(['id', 'start', 'end', 'resource']);
        $this->user = $user;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'happening' => [
                'id' => $this->happening['id'],
                'start' => Carbon::parse($this->happening['start'])->format('Y-m-d H:i'),
                'end' => Carbon::parse($this->happening['end'])->format('Y-m-d H:i'),
                'resource' => [
                    'id' => $this->happening['resource']['id'],
                    'title' => $this->happening['resource']['title'],
                    'institutionId' => $this->happening['resource']['institution_id'],
                ],
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('happenings.' . $this->user->id);
    }
}

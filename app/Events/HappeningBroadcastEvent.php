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

class HappeningBroadcastEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Happening $happening;
    public User $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Happening $happening, User $user)
    {
        $this->happening = $happening;
        $this->user = $user;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $happening = $this->happening;

        $owner = User::find($happening->user_id_01);
        $is_verification_required = $happening->resource->is_verification_required && !$owner->is_admin;

        return [
            'happening' => [
                'id' => $happening->id,
                'user_01' => $owner->name,
                'user_02' => User::find($happening->user_id_02)->name ?? $happening->verifier,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'is_verified' => $happening->is_verified,
                'resource' => [
                    'id' => $happening->resource_id,
                    'title' => $happening->resource->title,
                    'location' => $happening->resource->location,
                ],
                'reserved_at' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
                'verified_at' => Carbon::parse($happening->verified_at)->format('Y-m-d H:i'),
                'can' => $happening->getPermissions($this->user),
                'is_verification_required' => $is_verification_required,
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

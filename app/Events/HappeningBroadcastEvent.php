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

abstract class HappeningBroadcastEvent implements ShouldBroadcast
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
        $resource = $happening->resource;

        /** @var User */
        $user1 = User::find($happening->user_id_01);

        $is_admin = $user1->hasPermission('no_verifier', $resource->resource_group->institution);
        $is_verification_required = $resource->is_verification_required && !$is_admin;

        return [
            'happening' => [
                'id' => $happening->id,
                'user_01' => $user1->name,
                'user_02' => User::find($happening->user_id_02)->name ?? $happening->verifier,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'isVerified' => $happening->is_verified,
                'resource' => [
                    'id' => $resource->id,
                    'title' => $resource->getTranslations('title'),
                    'capacity' => $resource->capacity,
                    'location' => $resource->getTranslations('location'),
                    'locationUri' => $resource->location_uri,
                    'description' => $resource->getTranslations('description'),
                    'resourceGroup' => $resource->resource_group->getTranslations('term_singular'),
                    'resourceGroupId' => $resource->resource_group_id,
                ],
                'reservedAt' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
                'verifiedAt' => Carbon::parse($happening->verified_at)->format('Y-m-d H:i'),
                'can' => $happening->getPermissions($this->user),
                'isVerificationRequired' => $is_verification_required,
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

<?php

namespace App\Services\Happenings;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;

class HappeningBroadcastPayloadFactory
{
    /**
     * @return array<string, mixed>
     */
    public function make(Happening $happening, User $recipient): array
    {
        $happening->loadMissing([
            'resource.resource_group.institution',
            'user1',
            'user2',
        ]);

        $resource = $happening->resource;
        $user1 = $happening->user1 ?? User::findOrFail($happening->user_id_01);
        $isAdmin = $user1->hasPermission('no_verifier', $resource->resource_group->institution);
        $isVerificationRequired = $resource->is_verification_required && !$isAdmin;

        return [
            'happening' => [
                'id' => $happening->id,
                'user_01' => $user1->name,
                'user_02' => $happening->user2 !== null ? $happening->user2->name : $happening->verifier,
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
                    'institution' => $resource->resource_group->institution->title,
                ],
                'reservedAt' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
                'verifiedAt' => Carbon::parse($happening->verified_at)->format('Y-m-d H:i'),
                'can' => $happening->getPermissions($recipient),
                'isVerificationRequired' => $isVerificationRequired,
                'label' => $happening->getTranslations('label'),
            ],
        ];
    }
}

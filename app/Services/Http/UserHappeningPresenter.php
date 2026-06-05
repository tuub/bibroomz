<?php

namespace App\Services\Http;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;

class UserHappeningPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Happening $happening, User $currentUser): array
    {
        return [
            'id' => $happening->id,
            'user_01' => $happening->user1?->name,
            'user_02' => $happening->user2 ? $happening->user2->name : $happening->verifier,
            'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
            'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
            'can' => $happening->getPermissions($currentUser),
            'isVerified' => $happening->is_verified,
            'resource' => [
                'id' => $happening->resource_id,
                'title' => $happening->resource->getTranslations('title'),
                'capacity' => $happening->resource->capacity,
                'location' => $happening->resource->getTranslations('location'),
                'locationUri' => $happening->resource->location_uri,
                'description' => $happening->resource->getTranslations('description'),
                'resourceGroup' => $happening->resource->resource_group->getTranslations('term_singular'),
                'institution' => $happening->resource->resource_group->institution->title,
                'institutionId' => $happening->resource->resource_group->institution_id,
            ],
            'reservedAt' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
            'verifiedAt' => Carbon::parse($happening->verified_at)->format('Y-m-d H:i'),
            'isVerificationRequired' => $happening->resource->is_verification_required,
            'label' => $happening->getTranslations('label'),
        ];
    }
}

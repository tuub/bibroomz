<?php

namespace App\Services\Happenings;

use App\Models\Happening;

class AdminHappeningPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Happening $happening): array
    {
        return [
            'id' => $happening->id,
            'start' => $happening->start,
            'end' => $happening->end,
            'institution_id' => $happening->resource->resource_group->institution->id,
            'institution' => $happening->resource->resource_group->institution->getTranslations('title'),
            'resource_group' => $happening->resource->resource_group->getTranslations('title'),
            'resource' => $happening->resource->getTranslations('title'),
            'user1' => $happening->user1?->name,
            'user2' => $happening->is_verified ? $happening->user2?->name : $happening->verifier,
            'label' => $happening->getTranslations('label'),
            'is_verified' => $happening->is_verified,
        ];
    }
}

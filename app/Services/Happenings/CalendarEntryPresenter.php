<?php

namespace App\Services\Happenings;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;

class CalendarEntryPresenter
{
    public function __construct(private readonly HappeningStatusCalculator $statusCalculator) {}

    /**
     * @return array<string, mixed>
     */
    public function presentHappening(Happening $happening, ?User $viewer): array
    {
        $status = $this->statusCalculator->calculate($happening, $viewer);
        $ownerName = $viewer?->isAdmin() ? $happening->user1?->name : null;

        return [
            'id' => $happening->id,
            'status' => $status,
            'resourceId' => $happening->resource->id,
            'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
            'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
            'classNames' => $status['type'],
            'can' => $happening->getPermissions($viewer),
            'isVerificationRequired' => $happening->resource->is_verification_required,
            'resource' => [
                'resourceGroup' => $happening->resource->resource_group->getTranslations('term_singular'),
                'institution' => $happening->resource->resource_group->institution->title,
            ],
            'user_01' => $ownerName,
            'label' => $happening->getTranslations('label'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentInstitutionClosing(Closing $closing, Resource $resource): array
    {
        return [
            'id' => $closing->id,
            'status' => null,
            'resourceId' => $resource->id,
            'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
            'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
            'description' => $closing->getTranslations('description'),
            'resource_group' => $resource->resource_group->getTranslations('term_singular'),
            'user' => null,
            'classNames' => 'closed',
            'display' => 'background',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentResourceClosing(Closing $closing, Resource $resource): array
    {
        return [
            'id' => $closing->id,
            'status' => null,
            'resourceId' => $resource->id,
            'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
            'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
            'description' => $closing->getTranslations('description'),
            'user' => null,
            'classNames' => 'closed',
            'display' => 'background',
        ];
    }
}

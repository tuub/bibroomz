<?php

namespace App\Services\Happenings;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ListCalendarEntriesAction
{
    public function __construct(private CalendarEntryPresenter $presenter)
    {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(
        ResourceGroup $resourceGroup,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?User $viewer,
    ): Collection {
        return $this->getHappeningsOutputCollection($resourceGroup, $start, $end, $viewer)
            ->concat($this->getInstitutionClosingsOutputCollection($resourceGroup, $start, $end))
            ->concat($this->getResourceClosingsOutputCollection($resourceGroup, $start, $end))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getHappeningsOutputCollection(
        ResourceGroup $resourceGroup,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?User $viewer,
    ): Collection {
        $happenings = Happening::with([
            'resource.resource_group.institution',
            'user1',
            'user2',
        ])
            ->resourceGroup($resourceGroup)
            ->whereDate('start', '>=', $start)
            ->whereDate('end', '<=', $end)
            ->active()
            ->get()
            ->filter->isResourceOpen()
            ->map->withAdjustedStartEndTimes()
            ->filter(fn (mixed $happening): bool => $happening instanceof Happening)
            ->values();

        return $happenings->map(fn (Happening $happening) => $this->presenter->presentHappening($happening, $viewer));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getInstitutionClosingsOutputCollection(
        ResourceGroup $resourceGroup,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Collection {
        $closings = $resourceGroup->institution->closings;
        $resources = $resourceGroup->resources;

        return $closings
            ->filter(fn (Closing $closing): bool => $closing->end->isAfter($start) && $closing->start->isBefore($end))
            ->flatMap(fn (Closing $closing) => $resources
                ->map(fn (Resource $resource) => $this->presenter->presentInstitutionClosing($closing, $resource)));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getResourceClosingsOutputCollection(
        ResourceGroup $resourceGroup,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Collection {
        return $resourceGroup->resources->flatMap(function (Resource $resource) use ($start, $end) {
            return $resource->closings
                ->filter(fn (Closing $closing): bool =>
                    $closing->end->isAfter($start) && $closing->start->isBefore($end))
                ->map(fn (Closing $closing) => $this->presenter->presentResourceClosing($closing, $resource));
        });
    }
}

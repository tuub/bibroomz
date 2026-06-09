<?php

namespace App\Services\Http;

use App\Models\Happening;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Support\Collection;

class ListUserHappeningsAction
{
    public function __construct(private readonly UserHappeningPresenter $presenter) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(ResourceGroup $resourceGroup, User $user): Collection
    {
        $happenings = Happening::query()
            ->with([
                'resource.resource_group.institution',
                'user1',
                'user2',
            ])
            ->resourceGroup($resourceGroup)
            ->user($user)
            ->active()
            ->weekly()
            ->orderBy('start')
            ->get()
            ->filter(static fn (Happening $happening): bool => $happening->isResourceOpen())
            ->map(static fn (Happening $happening): ?Happening => $happening->withAdjustedStartEndTimes())
            ->filter(static fn (mixed $happening): bool => $happening instanceof Happening)
            ->values();

        return $happenings
            ->map(fn (Happening $happening): array => $this->presenter->present($happening, $user))
            ->values();
    }
}

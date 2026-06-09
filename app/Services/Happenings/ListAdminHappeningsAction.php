<?php

namespace App\Services\Happenings;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ListAdminHappeningsAction
{
    public function __construct(private readonly AdminHappeningPresenter $presenter) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(User $user): Collection
    {
        return Happening::with(['resource.resource_group.institution', 'user1', 'user2'])
            ->whereDate('start', '>=', Carbon::now())
            ->orderBy('start')
            ->get()
            ->filter->isViewableByUser($user)
            ->map(fn (Happening $happening): array => $this->presenter->present($happening))
            ->values();
    }
}

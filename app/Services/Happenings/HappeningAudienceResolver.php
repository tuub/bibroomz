<?php

namespace App\Services\Happenings;

use App\Models\Happening;
use App\Models\User;
use Illuminate\Support\Collection;

class HappeningAudienceResolver
{
    /**
     * @return Collection<int, User>
     */
    public function resolve(Happening $happening): Collection
    {
        $verifier = User::where('name', $happening->verifier)->first();

        return collect([$happening->user1, $happening->user2, $verifier])
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->values();
    }
}

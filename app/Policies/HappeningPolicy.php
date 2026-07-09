<?php

namespace App\Policies;

use App\Models\Happening;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HappeningPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user): ?bool
    {
        if ($user->isBanned()) {
            return false;
        }

        return null;
    }

    public function create(): bool
    {
        return true;
    }

    public function update(User $user, Happening $happening): bool
    {
        $user1 = $happening->user1;

        if (! $user1 instanceof User) {
            return false;
        }

        if ($happening->isPast()) {
            return false;
        }

        if ($happening->isPresent() && $happening->isVerified()) {
            return false;
        }

        if ($user->getKey() === $user1->getKey()) {
            return true;
        }

        if ($user->getKey() === $happening->user2?->getKey()) {
            return true;
        }

        return $user->name === $happening->verifier;
    }

    public function delete(User $user, Happening $happening): bool
    {
        if ($user->isAdmin() && ! $happening->isPast()) {
            return true;
        }

        return $this->update($user, $happening);
    }

    public function verify(User $user, Happening $happening): bool
    {
        if ($happening->isPast()) {
            return false;
        }

        if ($happening->isVerified()) {
            return false;
        }

        return $user->name === $happening->verifier;
    }

    public function adminView(User $user, Happening $happening): bool
    {
        return $user->can('view_happenings', $happening->resource->resource_group->institution);
    }

    public function adminCreate(User $user, Institution $institution): bool
    {
        return $user->can('create_happenings', $institution);
    }

    public function adminUpdate(User $user, Happening $happening): bool
    {
        return $user->can('edit_happenings', $happening->resource->resource_group->institution);
    }

    public function adminDelete(User $user, Happening $happening): bool
    {
        return $user->can('delete_happenings', $happening->resource->resource_group->institution);
    }
}

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
    public function before(User $user)
    {
        if ($user->isBanned()) {
            return false;
        }
    }

    /**
     * Determine if a happening can be created by the user.
     *
     * @param User $user
     * @return bool
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * Determine if the given happening can be updated by the user.
     *
     * @param User $user
     * @param Happening $happening
     * @return bool
     */
    public function update(User $user, Happening $happening): bool
    {
        if ($happening->isPast()) {
            return false;
        }

        if ($happening->isPresent() && $happening->isVerified()) {
            return false;
        }

        if ($user->getKey() === $happening->user1->getKey()) {
            return true;
        }

        if ($user->getKey() === $happening->user2?->getKey()) {
            return true;
        }

        if ($user->name === $happening->verifier) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given happening can be deleted by the user.
     *
     * @param User $user
     * @param Happening $happening
     * @return bool
     */
    public function delete(User $user, Happening $happening): bool
    {
        return $this->update($user, $happening);
    }

    /**
     * Determine if the given happening can be verified by the user.
     *
     * @param User $user
     * @param Happening $happening
     * @return bool
     */
    public function verify(User $user, Happening $happening): bool
    {
        if ($happening->isPast()) {
            return false;
        }

        if ($happening->isVerified()) {
            return false;
        }

        if ($user->name === $happening->verifier) {
            return true;
        }

        return false;
    }

    public function adminView(User $user, Happening $happening)
    {
        if ($user->can('view_happenings', $happening->resource->resource_group->institution)) {
            return true;
        }
    }

    public function adminCreate(User $user, Institution $institution)
    {
        if ($user->can('create_happenings', $institution)) {
            return true;
        }
    }

    public function adminUpdate(User $user, Happening $happening)
    {
        if ($user->can('edit_happenings', $happening->resource->resource_group->institution)) {
            return true;
        }
    }

    public function adminDelete(User $user, Happening $happening)
    {
        if ($user->can('delete_happenings', $happening->resource->resource_group->institution)) {
            return true;
        }
    }
}

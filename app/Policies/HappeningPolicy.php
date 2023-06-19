<?php

namespace App\Policies;

use App\Models\Happening;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HappeningPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        return $user->getKey() === $happening->user1->getKey();
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
        return $user->getKey() === $happening->user1->getKey();
    }

    /**
     * Determine if the given happening can be confirmed by the user.
     *
     * @param User $user
     * @param Happening $happening
     * @return bool
     */
    public function confirm(User $user, Happening $happening): bool
    {
        return $user->name === $happening->confirmer;
    }

    /**
     * Determine if a happening can be created by the user.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return !$user->banned_at?->isValid();
    }
}

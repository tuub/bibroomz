<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPolicy
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

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function delete(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function edit(User $user, Institution $institution): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($institution->isAdmin($user)) {
            return true;
        }

        return false;
    }
}

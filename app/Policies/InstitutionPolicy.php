<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Institution $institution)
    {
        if ($user->can('view institutions')) {
            return true;
        }

        if ($user->can('view institution', $institution)) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($user->can('create institutions')) {
            return true;
        }
    }

    public function update(User $user, Institution $institution)
    {
        if ($user->can('edit institutions')) {
            return true;
        }

        if ($user->can('edit institution', $institution)) {
            return true;
        }
    }

    public function edit(User $user, Institution $institution)
    {
        return $this->update($user, $institution);
    }

    public function delete(User $user, Institution $institution)
    {
        if ($user->can('delete institutions')) {
            return true;
        }

        if ($user->can('delete institution', $institution)) {
            return true;
        }
    }
}

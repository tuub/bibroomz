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
        if ($user->can('view_institutions')) {
            return true;
        }

        if ($user->can('view_institution', $institution)) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($user->can('create_institutions')) {
            return true;
        }
    }

    public function update(User $user, Institution $institution)
    {
        if ($user->can('edit_institutions')) {
            return true;
        }

        if ($user->can('edit_institution', $institution)) {
            return true;
        }
    }

    public function edit(User $user, Institution $institution)
    {
        return $this->update($user, $institution);
    }

    public function delete(User $user, Institution $institution)
    {
        if ($user->can('delete_institutions')) {
            return true;
        }

        if ($user->can('delete_institution', $institution)) {
            return true;
        }
    }
}

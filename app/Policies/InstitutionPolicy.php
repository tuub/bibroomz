<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Institution $institution): bool
    {
        if ($user->can('view_institutions')) {
            return true;
        }

        return $user->can('view_institution', $institution);
    }

    public function create(User $user): bool
    {
        return $user->can('create_institutions');
    }

    public function update(User $user, Institution $institution): bool
    {
        if ($user->can('edit_institutions')) {
            return true;
        }

        return $user->can('edit_institution', $institution);
    }

    public function edit(User $user, Institution $institution): bool
    {
        return $this->update($user, $institution);
    }

    public function delete(User $user, Institution $institution): bool
    {
        if ($user->can('delete_institutions')) {
            return true;
        }

        return $user->can('delete_institution', $institution);
    }
}

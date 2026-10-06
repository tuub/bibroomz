<?php

namespace App\Policies;

use App\Enums\PermissionKey;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstitutionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Institution $institution): bool
    {
        if ($user->can(PermissionKey::ViewInstitutions->value)) {
            return true;
        }

        return $user->can(PermissionKey::ViewInstitution->value, $institution);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionKey::CreateInstitutions->value);
    }

    public function update(User $user, Institution $institution): bool
    {
        if ($user->can(PermissionKey::EditInstitutions->value)) {
            return true;
        }

        return $user->can(PermissionKey::EditInstitution->value, $institution);
    }

    public function edit(User $user, Institution $institution): bool
    {
        return $this->update($user, $institution);
    }

    public function delete(User $user, Institution $institution): bool
    {
        if ($user->can(PermissionKey::DeleteInstitutions->value)) {
            return true;
        }

        return $user->can(PermissionKey::DeleteInstitution->value, $institution);
    }
}

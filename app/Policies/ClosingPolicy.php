<?php

namespace App\Policies;

use App\Contracts\ClosingSubject;
use App\Enums\PermissionKey;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClosingPolicy
{
    use HandlesAuthorization;

    /**
     * @param  Institution|\App\Models\Resource  $closable
     */
    public function viewAny(User $user, ClosingSubject $closable): bool
    {
        return $user->can(PermissionKey::ViewClosings->value, $closable->institutionForClosings());
    }

    /**
     * @param  Institution|\App\Models\Resource  $closable
     */
    public function create(User $user, ClosingSubject $closable): bool
    {
        return $user->can(PermissionKey::CreateClosings->value, $closable->institutionForClosings());
    }

    public function update(User $user, Closing $closing): bool
    {
        return $user->can(PermissionKey::EditClosings->value, $closing->getInstitution());
    }

    public function edit(User $user, Closing $closing): bool
    {
        return $this->update($user, $closing);
    }

    public function delete(User $user, Closing $closing): bool
    {
        return $user->can(PermissionKey::DeleteClosings->value, $closing->getInstitution());
    }
}

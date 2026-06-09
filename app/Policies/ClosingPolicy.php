<?php

namespace App\Policies;

use App\Contracts\ClosingSubject;
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
        return $user->can('view_closings', $closable->institutionForClosings());
    }

    /**
     * @param  Institution|\App\Models\Resource  $closable
     */
    public function create(User $user, ClosingSubject $closable): bool
    {
        return $user->can('create_closings', $closable->institutionForClosings());
    }

    public function update(User $user, Closing $closing): bool
    {
        return $user->can('edit_closings', $closing->getInstitution());
    }

    public function edit(User $user, Closing $closing): bool
    {
        return $this->update($user, $closing);
    }

    public function delete(User $user, Closing $closing): bool
    {
        return $user->can('delete_closings', $closing->getInstitution());
    }
}

<?php

namespace App\Policies;

use App\Models\Closing;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class ClosingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Model $closable)
    {
        $institution = $closable instanceof Institution ? $closable : $closable->institution;

        if ($user->can('view_closings', $institution)) {
            return true;
        }
    }

    public function create(User $user, Model $closable)
    {
        $institution = $closable instanceof Institution ? $closable : $closable->institution;

        if ($user->can('create_closings', $institution)) {
            return true;
        }
    }

    public function update(User $user, Closing $closing)
    {
        $closable = $closing->closable;
        $institution = $closable instanceof Institution ? $closable : $closable->institution;

        if ($user->can('edit_closings', $institution)) {
            return true;
        }
    }

    public function edit(User $user, Closing $closing)
    {
        return $this->update($user, $closing);
    }

    public function delete(User $user, Closing $closing)
    {
        $closable = $closing->closable;
        $institution = $closable instanceof Institution ? $closable : $closable->institution;

        if ($user->can('delete:closings', $institution)) {
            return true;
        }
    }
}

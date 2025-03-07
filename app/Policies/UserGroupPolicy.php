<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserGroupPolicy
{
    use HandlesAuthorization;

    public function view(User $user, UserGroup $userGroup)
    {
        return $user->can('view_user_groups', $userGroup->institution);
    }

    public function create(User $user, Institution $institution)
    {
        if ($user->can('create_user_groups', $institution)) {
            return true;
        }
    }

    public function update(User $user, UserGroup $userGroup)
    {
        return $user->can('edit_user_groups', $userGroup->institution);
    }

    public function edit(User $user, UserGroup $userGroup)
    {
        return $this->update($user, $userGroup);
    }

    public function delete(User $user, UserGroup $userGroup)
    {
        if ($user->can('delete_user_groups', $userGroup->institution)) {
            return true;
        }
    }

    public function import(User $user, UserGroup $userGroup)
    {
        if ($user->can('edit_user_groups', $userGroup->institution)) {
            return true;
        }
    }
}

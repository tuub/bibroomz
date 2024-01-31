<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        if ($user->can('view_users')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if ($user->can('create_users')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, User $model)
    {
        if ($model->isAdmin() && !$user->can('edit_admin_users')) {
            return false;
        }

        if ($user->can('edit_users')) {
            return true;
        }
    }

    public function edit(User $user, User $model)
    {
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        if ($model->isAdmin() && !$user->can('delete_admin_users')) {
            return false;
        }

        if ($user->can('delete_users')) {
            return true;
        }
    }

    public function ban(User $user, User $model)
    {
        return $this->edit($user, $model);
    }

    public function unban(User $user, User $model)
    {
        return $this->edit($user, $model);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_users');
    }

    public function create(User $user): bool
    {
        return $user->can('create_users');
    }

    public function update(User $user, User $model): bool
    {
        if ($model->isAdmin() && ! $user->can('edit_admin_users')) {
            return false;
        }

        return $user->can('edit_users');
    }

    public function edit(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->isAdmin() && ! $user->can('delete_admin_users')) {
            return false;
        }

        return $user->can('delete_users');
    }

    public function ban(User $user, User $model): bool
    {
        return $this->edit($user, $model);
    }

    public function unban(User $user, User $model): bool
    {
        return $this->edit($user, $model);
    }

    public function impersonate(User $user, User $model): bool
    {
        if ($model->is($user)) {
            return false;
        }

        return $user->isAdmin();
    }
}

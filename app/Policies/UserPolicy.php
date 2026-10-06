<?php

namespace App\Policies;

use App\Enums\PermissionKey;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionKey::ViewUsers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionKey::CreateUsers->value);
    }

    public function update(User $user, User $model): bool
    {
        if ($model->isAdmin() && ! $user->can(PermissionKey::EditAdminUsers->value)) {
            return false;
        }

        return $user->can(PermissionKey::EditUsers->value);
    }

    public function edit(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->isAdmin() && ! $user->can(PermissionKey::DeleteAdminUsers->value)) {
            return false;
        }

        return $user->can(PermissionKey::DeleteUsers->value);
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

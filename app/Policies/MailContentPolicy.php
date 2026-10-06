<?php

namespace App\Policies;

use App\Enums\PermissionKey;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\User;

class MailContentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Institution $institution): bool
    {
        return $user->can(PermissionKey::ViewMails->value, $institution);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MailContent $mailContent): bool
    {
        return $user->can(PermissionKey::ViewMails->value, $mailContent->institution);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Institution $institution): bool
    {
        return $user->can(PermissionKey::CreateMails->value, $institution);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MailContent $mailContent): bool
    {
        return $user->can(PermissionKey::EditMails->value, $mailContent->institution);
    }

    public function edit(User $user, MailContent $mailContent): bool
    {
        return $this->update($user, $mailContent);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MailContent $mailContent): bool
    {
        return $user->can(PermissionKey::DeleteMails->value, $mailContent->institution);
    }
}

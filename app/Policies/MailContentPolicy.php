<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\User;

class MailContentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Institution $institution)
    {
        if ($user->can('view_mails', $institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MailContent $mailContent)
    {
        if ($user->can('view_mails', $mailContent->institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Institution $institution)
    {
        if ($user->can('create_mails', $institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MailContent $mailContent)
    {
        if ($user->can('edit_mails', $mailContent->institution)) {
            return true;
        }
    }

    public function edit(User $user, MailContent $mailContent)
    {
        return $this->update($user, $mailContent);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MailContent $mailContent)
    {
        if ($user->can('delete_mails', $mailContent->institution)) {
            return true;
        }
    }
}

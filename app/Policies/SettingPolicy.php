<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Institution $institution)
    {
        if ($user->can('view_settings', $institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Setting $setting)
    {
        if ($user->can('view_settings', $setting->institution)) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Setting $setting)
    {
        if ($user->can('edit_settings', $setting->institution)) {
            return true;
        }
    }

    public function edit(User $user, Setting $setting)
    {
        return $this->update($user, $setting);
    }
}

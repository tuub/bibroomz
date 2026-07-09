<?php

namespace App\Policies;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user, Institution|ResourceGroup $closable): bool
    {
        $institution = $closable->institutionForSettings();

        if ($user->can('view_settings', $institution)) {
            return true;
        }

        return $user->can('edit_settings', $institution);
    }

    public function editAny(User $user, Institution|ResourceGroup $settingable): bool
    {
        return $user->can('edit_settings', $settingable->institutionForSettings());
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->can('view_settings', $setting->getInstitution());
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->can('edit_settings', $setting->getInstitution());
    }

    public function edit(User $user, Setting $setting): bool
    {
        return $this->update($user, $setting);
    }
}

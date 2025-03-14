<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;

class ResourceGroupService
{
    public function deleteResourceGroup($id)
    {
        $rg = ResourceGroup::where('id', $id)->firstOrFail();
        $rg->deleteOrFail();

        return $rg;
    }

    public function getInstitutionsForUser(User $user)
    {
        return Institution::active()
            ->orderBy('title')
            ->with('user_groups')
            ->without('closings')
            ->get()
            ->filter
            ->isUserAbleToCreateResourceGroup($user)
            ->values();
    }

    public function getResourceGroupById($id)
    {
        return ResourceGroup::where('id', $id)->with('user_groups')->firstOrFail();
    }

    public function getResourceGroupsForUser(User $user)
    {
        return ResourceGroup::with(['institution'])
            ->orderBy('institution_id')
            ->orderBy('title')
            ->get()
            ->filter
            ->isViewableByUser($user);
    }

    public function storeResourceGroup(array $data)
    {
        $rg = ResourceGroup::create($data);
        $settings = Setting::getInitialValues();

        foreach ($settings['resource_group'] as $key => $value) {
            $setting = new Setting([
                'key' => $key,
                'value' => $value,
            ]);

            $rg->settings()->save($setting);
        }

        $rg->user_groups()->sync($data['user_groups']);

        return $rg;
    }

    public function updateResourceGroup($id, array $data)
    {
        $rg = ResourceGroup::where('id', $id)->firstOrFail();
        $rg->updateOrFail($data);

        $rg->user_groups()->sync($data['user_groups']);

        return $rg;
    }
}

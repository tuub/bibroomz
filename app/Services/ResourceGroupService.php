<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ResourceGroupService
{
    public function deleteResourceGroup(string $id): ResourceGroup
    {
        $rg = ResourceGroup::where('id', $id)->firstOrFail();
        $rg->deleteOrFail();

        return $rg;
    }

    /**
     * @return Collection<int, Institution>
     */
    public function getInstitutionsForUser(User $user): Collection
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

    public function getResourceGroupById(string $id): ResourceGroup
    {
        return ResourceGroup::where('id', $id)->with('user_groups')->firstOrFail();
    }

    /**
     * @return Collection<int, ResourceGroup>
     */
    public function getResourceGroupsForUser(User $user): Collection
    {
        return ResourceGroup::with(['institution'])
            ->orderBy('institution_id')
            ->orderBy('title')
            ->get()
            ->filter
            ->isViewableByUser($user);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function storeResourceGroup(array $data): ResourceGroup
    {
        $rg = ResourceGroup::create($this->extractAttributes($data));
        foreach (Setting::getInitialValues()['resource_group'] as $key => $value) {
            $setting = new Setting([
                'key' => $key,
                'value' => $value,
            ]);

            $rg->settings()->save($setting);
        }

        $rg->user_groups()->sync($this->extractUserGroups($data));

        return $rg;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateResourceGroup(string $id, array $data): ResourceGroup
    {
        $rg = ResourceGroup::where('id', $id)->firstOrFail();
        $rg->updateOrFail($this->extractAttributes($data));

        $rg->user_groups()->sync($this->extractUserGroups($data));

        return $rg;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function extractUserGroups(array $data): array
    {
        $userGroups = $data['user_groups'] ?? [];

        if (! is_array($userGroups)) {
            return [];
        }

        return array_values(array_filter($userGroups, 'is_string'));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractAttributes(array $data): array
    {
        $attributes = [];

        foreach (Arr::except($data, ['user_groups']) as $key => $value) {
            if (is_string($key)) {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }
}

<?php

namespace App\Services\Http;

use App\Models\ResourceGroup;

class ResourceGroupSettingsMapper
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function map(ResourceGroup $resourceGroup): array
    {
        $resourceGroup->loadMissing('institution.settings', 'settings');

        $settings = [];

        foreach ($resourceGroup->institution->settings as $setting) {
            $settings['institution'][$setting->key] = $setting->value;
        }

        foreach ($resourceGroup->settings as $setting) {
            $settings['resource_group'][$setting->key] = $setting->value;
        }

        return $settings;
    }
}

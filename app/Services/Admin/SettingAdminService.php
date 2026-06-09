<?php

namespace App\Services\Admin;

use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Services\AdminLoggingService;

class SettingAdminService
{
    public function __construct(private readonly AdminLoggingService $adminLoggingService) {}

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(Institution|ResourceGroup $settingable, string $settingableType): array
    {
        return [
            'settingable' => $settingable->withoutRelations(),
            'settingable_type' => $settingableType,
            'settings' => $settingable->settings()->orderBy('key')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(Setting $setting): array
    {
        $settingableClass = explode('\\', $setting->settingable_type);
        $settingableType = Utility::convertCamelCaseToSnakeCase(end($settingableClass));

        return [
            'setting' => $setting,
            'settingable' => $setting->settingable,
            'settingable_type' => $settingableType,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Setting $setting, array $attributes): Setting
    {
        $setting->update($attributes);

        $this->adminLoggingService->log('updated', $setting);

        return $setting;
    }
}

<?php

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;

class SettingableResolver
{
    public function resolve(string $settingableType, string $settingableId): Institution|ResourceGroup
    {
        return Setting::getSettingableModel($settingableType)->findOrFail($settingableId);
    }

    public function typeForModel(Institution|ResourceGroup $settingable): string
    {
        $class = class_basename($settingable);
        $snakeCase = preg_replace('/(?<!^)[A-Z]/', '_$0', $class);

        return strtolower(is_string($snakeCase) ? $snakeCase : $class);
    }
}

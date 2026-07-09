<?php

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Services\AdminLoggingService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SettingAdminService
{
    public function __construct(private readonly AdminLoggingService $adminLoggingService) {}

    /**
     * @return array{
     *   settingable: Institution|ResourceGroup,
     *   settingable_type: string,
     *   settings: array<int, array{id: string|null, key: string, value: string}>
     * }
     */
    public function getIndexData(Institution|ResourceGroup $settingable, string $settingableType): array
    {
        return [
            'settingable' => $settingable->withoutRelations(),
            'settingable_type' => $settingableType,
            'settings' => $this->buildDefinitionSettings(
                $settingableType,
                $settingable->settings()->get()->keyBy('key'),
            ),
        ];
    }

    /**
     * @return array{
     *   setting: array{id: string|null, key: string, value: string},
     *   settingable: Institution|ResourceGroup,
     *   settingable_type: string,
     *   input_type: string
     * }
     */
    public function getEditFormData(Institution|ResourceGroup $settingable, string $settingableType, string $key): array
    {
        abort_unless(Setting::hasDefinition($settingableType, $key), 404);

        $setting = $settingable->settings()->where('key', $key)->first();
        $value = $setting instanceof Setting
            ? $setting->value
            : Setting::getDefaultValue($settingableType, $key);

        return [
            'setting' => [
                'id' => $setting?->id,
                'key' => $key,
                'value' => $this->normalizeValue($value),
            ],
            'settingable' => $settingable->withoutRelations(),
            'settingable_type' => $settingableType,
            'input_type' => Setting::getInputType($settingableType, $key),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Institution|ResourceGroup $settingable, array $attributes): Setting
    {
        $key = is_string($attributes['key'] ?? null) ? $attributes['key'] : '';
        $setting = $settingable->settings()->firstOrNew(['key' => $key]);
        $action = $setting->exists ? 'updated' : 'created';
        $value = $attributes['value'] ?? null;

        $setting->value = is_string($value)
            ? $value
            : (is_scalar($value) ? (string) $value : null);

        if ($setting->exists) {
            $setting->save();
        } else {
            $settingable->settings()->save($setting);
        }

        $this->adminLoggingService->log($action, $setting);

        return $setting->refresh();
    }

    /**
     * @param  EloquentCollection<int, Setting>  $existingSettings
     * @return array<int, array{id: string|null, key: string, value: string}>
     */
    private function buildDefinitionSettings(string $settingableType, EloquentCollection $existingSettings): array
    {
        $settings = [];

        foreach (Setting::getDefinitionKeys($settingableType) as $key) {
            $existingSetting = $existingSettings->firstWhere('key', $key);
            $value = $existingSetting instanceof Setting
                ? $existingSetting->value
                : Setting::getDefaultValue($settingableType, $key);

            $settings[] = [
                'id' => $existingSetting?->id,
                'key' => $key,
                'value' => $this->normalizeValue($value),
            ];
        }

        return $settings;
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}

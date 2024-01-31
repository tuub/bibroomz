<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = Institution::get();
        $resource_groups = ResourceGroup::get();

        $settings = Setting::getInitialValues();

        foreach ($institutions as $institution) {
            foreach ($settings['institution'] as $key => $value) {
                if ($institution->settings()->where('key', $key)->exists()) {
                    continue;
                }

                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);

                $institution->settings()->save($setting);
            }
        }

        foreach ($resource_groups as $resource_group) {
            foreach ($settings['resource_group'] as $key => $value) {
                if ($resource_group->settings()->where('key', $key)->exists()) {
                    continue;
                }

                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);

                $resource_group->settings()->save($setting);
            }
        }
    }
}

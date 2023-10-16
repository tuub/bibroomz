<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::truncate();

        $institutions = Institution::get();
        $resource_groups = ResourceGroup::get();

        $settings = Setting::getInitialValues();

        foreach ($institutions as $institution) {
            foreach ($settings['institution'] as $key => $value) {
                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);
                $institution->settings()->save($setting);
            }
        }

        foreach ($resource_groups as $resource_group) {
            foreach ($settings['resource_group'] as $key => $value) {
                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);
                $resource_group->settings()->save($setting);
            }
        }
    }
}

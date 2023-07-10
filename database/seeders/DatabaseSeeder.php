<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Institution::factory(1)
            ->has(Resource::factory()->count(5))
            ->create();

        // Init settings
        $institutions = Institution::get();
        $settings = Setting::getInitialValues();

        foreach ($institutions as $institution) {
            foreach ($settings as $key => $value) {
                $setting = new Setting([
                    'key' => $key,
                    'value' => $value,
                ]);
                $institution->settings()->save($setting);
            }
        }

        $this->call([
            WeekDaySeeder::class,
        ]);
    }
}

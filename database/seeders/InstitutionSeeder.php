<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Add random example institutions with random example resources
        if (env('DB_SEED_EXAMPLE_INSTITUTIONS')) {
            Institution::factory(1)
                ->has(Resource::factory()->count(5))
                ->create();
        }

        // Add real example institutions with random example resources
        if (env('DB_SEED_TUB_INSTITUTIONS')) {
            $institutions = [];
            $institutions[] = Institution::create([
                'title' => 'Universitätsbibliothek der TU Berlin',
                'short_title' => 'UB',
                'slug' => 'ub',
                'location' => 'Fasanenstr. 88, 10623 Berlin ',
                'home_uri' => 'https://tu.berlin/ub',
                'logo_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/UB_Signet_2018_TU_schwarz.png',
                'teaser_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/teaser_zb.jpg',
                'is_active' => true,
            ]);

            $institutions[] = Institution::create([
                'title' => 'Die Bibliothek Wirtschaft & Management',
                'short_title' => 'DBWM',
                'slug' => 'dbwm',
                'location' => 'Hauptgebäude, Raum H5150b, Straße des 17. Juni 135, 10623 Berlin',
                'home_uri' => 'https://www.tu.berlin/wm/bibliothek',
                'logo_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/logo_dbwm.png',
                'teaser_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/teaser_dbwm.jpg',
                'is_active' => true,
            ]);

            foreach ($institutions as $institution) {
                $resources = Resource::factory()->count(5)->make();
                foreach ($resources as $resource) {
                    $institution->resources()->save($resource);
                }
            }
        }

        // Init institution settings
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
    }
}
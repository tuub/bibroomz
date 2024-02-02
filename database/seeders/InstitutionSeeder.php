<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    private $institutions = [];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createExampleInstitution();
        $this->createTUBInstitutions();

        $this->createExampleResources();
    }

    private function createExampleInstitution()
    {
        if (!config('roomz.database.is_seed_example_institution')) {
            return;
        }

        $this->institutions[] = Institution::factory()->create();
    }

    private function createExampleResources()
    {
        foreach ($this->institutions as $institution) {
            $resource_groups = ResourceGroup::factory()->count(2)->make();

            foreach ($resource_groups as $resource_group) {
                $institution->resource_groups()->save($resource_group);
                $resources = Resource::factory()->count(5)->make();

                foreach ($resources as $resource) {
                    $resource_group->resources()->save($resource);
                }
            }
        }
    }

    private function createTUBInstitutions()
    {
        if (!config('roomz.database.is_seed_tub_institutions')) {
            return;
        }

        if (Institution::where('slug', 'ub')->count() == 0) {
            $this->institutions[] = Institution::create([
                'title' => 'Universitätsbibliothek der TU Berlin',
                'short_title' => 'UB',
                'slug' => 'ub',
                'location' => 'Fasanenstr. 88, 10623 Berlin ',
                'home_uri' => 'https://tu.berlin/ub',
                'email' => 'info@ub.tu-berlin.de',
                'logo_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/UB_Signet_2018_TU_schwarz.png',
                'teaser_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/teaser_zb.jpg',
                'is_active' => true,
            ]);
        }

        if (Institution::where('slug', 'dbwm')->count() == 0) {
            $this->institutions[] = Institution::create([
                'title' => 'Die Bibliothek Wirtschaft & Management',
                'short_title' => 'DBWM',
                'slug' => 'dbwm',
                'location' => 'Hauptgebäude, Raum H5150b, Straße des 17. Juni 135, 10623 Berlin',
                'home_uri' => 'https://www.tu.berlin/wm/bibliothek',
                'email' => 'info@dbwm.tu-berlin.de',
                'logo_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/logo_dbwm.png',
                'teaser_uri' => 'https://services.ub.tu-berlin.de/platzbuchung/images/tuub/teaser_dbwm.jpg',
                'is_active' => true,
            ]);
        }
    }
}

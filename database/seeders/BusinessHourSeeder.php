<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\WeekDay;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $institutions = Institution::active()->get();
        $week_days = WeekDay::get();

        foreach ($institutions as $institution) {
            $resource_groups = $institution->resource_groups;

            foreach ($resource_groups as $resource_group) {
                $resources = $resource_group->resources;

                foreach ($resources as $resource) {
                    if ($resource->business_hours()->count() > 0) {
                        continue;
                    }

                    $business_hour = BusinessHour::create([
                        'resource_id' => $resource->id,
                        'start' => '09:00:00',
                        'end' => '23:00:00',
                    ]);

                    foreach ($week_days as $week_day) {
                        $business_hour->week_days()->attach($week_day->id);
                    }
                }
            }
        }
    }
}

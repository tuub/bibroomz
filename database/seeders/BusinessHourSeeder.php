<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\WeekDay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            $resources = $institution->resources;

            foreach ($resources as $resource) {
                $bh = BusinessHour::create([
                    'resource_id' => $resource->id,
                    'start' => '09:00:00',
                    'end' => '23:00:00',
                ]);
                foreach ($week_days as $week_day) {
                    $bh->week_days()->attach($week_day->id);
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\WeekDay;
use Illuminate\Database\Seeder;

class ActiveDaySeeder extends Seeder
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
            $institution->week_days()->sync($week_days);
        }
    }
}

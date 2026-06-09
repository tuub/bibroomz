<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\WeekDay;
use Illuminate\Database\Seeder;

class ActiveDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = Institution::active()->get();
        $week_days = WeekDay::get();

        foreach ($institutions as $institution) {
            if ($institution->week_days->count() > 0) {
                continue;
            }

            $institution->week_days()->sync($week_days);
        }
    }
}

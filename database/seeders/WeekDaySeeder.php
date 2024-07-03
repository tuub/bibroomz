<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeekDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $keys = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];

        foreach ($keys as $index => $key) {
            if (DB::table('week_days')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('week_days')->insert(['day_of_week' => $index, 'key' => $key]);
        }
    }
}

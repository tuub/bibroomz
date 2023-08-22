<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
        DB::table('week_days')->insert(['day_of_week' => 2, 'key' => 'tuesday']);
        DB::table('week_days')->insert(['day_of_week' => 3, 'key' => 'wednesday']);
        DB::table('week_days')->insert(['day_of_week' => 4, 'key' => 'thursday']);
        DB::table('week_days')->insert(['day_of_week' => 5, 'key' => 'friday']);
        DB::table('week_days')->insert(['day_of_week' => 6, 'key' => 'saturday']);
        DB::table('week_days')->insert(['day_of_week' => 0, 'key' => 'sunday']);
    }
}

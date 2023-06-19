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
        DB::table('week_days')->insert(['day_of_week' => 1, 'name' => 'MONDAY']);
        DB::table('week_days')->insert(['day_of_week' => 2, 'name' => 'TUESDAY']);
        DB::table('week_days')->insert(['day_of_week' => 3, 'name' => 'WEDNESDAY']);
        DB::table('week_days')->insert(['day_of_week' => 4, 'name' => 'THURSDAY']);
        DB::table('week_days')->insert(['day_of_week' => 5, 'name' => 'FRIDAY']);
        DB::table('week_days')->insert(['day_of_week' => 6, 'name' => 'SATURDAY']);
        DB::table('week_days')->insert(['day_of_week' => 0, 'name' => 'SUNDAY']);
    }
}

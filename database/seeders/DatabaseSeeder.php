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
        $this->call([
            WeekDaySeeder::class,
            InstitutionSeeder::class,
            BusinessHourSeeder::class,
            ActiveDaySeeder::class,
            PermissionSeeder::class,
        ]);
    }
}

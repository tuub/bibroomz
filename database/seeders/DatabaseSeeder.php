<?php

namespace Database\Seeders;

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
            SettingSeeder::class,
            BusinessHourSeeder::class,
            ActiveDaySeeder::class,
            PermissionSeeder::class,
            MailTypeSeeder::class,
            MailContentSeeder::class,
        ]);
    }
}

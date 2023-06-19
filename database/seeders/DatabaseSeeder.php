<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Institution;
use App\Models\Resource;
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
        Institution::factory(1)
            ->has(Resource::factory()->count(5))
            ->create();

        $this->call([
            WeekDaySeeder::class,
        ]);
    }
}

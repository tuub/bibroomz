<?php

namespace App\Console;

use App\Console\Commands\RemoveUnverifiedHappenings;
use App\Console\Commands\RemoveUsers;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(
            RemoveUnverifiedHappenings::class,
            [
                '--force',
                '--isolated',
            ],
        )->everyMinute();

        $schedule->command(
            RemoveUsers::class,
            [
                '--force',
                '--isolated',
            ]
        )->dailyAt('04:05');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

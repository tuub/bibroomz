<?php

namespace App\Console;

use App\Models\Happening;
use App\Console\Commands\RemoveUnverifiedHappeningsCommand;
use App\Console\Commands\RemoveUsersCommand;
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
            RemoveUnverifiedHappeningsCommand::class,
            [
                '--force',
                '--isolated',
            ],
        )->everyMinute();

        $schedule->command(
            RemoveUsersCommand::class,
            [
                '--force',
                '--isolated',
            ]
        )->dailyAt('04:05');

        $schedule->command(
            'model:prune',
            [
                '--model' => [Happening::class],
            ]
        )->dailyAt('04:35');

        $schedule->command(
            'telescope:prune',
        )->dailyAt('04:45');
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

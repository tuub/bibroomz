<?php

namespace App\Console\Commands;

use App\Events\HappeningDeleted;
use App\Events\HappeningsChanged;
use App\Events\UnverifiedHappeningRemovedByScheduler;
use App\Models\Happening;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Eloquent\Builder;

class RemoveUnverifiedHappenings extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'happenings:remove-unverified
                            {--M|minutes=60 : Remove unverified happenings older than this many minutes}
                            {--H|hours=0 : Remove unverified happenings older than this many hours}
                            {--D|days=0 : Remove unverified happenings older than this many days}
                            {--all : Remove all unverified happenings}
                            {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unverified happenings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        $hours = $this->option('hours');
        $minutes = $this->option('minutes');

        if ($this->option('all')) {
            $this->info('Removing all unverified happenings...');
            $query = Happening::where('is_verified', false);
        } elseif ($days > 0) {
            $this->info('Removing unverified happenings older than ' . $days . ' days...');
            $query = Happening::where('created_at', '<', now()->subDays($days))->where('is_verified', false);
        } elseif ($hours > 0) {
            $this->info('Removing unverified happenings older than ' . $hours . ' hours...');
            $query = Happening::where('created_at', '<', now()->subHours($hours))->where('is_verified', false);
        } elseif ($minutes > 0) {
            $this->info('Removing unverified happenings older than ' . $minutes . ' minutes...');
            $query = Happening::where('created_at', '<', now()->subMinutes($minutes))->where('is_verified', false);
        } else {
            $this->warn('Option arguments invalid.');
            $this->warn('Nothing to do.');

            return Command::INVALID;
        }

        $this->info('Found ' . $query->count() . ' happenings to remove.');

        if ($query->count() === 0) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
        }

        if ($this->output->isVerbose()){
            // print happenings to be removed
            $this->prettyPrintHappenings($query);
        }

        if (!$this->option('force') && !$this->confirm('Do you want to proceed?')) {
            $this->info('Nothing to do.');
            return Command::INVALID;
        }

        $query->lazy()->each(function (Happening $happening) {
            $happening->delete();
            $happening->broadcast(UnverifiedHappeningRemovedByScheduler::class);
        });

        $this->info('Done.');
        return Command::SUCCESS;
    }

    private function prettyPrintHappenings(Builder $query): void
    {
        $query->lazy()->each(function (Happening $happening) {
            $this->line($happening->toJson(JSON_PRETTY_PRINT));
        });
    }
}

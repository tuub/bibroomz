<?php

namespace App\Console\Commands;

use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Happening;
use App\Models\Institution;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Eloquent\Builder;

class RemoveUnverifiedHappeningsCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'happenings:remove-unverified
                            {--M|minutes= : Remove unverified happenings older than this many minutes}
                            {--H|hours= : Remove unverified happenings older than this many hours}
                            {--D|days= : Remove unverified happenings older than this many days}
                            {--institution= : Remove unverified happenings from this institution}
                            {--settings=true : Get time interval from institution settings}
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
        $minutes = $this->option('minutes');
        $hours = $this->option('hours');
        $days = $this->option('days');

        $institution = Institution::find($this->option('institution'));

        /** @var Builder */
        $query = Happening::where('is_verified', false);

        // restrict query to institution
        if ($institution) {
            $this->info('Restricting to institution ' . $institution->id . '...');

            // FIXME: resource groups
            $query->whereRelation('resource', 'institution_id', $institution->id);
        }

        if (isset($minutes) || isset($hours) || isset($days) || !$this->option('settings')) {
            $time = $this->getIntervalByValues($minutes, $hours, $days);

            $query->where('created_at', '<', $time);

            $this->info('Removing unverified happenings created more than '
                . $time->locale('en')->diffForHumans(short: true, parts: 3) . '...');
        } elseif ($institution) {
            $time = $this->getIntervalByInstitution($institution);

            $query->where('created_at', '<', $time);

            $this->info('Removing unverified happenings created more than '
                . $time->locale('en')->diffForHumans(short: true, parts: 3) . '...');
        } else {
            $query->where(function (Builder $query) {
                $institutions = Institution::all();
                $institution = $institutions->shift();

                $time = $this->getIntervalByInstitution($institution);

                $query->where(fn (Builder $q) => $q
                    ->whereRelation('resource', 'institution_id', $institution->id)
                    ->where('created_at', '<', $time));

                $this->info('Removing unverified happenings created more than '
                    . $time->locale('en')->diffForHumans(short: true, parts: 3)
                    . ' for institution ' . $institution->id . '...');

                foreach ($institutions as $institution) {
                    $time = $this->getIntervalByInstitution($institution);

                    $query->orWhere(fn (Builder $q) => $q
                        ->whereRelation('resource', 'institution_id', $institution->id)
                        ->where('created_at', '<', $time));

                    $this->info('Removing unverified happenings created more than '
                        . $time->locale('en')->diffForHumans(short: true, parts: 3)
                        . ' for institution ' . $institution->id . '...');
                }
            });
        }

        // print count
        $this->info('Found ' . $query->count() . ' happenings to remove.');

        // abort if no happenings to remove
        if ($query->count() === 0) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
        }

        if ($this->output->isVerbose()) {
            // print happenings to be removed
            $this->prettyPrintHappenings($query);
        }

        // ask for confirmation
        if (!$this->option('force') && !$this->confirm('Do you want to proceed?')) {
            $this->info('Nothing to do.');
            return Command::INVALID;
        }

        // remove happenings and broadcast
        $query->lazy()->each(function (Happening $happening) {
            $happening->delete();
            $happening->broadcast(UnverifiedHappeningRemovedBySchedulerEvent::class);
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

    private function getIntervalByInstitution(Institution $institution)
    {
        $time = now();

        $setting = $institution->settings->where('key', 'cleanup_interval')->first()?->value ??
            config('roomz.default.cleanup_interval');

        foreach (explode(':', $setting) as $index => $interval) {
            $interval = (int) $interval ?? 0;

            if ($index === 0) {
                $time->subDays($interval);
            }

            if ($index === 1) {
                $time->subHours($interval);
            }

            if ($index === 2) {
                $time->subMinutes($interval);
            }
        }

        return $time;
    }

    private function getIntervalByValues(int|null $minutes, int|null $hours, int|null $days)
    {
        $time = now();

        if ($minutes) {
            $time->subMinutes($minutes);
        }

        if ($hours) {
            $time->subHours($hours);
        }

        if ($days) {
            $time->subDays($days);
        }

        return $time;
    }
}

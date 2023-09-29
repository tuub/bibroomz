<?php

namespace App\Console\Commands;

use App\Events\UnverifiedHappeningRemovedBySchedulerEvent;
use App\Models\Happening;
use App\Models\Institution;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

        // get institution by id or slug
        $institution = Institution::find($this->option('institution'))
            ?? Institution::where('slug', $this->option('institution'))->first();

        /** @var Builder */
        $query = Happening::where('is_verified', false);

        // restrict query to institution
        if ($institution) {
            $this->info('Restricting to institution ' . $institution->id . '...');

            $query = $this->restrictQueryToInstitution($query, $institution);
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
                $this->applySettingsPerInstitution($query, $institutions);
            });
        }

        if ($this->output->isVerbose()) {
            // print sql with bindings
            $this->line($query->toRawSql());

            // print happenings to be removed
            $this->prettyPrintHappenings($query);
        }

        // print count
        $this->info('Found ' . $query->count() . ' happenings to remove.');

        // abort if no happenings to remove
        if ($query->count() === 0) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
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

    private function restrictQueryToInstitution(Builder $query, Institution $institution): Builder
    {
        return $query->whereHas(
            'resource',
            fn (Builder $q) => $q->whereHas(
                'resource_group',
                fn (Builder $q) => $q->where('institution_id', $institution->id)
            )
        );
    }

    private function applySettingsPerInstitution(Builder $query, Collection $institutions): Builder
    {
        // get first institution
        $institution = $institutions->shift();
        $time = $this->getIntervalByInstitution($institution);

        // use where instead of orWhere for the first institution
        $query->where(fn (Builder $q) => $this->restrictQueryToInstitution($q, $institution)
            ->where('created_at', '<', $time));

        $this->info('Removing unverified happenings created more than '
            . $time->locale('en')->diffForHumans(short: true, parts: 3)
            . ' for institution ' . $institution->id . '...');

        // loop over remaining institutions
        foreach ($institutions as $institution) {
            $time = $this->getIntervalByInstitution($institution);

            // use orWhere instead of where
            $query->orWhere(fn (Builder $q) => $this->restrictQueryToInstitution($q, $institution)
                ->where('created_at', '<', $time));

            $this->info('Removing unverified happenings created more than '
                . $time->locale('en')->diffForHumans(short: true, parts: 3)
                . ' for institution ' . $institution->id . '...');
        }

        return $query;
    }
}

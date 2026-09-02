<?php

namespace App\Console\Commands;

use App\Models\Happening;
use App\Models\Institution;
use App\Services\Console\CleanupIntervalResolver;
use App\Services\Console\RemoveUnverifiedHappeningsAction;
use App\Services\Console\RemoveUnverifiedHappeningsQueryBuilder;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Eloquent\Builder;

#[Description('Remove unverified happenings')]
#[Signature('roomz:remove-unverified-happenings
                            {--M|minutes= : Remove unverified happenings older than this many minutes}
                            {--H|hours= : Remove unverified happenings older than this many hours}
                            {--D|days= : Remove unverified happenings older than this many days}
                            {--institution= : Remove unverified happenings from this institution}
                            {--settings=true : Get time interval from institution settings}
                            {--force : Do not ask for confirmation}')]
class RemoveUnverifiedHappeningsCommand extends Command implements Isolatable
{
    public function __construct(
        private readonly CleanupIntervalResolver $cleanupIntervalResolver,
        private readonly RemoveUnverifiedHappeningsQueryBuilder $queryBuilder,
        private readonly RemoveUnverifiedHappeningsAction $removeUnverifiedHappeningsAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = $this->option('minutes');
        $hours = $this->option('hours');
        $days = $this->option('days');

        $institution = $this->queryBuilder->resolveInstitution($this->option('institution'));
        $query = $this->queryBuilder->baseQuery();

        if ($institution instanceof Institution) {
            $this->info('Restricting to institution '.$institution->id.'...');
            $query = $this->queryBuilder->restrictToInstitution($query, $institution);
        }

        if (isset($minutes) || isset($hours) || isset($days) || ! $this->option('settings')) {
            $time = $this->cleanupIntervalResolver->fromValues($minutes, $hours, $days);
            $query->where('created_at', '<', $time);
            $time->locale('en');

            $this->info('Removing unverified happenings created more than '
                .$time->diffForHumans(short: true, parts: 3).'...');
        } elseif ($institution instanceof Institution) {
            $time = $this->cleanupIntervalResolver->fromInstitution($institution);
            $query->where('created_at', '<', $time);
            $time->locale('en');

            $this->info('Removing unverified happenings created more than '
                .$time->diffForHumans(short: true, parts: 3).'...');
        } else {
            $query->where(function (Builder $builder): void {
                $this->queryBuilder->applySettingsPerInstitution(
                    $builder,
                    Institution::all(),
                    function (Institution $institution, Carbon $time): void {
                        $time->locale('en');
                        $this->info('Removing unverified happenings created more than '
                            .$time->diffForHumans(short: true, parts: 3)
                            .' for institution '.$institution->id.'...');
                    },
                );
            });
        }

        if ($this->output->isVerbose()) {
            // print sql with bindings
            $this->line($query->toRawSql());

            // print happenings to be removed
            $this->prettyPrintHappenings($query);
        }

        // print count
        $this->info('Found '.$query->count().' happenings to remove.');

        // abort if no happenings to remove
        if ($query->count() === 0) {
            $this->info('Nothing to do.');

            return Command::SUCCESS;
        }

        // ask for confirmation
        if (! $this->option('force') && ! $this->confirm('Do you want to proceed?')) {
            $this->info('Nothing to do.');

            return Command::INVALID;
        }

        $this->removeUnverifiedHappeningsAction->execute($query);

        $this->info('Done.');

        return Command::SUCCESS;
    }

    /**
     * @param  Builder<Happening>  $query
     */
    private function prettyPrintHappenings(Builder $query): void
    {
        $query->each(function (Happening $happening): void {
            $this->line($happening->toJson(JSON_PRETTY_PRINT));
        });
    }
}

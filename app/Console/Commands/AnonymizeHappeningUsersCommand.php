<?php

namespace App\Console\Commands;

use App\Models\Happening;
use App\Services\Console\AnonymizeHappeningUsersAction;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Eloquent\Builder;

class AnonymizeHappeningUsersCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomz:anonymize-happening-users
        {--D|days= : Anonymize happenings older than this many days}
        {--dry-run : Do not remove users}
        {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymize past happenings';

    public function __construct(private AnonymizeHappeningUsersAction $anonymizeHappeningUsersAction)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = $this->anonymizeHappeningUsersAction->query($this->resolveDays());

        if ($this->output->isVerbose()) {
            $this->line($query->toRawSql());
            $this->prettyPrintHappenings($query);
        }

        $this->info('Found ' . $query->count() . ' happenings to anonymize.');

        if ($query->count() === 0) {
            $this->info('Nothing to do.');

            return Command::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Nothing to do.');

            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Do you want to proceed?')) {
            $this->info('Nothing to do.');

            return Command::INVALID;
        }

        $this->anonymizeHappeningUsersAction->execute($query);

        $this->info('Done.');

        return Command::SUCCESS;
    }

    private function resolveDays(): int
    {
        $optionDays = $this->parseDaysValue($this->option('days'));

        if ($optionDays !== null) {
            return $optionDays;
        }

        $configDays = $this->parseDaysValue(config('roomz.happenings.anonymize_days'));

        if ($configDays !== null) {
            return $configDays;
        }

        return 30;
    }

    private function parseDaysValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value) || $value === '' || ! preg_match('/^\d+$/', $value)) {
            return null;
        }

        $days = (int) $value;

        return $days > 0 ? $days : null;
    }

    /**
     * @param Builder<Happening> $query
     */
    private function prettyPrintHappenings(Builder $query): void
    {
        $query->each(function (Happening $happening): void {
            $this->line($happening->toJson(JSON_PRETTY_PRINT));
        });
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Console\RemoveUsersAction;
use App\Services\Console\RemoveUsersQueryBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

#[Description('Remove users with no recent happenings')]
#[Signature('roomz:remove-users
        {--D|days= : Remove users with no happenings more recent than this many days}
        {--dry-run : Do not remove users}
        {--force : Do not ask for confirmation}')]
class RemoveUsersCommand extends Command implements Isolatable
{
    public function __construct(
        private readonly RemoveUsersQueryBuilder $queryBuilder,
        private readonly RemoveUsersAction $removeUsersAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->resolveDays();

        $this->info("Removing users with no happenings more recent than $days days ago.");

        $query = $this->queryBuilder->build($days);
        $users = $this->queryBuilder->candidates($days);

        $this->info('Found '.$users->count().' users to remove.');

        if ($users->count() === 0) {
            $this->info('Nothing to do.');

            return Command::SUCCESS;
        }

        if ($this->output->isVerbose()) {
            $this->line($query->toRawSql());

            $users->each(function (User $user): void {
                $this->line($user->toJson(JSON_PRETTY_PRINT));
            });
        }

        if ($this->option('dry-run')) {
            $this->info('Nothing to do.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Do you want to proceed?')) {
            $this->info('Nothing to do.');

            return Command::INVALID;
        }

        $this->removeUsersAction->execute($users);

        $this->info('Done.');

        return Command::SUCCESS;
    }

    private function resolveDays(): int
    {
        $optionValue = $this->option('days');

        if (is_string($optionValue) && $optionValue !== '') {
            return (int) $optionValue;
        }

        $configValue = config('roomz.user.cleanup_days');

        if (is_int($configValue)) {
            return $configValue;
        }

        if (is_string($configValue) && $configValue !== '') {
            return (int) $configValue;
        }

        return 0;
    }
}

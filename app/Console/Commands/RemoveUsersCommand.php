<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Query\Builder;

class RemoveUsersCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomz:remove-users
        {--D|days= : Remove users with no happenings more recent than this many days}
        {--dry-run : Do not remove users}
        {--force : Do not ask for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove users with no recent happenings';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = config('roomz.user.cleanup_days');

        if ($this->option('days')) {
            $days = $this->option('days');
        }

        $this->info("Removing users with no happenings more recent than $days days ago.");

        /** @var Builder $query */
        $query = User::query()
            ->where('is_admin', '=', false);

        // do not delete privileged users
        $query->whereNotExists(function (Builder $query) {
            $query->from('institution_user_role')
                ->whereColumn('user_id', 'users.id');
        });

        // find users with no recent happenings
        $query->whereNotExists(function (Builder $query) use ($days) {
            $query->from('happenings')
                ->where('end', '>', now()->subDays($days))
                ->where(function (Builder $query) {
                    $query->whereColumn('user_id_01', 'users.id')
                        ->orWhereColumn('user_id_02', 'users.id');
                });
        });

        $users = $query->get()->filter(fn (User $user) => !$user->isLoggedIn());

        // print count
        $this->info('Found ' . $users->count() . ' users to remove.');

        // abort if no users to remove
        if ($users->count() === 0) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
        }

        // print users to be removed
        if ($this->output->isVerbose()) {
            $this->line($query->toRawSql());

            $users->each(function (User $user) {
                $this->line($user->toJson(JSON_PRETTY_PRINT));
            });
        }

        // abort if dry run
        if ($this->option('dry-run')) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
        }

        // ask for confirmation
        if (!$this->option('force') && !$this->confirm('Do you want to proceed?')) {
            $this->info('Nothing to do.');
            return Command::INVALID;
        }

        // remove users
        $users->each(function (User $user) {
            $user->delete();
        });

        $this->info('Done.');
        return Command::SUCCESS;
    }
}

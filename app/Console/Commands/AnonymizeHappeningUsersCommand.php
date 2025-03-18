<?php

namespace App\Console\Commands;

use App\Models\Happening;
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');

        if (!isset($days)) {
            $days = config('roomz.happenings.anonymize_days');
        }

        /** @var Builder */
        $query = Happening::withTrashed()
            ->where('end', '<=', now()->subDays($days))
            ->where(function (Builder $query) {
                return $query
                    ->where('user_id_01', '!=', null)
                    ->orWhere('user_id_02', '!=', null)
                    ->orWhere('verifier', '!=', null);
            });

        if ($this->output->isVerbose()) {
            // print sql with bindings
            $this->line($query->toRawSql());

            // print happenings to be removed
            $this->prettyPrintHappenings($query);
        }

        // print count
        $this->info('Found ' . $query->count() . ' happenings to anonymize.');

        // abort if no happenings to remove
        if ($query->count() === 0) {
            $this->info('Nothing to do.');
            return Command::SUCCESS;
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

        // anonymize happenings
        $query->lazy()->each(function (Happening $happening) {
            $happening->user1()->dissociate();
            $happening->user2()->dissociate();
            $happening->verifier = null;
            $happening->save();
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

<?php

namespace App\Console\Commands;

use App\Services\Console\ResourceGroupRestrictionInputCollector;
use App\Services\Console\RestrictResourceGroupAction;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;

class RestrictResourceGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomz:restrict-resource-group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restrict a resource group to users of one or more user groups';

    public function __construct(
        private ResourceGroupRestrictionInputCollector $inputCollector,
        private RestrictResourceGroupAction $restrictResourceGroupAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app()->setLocale('en');

        $input = $this->inputCollector->collect();

        if (!$this->confirm('Are you sure you want to restrict this resource group to the selected user groups?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        $this->restrictResourceGroupAction->execute(
            $input['resource_group'],
            $input['user_group_ids'],
        );

        $this->info('Success.');

        return Command::SUCCESS;
    }
}

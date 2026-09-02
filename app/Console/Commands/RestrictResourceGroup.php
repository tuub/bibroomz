<?php

namespace App\Console\Commands;

use App\Services\Console\ResourceGroupRestrictionInputCollector;
use App\Services\Console\RestrictResourceGroupAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;

#[Description('Restrict a resource group to users of one or more user groups')]
#[Signature('roomz:restrict-resource-group')]
class RestrictResourceGroup extends Command
{
    public function __construct(
        private readonly ResourceGroupRestrictionInputCollector $inputCollector,
        private readonly RestrictResourceGroupAction $restrictResourceGroupAction,
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

        if (! $this->confirm('Are you sure you want to restrict this resource group to the selected user groups?')) {
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

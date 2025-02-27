<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app()->setLocale('en');

        $institution_id = select(
            'Select an institution',
            Institution::orderBy('title')->pluck('title', 'id'),
        );

        $institution = Institution::findOrFail($institution_id);

        $resource_group_id = select(
            label: 'Select a resource group',
            options: $institution->resource_groups->pluck('title', 'id'),
        );

        $resource_group = ResourceGroup::findOrFail($resource_group_id);

        $user_groups = multiselect(
            label: 'Select some user groups to restrict this resource group to',
            options: $institution->user_groups->pluck('title', 'id'),
            default: $resource_group->user_groups->pluck('id'),
        );

        if (!$this->confirm('Are you sure you want to restrict this resource group to the selected user groups?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        $resource_group->user_groups()->sync($user_groups);

        info('Success.');
    }
}

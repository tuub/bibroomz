<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Models\ResourceGroup;

class RestrictResourceGroupAction
{
    /**
     * @param  array<int, string>  $userGroupIds
     */
    public function execute(ResourceGroup $resourceGroup, array $userGroupIds): void
    {
        $resourceGroup->user_groups()->sync($userGroupIds);
    }
}

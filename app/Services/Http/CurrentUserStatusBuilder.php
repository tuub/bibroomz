<?php

declare(strict_types=1);

namespace App\Services\Http;

use App\Models\ResourceGroup;
use App\Models\User;

class CurrentUserStatusBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $user->loadMissing('user_groups');

        $allowedResourceGroups = ResourceGroup::query()
            ->with('user_groups')
            ->get()
            ->filter(fn (ResourceGroup $resourceGroup): bool => $resourceGroup->isAllowedUser($user))
            ->pluck('id')
            ->values();

        return [
            'isAdmin' => $user->isAdmin(),
            'user' => $user->only(['id', 'name', 'email']),
            'permissions' => $user->getPermissions(),
            'allowedResourceGroups' => $allowedResourceGroups,
        ];
    }
}

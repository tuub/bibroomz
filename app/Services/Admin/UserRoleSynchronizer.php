<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\User;

class UserRoleSynchronizer
{
    /**
     * @param  array<int, array{role_id: string, institution_id: string}>  $roles
     */
    public function sync(User $user, array $roles, User $actor): void
    {
        $editableInstitutionIds = Institution::query()
            ->get()
            ->filter(fn (Institution $institution): bool => $institution->isEditableByUser($actor))
            ->modelKeys();

        $user->roles()->wherePivotIn('institution_id', $editableInstitutionIds)->detach();

        foreach ($roles as $role) {
            $user->roles()->attach($role['role_id'], ['institution_id' => $role['institution_id']]);
        }
    }
}

<?php

namespace Tests\Concerns;

use App\Library\Utility;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

trait InteractsWithPermissions
{
    protected function seedPermissions(): void
    {
        $this->seed(PermissionSeeder::class);
    }

    protected function grantPermission(User $user, Institution $institution, string $permissionKey): Role
    {
        $permission = Permission::firstWhere('key', $permissionKey);

        $role = Role::create([
            'name' => Utility::getTranslatable($permissionKey),
        ]);

        $role->permissions()->attach($permission);
        $user->roles()->attach($role->id, ['institution_id' => $institution->id]);
        $user->unsetRelation('roles');
        $user->unsetRelation('institutions');

        return $role;
    }
}

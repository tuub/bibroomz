<?php

namespace Tests\Concerns;

use App\Enums\PermissionKey;
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

    protected function grantPermission(
        User $user,
        Institution $institution,
        PermissionKey|string $permissionKey,
    ): Role {
        $permissionKey = $permissionKey instanceof PermissionKey ? $permissionKey->value : $permissionKey;
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

    protected function assertScopedGetAuthorizationMatrix(
        string $uri,
        Institution $targetInstitution,
        PermissionKey $requiredPermission,
        PermissionKey $unrelatedPermission = PermissionKey::ViewUsers,
    ): void {
        $this->get($uri)->assertRedirect();

        $unrelatedActor = User::factory()->create();
        $this->grantPermission($unrelatedActor, $targetInstitution, $unrelatedPermission);
        $this->actingAs($unrelatedActor)->get($uri)->assertForbidden();

        $otherInstitution = Institution::factory()->create();
        $crossInstitutionActor = User::factory()->create();
        $this->grantPermission($crossInstitutionActor, $otherInstitution, $requiredPermission);
        $this->actingAs($crossInstitutionActor)->get($uri)->assertForbidden();

        $allowedActor = User::factory()->create();
        $this->grantPermission($allowedActor, $targetInstitution, $requiredPermission);
        $this->actingAs($allowedActor)->get($uri)->assertSuccessful();
    }

    protected function assertGlobalGetAuthorizationMatrix(
        string $uri,
        PermissionKey $requiredPermission,
        PermissionKey $unrelatedPermission = PermissionKey::ViewUsers,
    ): void {
        $this->get($uri)->assertRedirect();

        $institution = Institution::factory()->create();
        $unrelatedActor = User::factory()->create();
        $this->grantPermission($unrelatedActor, $institution, $unrelatedPermission);
        $this->actingAs($unrelatedActor)->get($uri)->assertForbidden();

        $allowedActor = User::factory()->create();
        $this->grantPermission($allowedActor, $institution, $requiredPermission);
        $this->actingAs($allowedActor)->get($uri)->assertSuccessful();
    }
}

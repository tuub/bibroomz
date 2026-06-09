<?php

namespace App\Services\Admin;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Services\AdminLoggingService;

class RoleAdminService
{
    public function __construct(private readonly AdminLoggingService $adminLoggingService) {}

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(): array
    {
        return [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormData(?Role $role = null): array
    {
        $data = [
            'permissions' => Permission::query()->orderBy('name')->get(),
            'groups' => PermissionGroup::query()->orderBy('name')->get(),
            'languages' => config('app.supported_locales'),
        ];

        if ($role instanceof Role) {
            $data['role'] = $role->load('permissions');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $permissionIds
     */
    public function store(array $attributes, array $permissionIds): Role
    {
        $role = Role::create($attributes);
        $role->permissions()->sync($permissionIds);

        $this->adminLoggingService->log('created', $role);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $permissionIds
     */
    public function update(Role $role, array $attributes, array $permissionIds): Role
    {
        $role->update($attributes);
        $role->permissions()->sync($permissionIds);

        $this->adminLoggingService->log('updated', $role);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();

        $this->adminLoggingService->log('deleted', $role);
    }
}

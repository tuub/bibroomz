<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Services\Admin\RoleAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(RoleAdminService::class);

uses(RefreshDatabase::class);

test('getIndexData returns roles key', function (): void {
    $service = app(RoleAdminService::class);
    $data = $service->getIndexData();

    expect($data)->toHaveKey('roles');
});

test('store creates a role', function (): void {
    $service = app(RoleAdminService::class);
    $role = $service->store(['name' => ['en' => 'Editor'], 'description' => ['en' => 'Edit rights']], []);

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->id)->not->toBeNull();
});

test('delete removes the role', function (): void {
    $role = Role::create(['name' => ['en' => 'ToDelete'], 'description' => ['en' => 'Desc']]);
    $id = $role->id;

    $service = app(RoleAdminService::class);
    $service->delete($role);

    expect(Role::find($id))->toBeNull();
});

test('getFormData without role returns permissions groups and languages', function (): void {
    $service = app(RoleAdminService::class);
    $data = $service->getFormData();

    expect($data)->toHaveKey('permissions')
        ->and($data)->toHaveKey('groups')
        ->and($data)->toHaveKey('languages')
        ->and($data)->not->toHaveKey('role');
});

test('getFormData with role returns role key with permissions loaded', function (): void {
    $role = Role::create(['name' => ['en' => 'Viewer'], 'description' => ['en' => 'View only']]);
    $service = app(RoleAdminService::class);

    $data = $service->getFormData($role);

    expect($data)->toHaveKey('role')
        ->and($data['role'])->toBeInstanceOf(Role::class)
        ->and($data['role']->relationLoaded('permissions'))->toBeTrue();
});

test('store syncs permission ids to the role', function (): void {
    $permission = Permission::create([
        'name' => ['en' => 'edit'],
        'description' => ['en' => 'Edit perm'],
        'key' => 'edit_'.uniqid(),
    ]);
    $service = app(RoleAdminService::class);

    $role = $service->store(
        ['name' => ['en' => 'Editor'], 'description' => ['en' => 'Edit rights']],
        [$permission->id]
    );

    expect($role->permissions->pluck('id')->toArray())->toContain($permission->id);
});

test('update modifies role attributes', function (): void {
    $role = Role::create(['name' => ['en' => 'Old Name'], 'description' => ['en' => 'Desc']]);
    $service = app(RoleAdminService::class);

    $updated = $service->update($role, ['name' => ['en' => 'New Name'], 'description' => ['en' => 'Desc']], []);

    expect($updated->getTranslation('name', 'en'))->toBe('New Name');
});

test('update syncs permissions on role update', function (): void {
    $role = Role::create(['name' => ['en' => 'Role'], 'description' => ['en' => 'Desc']]);
    $permission = Permission::create([
        'name' => ['en' => 'write'],
        'description' => ['en' => 'Write perm'],
        'key' => 'write_'.uniqid(),
    ]);
    $service = app(RoleAdminService::class);

    $updated = $service->update($role, ['name' => ['en' => 'Role'], 'description' => ['en' => 'Desc']], [$permission->id]);

    expect($updated->permissions->pluck('id')->toArray())->toContain($permission->id);
});

<?php

declare(strict_types=1);

use App\Library\Utility;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(Permission::class);

uses(RefreshDatabase::class);

test('permission can be retrieved from seeded data by key', function (): void {
    $this->seed(PermissionSeeder::class);

    $permission = Permission::firstWhere('key', 'view_mails');

    expect($permission)->not->toBeNull()
        ->and($permission?->key)->toBe('view_mails');
});

test('permission name stores translations', function (): void {
    $this->seed(PermissionSeeder::class);

    $permission = Permission::firstWhere('key', 'view_mails');

    expect($permission)->not->toBeNull();
    expect($permission?->getTranslations('name'))->toBeArray();
});

test('permission roles relationship returns BelongsToMany', function (): void {
    $permission = new Permission;

    expect($permission->roles())->toBeInstanceOf(BelongsToMany::class);
});

test('permission roles relationship resolves when attached to a role', function (): void {
    $this->seed(PermissionSeeder::class);

    $permission = Permission::firstWhere('key', 'view_mails');
    $role = Role::create([
        'name' => Utility::getTranslatable('Test role'),
        'description' => Utility::getTranslatable('Desc'),
    ]);

    $role->permissions()->attach($permission);

    expect($permission?->roles()->count())->toBeGreaterThan(0)
        ->and($permission?->roles()->pluck('roles.id')->all())->toContain($role->id);
});

test('permission group relationship returns BelongsTo', function (): void {
    $permission = new Permission;

    expect($permission->group())->toBeInstanceOf(BelongsTo::class);
});

test('permission description field stores translations', function (): void {
    $this->seed(PermissionSeeder::class);

    $permission = Permission::firstWhere('key', 'view_mails');

    expect($permission)->not->toBeNull();
    expect($permission?->getTranslations('description'))->toBeArray();
});

test('permission can be force created with translatable fields', function (): void {
    $permission = Permission::forceCreate([
        'key' => 'custom_perm',
        'name' => Utility::getTranslatable('Custom Permission'),
        'description' => Utility::getTranslatable('Custom description'),
    ]);

    expect($permission->key)->toBe('custom_perm')
        ->and($permission->getTranslations('name'))->toBeArray();
});

test('permission group relationship resolves when permission group is attached', function (): void {
    $permGroup = PermissionGroup::forceCreate([
        'key' => 'test-group',
        'name' => Utility::getTranslatable('Test Group'),
        'description' => Utility::getTranslatable('Group desc'),
    ]);

    $permission = Permission::forceCreate([
        'key' => 'grouped_perm',
        'name' => Utility::getTranslatable('Grouped Permission'),
        'description' => Utility::getTranslatable('Desc'),
        'group_id' => $permGroup->id,
    ]);

    expect($permission->group()->firstOrFail()->id)->toBe($permGroup->id);
});

<?php

declare(strict_types=1);

use App\Library\Utility;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(PermissionGroup::class);

uses(RefreshDatabase::class);

test('permission group can be created with translatable fields', function (): void {
    $permGroup = PermissionGroup::forceCreate([
        'key' => 'booking-group',
        'name' => Utility::getTranslatable('Booking'),
        'description' => Utility::getTranslatable('Booking permissions'),
    ]);

    expect($permGroup->id)->not->toBeNull()
        ->and($permGroup->getTranslations('name'))->toBeArray()
        ->and($permGroup->getTranslations('description'))->toBeArray();
});

test('permission group permissions relationship returns HasMany', function (): void {
    $permGroup = new PermissionGroup;

    expect($permGroup->permissions())->toBeInstanceOf(HasMany::class);
});

test('permissions can be associated with permission group via group_id', function (): void {
    $permGroup = PermissionGroup::forceCreate([
        'key' => 'admin-group',
        'name' => Utility::getTranslatable('Admin'),
        'description' => Utility::getTranslatable('Admin permissions'),
    ]);

    Permission::forceCreate([
        'key' => 'admin_perm_a',
        'name' => Utility::getTranslatable('Admin Perm A'),
        'description' => Utility::getTranslatable('Desc A'),
        'group_id' => $permGroup->id,
    ]);

    Permission::forceCreate([
        'key' => 'admin_perm_b',
        'name' => Utility::getTranslatable('Admin Perm B'),
        'description' => Utility::getTranslatable('Desc B'),
        'group_id' => $permGroup->id,
    ]);

    $count = Permission::where('group_id', $permGroup->id)->count();
    expect($count)->toBe(2);
});

test('permission group name and description translations are retrievable', function (): void {
    $permGroup = PermissionGroup::forceCreate([
        'key' => 'user-group',
        'name' => ['en' => 'User Management', 'de' => 'Nutzerverwaltung'],
        'description' => ['en' => 'User permissions', 'de' => 'Nutzerberechtigungen'],
    ]);

    expect($permGroup->getTranslation('name', 'en'))->toBe('User Management')
        ->and($permGroup->getTranslation('name', 'de'))->toBe('Nutzerverwaltung')
        ->and($permGroup->getTranslation('description', 'en'))->toBe('User permissions');
});

test('permission group is retrievable from seeded data', function (): void {
    $this->seed(PermissionSeeder::class);

    $groups = PermissionGroup::all();

    expect($groups->count())->toBeGreaterThan(0);
});

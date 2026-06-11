<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\InstitutionUserRole;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(Role::class);

uses(RefreshDatabase::class);

test('role can be created with translatable name', function (): void {
    $role = Role::create(['name' => ['en' => 'Admin', 'de' => 'Administrator'], 'description' => ['en' => 'Desc']]);

    expect($role->id)->not->toBeNull()
        ->and($role->getTranslation('name', 'en'))->toBe('Admin');
});

test('role has permissions relationship', function (): void {
    $role = Role::create(['name' => ['en' => 'Editor'], 'description' => ['en' => 'Can edit']]);

    expect($role->permissions())->not->toBeNull();
});

test('role hasPermission returns false when no permissions', function (): void {
    $role = Role::create(['name' => ['en' => 'Empty'], 'description' => ['en' => 'No perms']]);

    expect($role->hasPermission('some-permission'))->toBeFalse();
});

test('role getPermissionKeys returns empty array when no permissions', function (): void {
    $role = Role::create(['name' => ['en' => 'Empty'], 'description' => ['en' => 'No perms']]);
    $role->load('permissions');

    expect($role->getPermissionKeys())->toBe([]);
});

test('role getPermissionKeys returns all permission keys when no filter', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm1 */
    $perm1 = Permission::firstWhere('key', 'view_users');
    /** @var Permission $perm2 */
    $perm2 = Permission::firstWhere('key', 'edit_users');

    $role = Role::create(['name' => ['en' => 'Multi'], 'description' => ['en' => 'Multi perm']]);
    $role->permissions()->attach([$perm1->id, $perm2->id]);
    $role->load('permissions');

    $keys = $role->getPermissionKeys();

    expect($keys)->toContain('view_users', 'edit_users');
});

test('role getPermissionKeys filters to only specified permission keys', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm1 */
    $perm1 = Permission::firstWhere('key', 'view_users');
    /** @var Permission $perm2 */
    $perm2 = Permission::firstWhere('key', 'edit_users');

    $role = Role::create(['name' => ['en' => 'Filtered'], 'description' => ['en' => 'Filter test']]);
    $role->permissions()->attach([$perm1->id, $perm2->id]);
    $role->load('permissions');

    $keys = $role->getPermissionKeys(['view_users']);

    expect($keys)->toContain('view_users')
        ->and($keys)->not->toContain('edit_users');
});

test('role getPermissionKeys returns empty array when filter matches nothing', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');

    $role = Role::create(['name' => ['en' => 'NoMatch'], 'description' => ['en' => 'No match']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    $keys = $role->getPermissionKeys(['nonexistent_permission']);

    expect($keys)->toBe([]);
});

test('role hasPermission returns true when permission key is in permissions', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');

    $role = Role::create(['name' => ['en' => 'Viewer'], 'description' => ['en' => 'Can view']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    expect($role->hasPermission('view_users'))->toBeTrue()
        ->and($role->hasPermission('edit_users'))->toBeFalse();
});

test('hasPermission with no pivot delegates to permissions collection', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');

    $role = Role::create(['name' => ['en' => 'NoPivot'], 'description' => ['en' => '']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    expect($role->pivot)->toBeNull();

    expect($role->hasPermission('view_users'))->toBeTrue()
        ->and($role->hasPermission('nonexistent_key'))->toBeFalse();
});

test('hasPermission with no institution delegates to permissions collection', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');

    $role = Role::create(['name' => ['en' => 'NoInst'], 'description' => ['en' => '']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    expect($role->hasPermission('view_users', null))->toBeTrue();
});

test('hasPermission with no pivot still delegates to permissions collection even when institution is provided', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');
    $institution = Institution::factory()->create();

    $role = Role::create(['name' => ['en' => 'NoPivotWithInstitution'], 'description' => ['en' => '']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    expect($role->pivot)->toBeNull()
        ->and($role->hasPermission('view_users', $institution))->toBeTrue()
        ->and($role->hasPermission('edit_users', $institution))->toBeFalse();
});

test('hasPermission with pivot and null institution falls back to permissions collection', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');
    $institution = Institution::factory()->create();

    $role = Role::create(['name' => ['en' => 'PivotNoInstitution'], 'description' => ['en' => '']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    $pivot = new InstitutionUserRole;
    $pivot->institution_id = $institution->id;
    $pivot->setRelation('role', $role);
    $role->setRelation('pivot', $pivot);

    expect($role->hasPermission('view_users', null))->toBeTrue()
        ->and($role->hasPermission('edit_users', null))->toBeFalse();
});

test('hasPermission with pivot and different institution returns false even when role has the permission', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm */
    $perm = Permission::firstWhere('key', 'view_users');
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();

    $role = Role::create(['name' => ['en' => 'PivotOtherInstitution'], 'description' => ['en' => '']]);
    $role->permissions()->attach($perm->id);
    $role->load('permissions');

    $pivot = new InstitutionUserRole;
    $pivot->institution_id = $institution->id;
    $pivot->setRelation('role', $role);
    $role->setRelation('pivot', $pivot);

    expect($role->hasPermission('view_users', $otherInstitution))->toBeFalse();
});

test('hasPermission returns false when permission not in collection and no institution', function (): void {
    $role = Role::create(['name' => ['en' => 'RemoveNotTest'], 'description' => ['en' => '']]);
    $role->load('permissions');

    expect($role->hasPermission('view_users', null))->toBeFalse();
});

test('getPermissionKeys result has first element at index zero', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm1 */
    $perm1 = Permission::firstWhere('key', 'view_users');
    /** @var Permission $perm2 */
    $perm2 = Permission::firstWhere('key', 'edit_users');

    $role = Role::create(['name' => ['en' => 'ListTest'], 'description' => ['en' => '']]);
    $role->permissions()->attach([$perm1->id, $perm2->id]);
    $role->load('permissions');

    $keys = $role->getPermissionKeys(['view_users']);

    expect($keys)->toContain('view_users')
        ->and($keys[0])->toBe('view_users');
});

test('getPermissionKeys filtered result has sequential integer keys starting from 0', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm1 */
    $perm1 = Permission::firstWhere('key', 'view_users');
    /** @var Permission $perm2 */
    $perm2 = Permission::firstWhere('key', 'edit_users');

    $role = Role::create(['name' => ['en' => 'SeqKeys'], 'description' => ['en' => '']]);
    $role->permissions()->attach([$perm1->id, $perm2->id]);
    $role->load('permissions');

    $keys = $role->getPermissionKeys(['view_users', 'edit_users']);

    expect(array_keys($keys))->toBe([0, 1]);
});

test('getPermissionKeys reindexes a filtered match taken from a later permission', function (): void {
    $this->seed(PermissionSeeder::class);

    /** @var Permission $perm1 */
    $perm1 = Permission::firstWhere('key', 'view_users');
    /** @var Permission $perm2 */
    $perm2 = Permission::firstWhere('key', 'edit_users');

    $role = Role::create(['name' => ['en' => 'LaterMatch'], 'description' => ['en' => '']]);
    $role->permissions()->attach([$perm1->id, $perm2->id]);
    $role->load('permissions');

    $keys = $role->getPermissionKeys(['edit_users']);

    expect($keys)->toBe(['edit_users'])
        ->and(array_keys($keys))->toBe([0]);
});

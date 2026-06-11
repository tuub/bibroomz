<?php

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(User::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('user happenings relation and delete hook cover linked happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $secondUser = User::factory()->create();

    $primaryHappening = Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => null,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => null,
        'label' => ['en' => 'Primary'],
    ]);

    $secondaryHappening = Happening::create([
        'user_id_01' => $secondUser->id,
        'user_id_02' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => '2026-06-10 10:30:00',
        'end' => '2026-06-10 11:30:00',
        'reserved_at' => '2026-06-10 08:30:00',
        'verified_at' => null,
        'label' => ['en' => 'Secondary'],
    ]);

    expect($owner->happenings()->pluck('id')->all())->toBe([$primaryHappening->id]);

    $owner->delete();

    expect(Happening::withTrashed()->find($primaryHappening->id)?->trashed())->toBeTrue()
        ->and(Happening::withTrashed()->find($secondaryHappening->id)?->trashed())->toBeTrue();
});

test('user finds concurrent happenings in the same resource group while excluding the current happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $otherResource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $otherGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $outsideResource = Resource::factory()->for($otherGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $current = Happening::create([
        'user_id_01' => $user->id,
        'user_id_02' => null,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => null,
        'label' => ['en' => 'Current'],
    ]);

    $conflicting = Happening::create([
        'user_id_01' => $user->id,
        'user_id_02' => null,
        'resource_id' => $otherResource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
        'reserved_at' => '2026-06-10 08:15:00',
        'verified_at' => null,
        'label' => ['en' => 'Conflict'],
    ]);

    Happening::create([
        'user_id_01' => $user->id,
        'user_id_02' => null,
        'resource_id' => $outsideResource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => '2026-06-10 09:15:00',
        'end' => '2026-06-10 09:45:00',
        'reserved_at' => '2026-06-10 08:20:00',
        'verified_at' => null,
        'label' => ['en' => 'Outside'],
    ]);

    $otherHappenings = $user->getOtherUserHappeningsForResourceGroup($resourceGroup, $current);

    expect($otherHappenings->modelKeys())->toBe([$conflicting->id])
        ->and($user->isHavingConcurrentHappening(
            CarbonImmutable::parse('2026-06-10 09:45:00'),
            CarbonImmutable::parse('2026-06-10 10:15:00'),
            $current,
        ))->toBeTrue()
        ->and($user->isHavingConcurrentHappening(
            CarbonImmutable::parse('2026-06-10 11:00:00'),
            CarbonImmutable::parse('2026-06-10 12:00:00'),
            $current,
        ))->toBeFalse();
});

test('non admin user permissions are collected from attached institution roles', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $institution, 'view_users');
    grantAdminPermission($user, $institution, 'edit_users');

    $permissions = $user->fresh('roles.permissions')?->getPermissions();

    expect($permissions?->get($institution->id)?->all())->toContain('view_users', 'edit_users')
        ->and($user->fresh('roles.permissions')?->hasPermission('view_users', $institution))->toBeTrue()
        ->and($user->fresh('roles.permissions')?->hasPermission('delete_users', $institution))->toBeFalse();
});

test('user reports whether it is a system user', function (): void {
    $systemUser = User::factory()->create(['is_system_user' => true]);
    $regularUser = User::factory()->create(['is_system_user' => false]);

    expect($systemUser->isSystemUser())->toBeTrue()
        ->and($regularUser->isSystemUser())->toBeFalse();
});

test('admin users receive all permission keys for every institution', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $firstInstitution = Institution::factory()->create();
    $secondInstitution = Institution::factory()->create();

    grantAdminPermission($admin, $firstInstitution, 'view_users');
    grantAdminPermission($admin, $secondInstitution, 'edit_users');

    $permissions = $admin->fresh('roles.permissions')?->getPermissions(['view_users', 'edit_users']);

    expect($permissions?->keys()->all())->toContain($firstInstitution->id, $secondInstitution->id)
        ->and($permissions?->get($firstInstitution->id)?->all())->toContain('view_users', 'edit_users')
        ->and($permissions?->get($secondInstitution->id)?->all())->toContain('view_users', 'edit_users')
        ->and($admin->hasPermission('delete_anything', $firstInstitution))->toBeTrue();
});

test('user login status depends on the persisted flag and activity cache', function (): void {
    $loggedOutUser = User::factory()->create(['is_logged_in' => false]);
    $loggedInUser = User::factory()->create(['is_logged_in' => true]);

    cache()->put('user_activity_'.$loggedInUser->id, true, 60);

    expect($loggedOutUser->isLoggedIn())->toBeFalse()
        ->and($loggedInUser->isLoggedIn())->toBeTrue();

    cache()->forget('user_activity_'.$loggedInUser->id);
});

test('user isLoggedIn returns false when flag is true but cache entry missing', function (): void {
    $user = User::factory()->create(['is_logged_in' => true]);
    cache()->forget('user_activity_'.$user->id);

    expect($user->isLoggedIn())->toBeFalse();
});

test('user isAdmin returns true for admin and false for regular user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $regular = User::factory()->create(['is_admin' => false]);

    expect($admin->isAdmin())->toBeTrue()
        ->and($regular->isAdmin())->toBeFalse();
});

test('user getPermissions with filter returns only matching permission keys for admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $institution = Institution::factory()->create();

    grantAdminPermission($admin, $institution, 'view_users');
    grantAdminPermission($admin, $institution, 'edit_users');

    $permissions = $admin->fresh('roles.permissions')?->getPermissions(['view_users']);

    expect($permissions?->get($institution->id)?->all())->toContain('view_users')
        ->and($permissions?->get($institution->id)?->all())->not->toContain('edit_users');
});

test('user getPermissions deduplicates keys when the same permission appears in multiple roles', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $institution, 'view_users');
    grantAdminPermission($user, $institution, 'view_users');

    $permissions = $user->fresh('roles.permissions')?->getPermissions();

    $keys = $permissions?->get($institution->id)?->all() ?? [];
    expect(array_count_values($keys)['view_users'])->toBe(1);
});

test('user hasPermission returns false when user has no roles', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    expect($user->fresh('roles')?->hasPermission('view_users', $institution))->toBeFalse();
});

test('isSystemUser returns false for user with is_system_user as 0 or false (RemoveBooleanCast)', function (): void {
    // RemoveBooleanCast would remove the (bool) cast from is_system_user
    $user = User::factory()->create(['is_system_user' => false]);

    expect($user->isSystemUser())->toBeFalse();

    // Also check it returns a bool, not truthy/falsy integer
    expect($user->isSystemUser())->toBeBool();
});

test('isHavingConcurrentHappening returns false when happening is null', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    // When $happening is null, $happening?->resource->resource_group resolves to null safely
    // RemoveNullSafeOperator would make this throw a null-dereference error
    $result = $user->isHavingConcurrentHappening(
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 10:00:00'),
        null, // $happening = null
    );

    expect($result)->toBeFalse();
});

test('getOtherUserHappeningsForResourceGroup handles null resource_group', function (): void {
    $user = User::factory()->create();

    // When $resource_group is null, $resource_group?->getKey() should return null safely
    // RemoveNullSafeOperator would call getKey() on null and throw
    $result = $user->getOtherUserHappeningsForResourceGroup(null, null);

    expect($result)->toBeEmpty();
});

test('getOtherUserHappeningsForResourceGroup handles null happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    // When $happening is null, $happening?->id should return null safely
    $result = $user->getOtherUserHappeningsForResourceGroup($resourceGroup, null);

    expect($result->count())->toBe(0);
});

test('getPermissions casts institution id to string', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $institution = Institution::factory()->create();

    $this->seed(PermissionSeeder::class);

    $perms = $admin->fresh('roles.permissions')?->getPermissions();

    // The keys of the resulting collection should be strings, not integers
    $keys = $perms?->keys()->all() ?? [];
    foreach ($keys as $key) {
        expect($key)->toBeString();
    }
});

test('getPermissions role institution_id is cast to string', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $institution, 'view_users');

    $perms = $user->fresh('roles.permissions')?->getPermissions();

    // Keys should be string representation of UUIDs
    $keys = $perms?->keys()->all() ?? [];
    foreach ($keys as $key) {
        expect($key)->toBeString();
    }
    expect($keys)->toContain($institution->id);
});

test('isLoggedIn returns false when is_logged_in is false even if cache exists', function (): void {
    $user = User::factory()->create(['is_logged_in' => false]);

    // Put cache entry that would make isLoggedIn true if early return is removed
    cache()->put('user_activity_'.$user->id, true, 60);

    // RemoveEarlyReturn mutation would skip the "return false"
    // and proceed to cache check — but the early return MUST fire first
    expect($user->isLoggedIn())->toBeFalse();

    cache()->forget('user_activity_'.$user->id);
});

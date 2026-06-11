<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(Institution::class);

uses(RefreshDatabase::class);

test('institution creates with valid data', function (): void {
    $institution = Institution::factory()->create([
        'title' => ['en' => 'Test Library', 'de' => 'Testbibliothek'],
        'email' => 'library@example.com',
        'is_active' => true,
    ]);

    expect($institution->id)->not->toBeNull()
        ->and($institution->email)->toBe('library@example.com')
        ->and($institution->is_active)->toBeTrue();
});

test('institution title field stores and retrieves translations', function (): void {
    $institution = Institution::factory()->create([
        'title' => ['en' => 'Reading Room', 'de' => 'Leseraum'],
    ]);

    expect($institution->getTranslation('title', 'en'))->toBe('Reading Room')
        ->and($institution->getTranslation('title', 'de'))->toBe('Leseraum');
});

test('institution resource_groups relationship returns HasMany', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->resource_groups())->toBeInstanceOf(HasMany::class);
});

test('institution resource_groups loads related resource groups', function (): void {
    $institution = Institution::factory()->create();
    ResourceGroup::factory()->for($institution, 'institution')->create();
    ResourceGroup::factory()->for($institution, 'institution')->create();

    expect($institution->resource_groups()->count())->toBe(2);
});

test('institution resources relationship returns HasManyThrough', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->resources())->toBeInstanceOf(HasManyThrough::class);
});

test('institution resources loads resources through resource groups', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($resourceGroup, 'resource_group')->create();
    Resource::factory()->for($resourceGroup, 'resource_group')->create();

    expect($institution->resources()->count())->toBe(2);
});

test('institution users relationship returns BelongsToMany', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->users())->toBeInstanceOf(BelongsToMany::class);
});

test('institution user_groups relationship returns HasMany', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->user_groups())->toBeInstanceOf(HasMany::class);
});

test('institution user_groups loads related user groups', function (): void {
    $institution = Institution::factory()->create();
    UserGroup::create(['title' => ['en' => 'Group A'], 'institution_id' => $institution->id]);

    expect($institution->user_groups()->count())->toBe(1);
});

test('institution closings relationship returns MorphMany', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->closings())->toBeInstanceOf(MorphMany::class);
});

test('institution closings loads related closings', function (): void {
    $institution = Institution::factory()->create();

    Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Closing A'],
    ]);

    expect($institution->closings()->count())->toBe(1);
});

test('institution settings relationship returns MorphMany', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->settings())->toBeInstanceOf(MorphMany::class);
});

test('institution settings relationship returns settings', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->settings()->count())->toBeGreaterThan(0);
});

test('institution week_days relationship returns BelongsToMany', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->week_days())->toBeInstanceOf(BelongsToMany::class);
});

test('institution week_days can be synced', function (): void {
    $this->seed(WeekDaySeeder::class);
    $institution = Institution::factory()->create();

    $institution->week_days()->sync([1, 2]);

    expect($institution->week_days()->count())->toBe(2);
});

test('institution is_active boolean is cast correctly', function (): void {
    $active = Institution::factory()->create(['is_active' => true]);
    $inactive = Institution::factory()->create(['is_active' => false]);

    expect($active->is_active)->toBeTrue()
        ->and($inactive->is_active)->toBeFalse();
});

test('institution scopeActive filters inactive institutions', function (): void {
    Institution::factory()->create(['is_active' => true]);
    Institution::factory()->create(['is_active' => false]);

    expect(Institution::query()->active()->count())->toBe(1);
});

test('institution institutionForClosings returns itself', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->institutionForClosings()->is($institution))->toBeTrue();
});

test('institution institutionForSettings returns itself', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->institutionForSettings()->is($institution))->toBeTrue();
});

test('scopeActive uses true literal — inactive institutions are excluded', function (): void {
    Institution::factory()->create(['is_active' => true]);
    Institution::factory()->create(['is_active' => true]);
    Institution::factory()->create(['is_active' => false]);

    expect(Institution::query()->active()->count())->toBe(2);
});

test('isUserAbleToCreateResource passes Resource class and institution in array', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    expect($institution->isUserAbleToCreateResource($user))->toBeTrue();
});

test('isUserAbleToCreateResource returns false for user without permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    expect($institution->isUserAbleToCreateResource($user))->toBeFalse();
});

test('isUserAbleToCreateResourceGroup passes ResourceGroup class and institution in array', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    expect($institution->isUserAbleToCreateResourceGroup($user))->toBeTrue();
});

test('isUserAbleToCreateResourceGroup returns false for user without permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    expect($institution->isUserAbleToCreateResourceGroup($user))->toBeFalse();
});

test('isUserAbleToCreateUserGroup passes UserGroup class and institution in array', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    expect($institution->isUserAbleToCreateUserGroup($user))->toBeTrue();
});

test('isUserAbleToCreateUserGroup returns false for user without permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    expect($institution->isUserAbleToCreateUserGroup($user))->toBeFalse();
});

test('isUserAbleToCreateResource requires the resource create permission on the institution context', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    grantAdminPermission($user, $institution, 'create_resources');
    $user = $user->fresh('roles.permissions');
    if (! $user instanceof User) {
        throw new RuntimeException('Expected refreshed user instance.');
    }

    expect($institution->isUserAbleToCreateResource($user))->toBeTrue()
        ->and($institution->isUserAbleToCreateResourceGroup($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateUserGroup($user))->toBeFalse();
});

test('isUserAbleToCreateResourceGroup requires the resource-group create permission on the institution context', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    grantAdminPermission($user, $institution, 'create_resource_groups');
    $user = $user->fresh('roles.permissions');
    if (! $user instanceof User) {
        throw new RuntimeException('Expected refreshed user instance.');
    }

    expect($institution->isUserAbleToCreateResource($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateResourceGroup($user))->toBeTrue()
        ->and($institution->isUserAbleToCreateUserGroup($user))->toBeFalse();
});

test('isUserAbleToCreateUserGroup requires the user-group create permission on the institution context', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    grantAdminPermission($user, $institution, 'create_user_groups');
    $user = $user->fresh('roles.permissions');
    if (! $user instanceof User) {
        throw new RuntimeException('Expected refreshed user instance.');
    }

    expect($institution->isUserAbleToCreateResource($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateResourceGroup($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateUserGroup($user))->toBeTrue();
});

test('institution getHiddenDays returns days not assigned to institution', function (): void {
    $this->seed(WeekDaySeeder::class);
    $institution = Institution::factory()->create();

    $institution->week_days()->sync([1]); // Only monday

    $hidden = $institution->getHiddenDays();

    expect($hidden->isEmpty())->toBeFalse(); // Other days should be hidden
});

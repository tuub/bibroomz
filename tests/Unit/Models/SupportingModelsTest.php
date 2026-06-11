<?php

use App\Library\Utility;
use App\Models\BusinessHour;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\InstitutionUserRole;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserGroupUser;
use App\Models\WeekDay;
use App\Rules\CurrentPasswordRule;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithPermissions;

covers(
    Closing::class,
    Institution::class,
    ResourceGroup::class,
    Resource::class,
    Role::class,
    Permission::class,
    PermissionGroup::class,
    InstitutionUserRole::class,
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    Carbon::setTestNow(Carbon::parse('2026-06-03 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 10:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('supporting models expose domain helpers and translation wrappers', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);

    DB::table('week_days')->insert([
        ['day_of_week' => 1, 'key' => 'mon'],
        ['day_of_week' => 2, 'key' => 'tue'],
        ['day_of_week' => 3, 'key' => 'wed'],
    ]);
    /** @var WeekDay $weekdayOne */
    $weekdayOne = WeekDay::find(1);
    /** @var WeekDay $weekdayTwo */
    $weekdayTwo = WeekDay::find(2);
    /** @var WeekDay $weekdayThree */
    $weekdayThree = WeekDay::find(3);
    $institution->week_days()->sync([$weekdayOne->id, $weekdayThree->id]);

    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Members'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup);

    $user = User::factory()->create(['name' => 'member']);
    $user->user_groups()->attach($userGroup, [
        'valid_from' => CarbonImmutable::now()->subDay(),
        'valid_until' => CarbonImmutable::now()->addDay(),
    ]);

    $blockedUser = User::factory()->create(['name' => 'blocked']);
    $blockedUser->user_groups()->attach($userGroup, [
        'valid_from' => CarbonImmutable::now()->addDay(),
        'valid_until' => CarbonImmutable::now()->addDays(2),
    ]);

    expect($resourceGroup->isAllowedUser($user))->toBeTrue()
        ->and($resourceGroup->isAllowedUser($blockedUser))->toBeFalse();

    $viewer = User::factory()->create();
    $this->grantPermission($viewer, $institution, 'view_resource_groups');
    $this->grantPermission($viewer, $institution, 'view_institution');

    expect($resourceGroup->isViewableByUser($viewer))->toBeTrue()
        ->and($institution->isViewableByUser($viewer))->toBeTrue()
        ->and($institution->getHiddenDays()->all())->toBe([$weekdayTwo->day_of_week]);

    $happening = Happening::create([
        'user_id_01' => $user->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => 'verifier',
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'label' => Utility::getTranslatable('Study'),
    ]);

    expect($institution->getHappenings()->pluck('id')->all())->toContain($happening->id);

    App::setLocale('en');
    expect($resourceGroup->withoutTranslations()['title'])->toBe($resourceGroup->getTranslation('title', 'en'))
        ->and($resourceGroup->withTranslations()['title'])->toBeArray();

    $permissionGroup = PermissionGroup::forceCreate([
        'key' => 'booking-group',
        'name' => Utility::getTranslatable('Booking'),
        'description' => Utility::getTranslatable('Permissions'),
    ]);

    expect($permissionGroup->permissions()->getRelated()::class)->toBe(Permission::class)
        ->and((new UserGroupUser)->user_group()->getRelated()::class)->toBe(UserGroup::class)
        ->and((new InstitutionUserRole)->user()->getRelated()::class)->toBe(User::class)
        ->and($weekdayOne->business_hours()->getRelated()::class)->toBe(BusinessHour::class)
        ->and($weekdayOne->institutions()->getRelated()::class)->toBe(Institution::class);
});

// ── Closing model: uncovered branches ────────────────────────────────────────

test('closing institution relationship returns a BelongsTo', function (): void {
    $closing = new Closing;
    expect($closing->institution())->toBeInstanceOf(BelongsTo::class);
});

test('closing getClosableModel returns Institution for institution path', function (): void {
    expect(Closing::getClosableModel('institution'))->toBeInstanceOf(Institution::class);
});

test('closing getClosableModel throws for unknown type', function (): void {
    expect(fn (): Institution|\App\Models\Resource => Closing::getClosableModel('unknown'))
        ->toThrow(InvalidArgumentException::class);
});

test('closing getClosingSubject throws when closable is not a ClosingSubject', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $user = User::factory()->create();

    $closing = Closing::create([
        'closable_id' => $resource->id,
        'closable_type' => $resource->getMorphClass(),
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'description' => ['en' => 'test'],
    ]);

    // Manually override closable to a User (which does not implement ClosingSubject)
    $closing->setRelation('closable', $user);

    expect(fn () => $closing->getClosingSubject())
        ->toThrow(InvalidArgumentException::class);
});

// ── Institution model: uncovered relationship and scope lines ─────────────────

test('institution relationship methods return correct relation types', function (): void {
    $institution = Institution::factory()->create();

    expect($institution->resource_groups())->toBeInstanceOf(HasMany::class)
        ->and($institution->resources())->toBeInstanceOf(HasManyThrough::class)
        ->and($institution->users())->toBeInstanceOf(BelongsToMany::class)
        ->and($institution->user_groups())->toBeInstanceOf(HasMany::class)
        ->and($institution->closings())->toBeInstanceOf(MorphMany::class);
});

test('institution scopeActive filters by is_active', function (): void {
    $sql = Institution::query()->active()->toSql();
    expect($sql)->toContain('is_active');
});

test('institution permission helpers return false for unprivileged user', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    expect($institution->isEditableByUser($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateResource($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateResourceGroup($user))->toBeFalse()
        ->and($institution->isUserAbleToCreateUserGroup($user))->toBeFalse();
});

test('institution institutionForClosings returns itself', function (): void {
    $institution = Institution::factory()->create();
    expect($institution->institutionForClosings()->is($institution))->toBeTrue();
});

test('institution institutionForSettings returns itself', function (): void {
    $institution = Institution::factory()->create();
    expect($institution->institutionForSettings()->is($institution))->toBeTrue();
});

// ── ResourceGroup model: uncovered lines ─────────────────────────────────────

test('resource group resources relationship returns HasMany', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);

    expect($resourceGroup->resources())->toBeInstanceOf(HasMany::class);
});

test('resource group scopeActive includes active institution check', function (): void {
    $sql = ResourceGroup::query()->active()->toSql();
    expect($sql)->toContain('is_active');
});

test('resource group isAllowedUser returns true when resource group has no user groups', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $user = User::factory()->create();

    // No user_groups attached → isEmpty() → return true
    $resourceGroup->load('user_groups');
    expect($resourceGroup->isAllowedUser($user))->toBeTrue();
});

test('resource group isAllowedUser returns true when user group pivot has both dates null', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('TestGroup2'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup, [
        'valid_from' => null,
        'valid_until' => null,
    ]);

    // Both null
    expect($resourceGroup->isAllowedUser($user))->toBeTrue();
});

test('resource group isAllowedUser branches covering valid_from only and valid_until only', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('TestGroup'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup);

    // User with valid_from in the past and no valid_until (only valid_from check)
    $userFromOnly = User::factory()->create();
    $userFromOnly->user_groups()->attach($userGroup, [
        'valid_from' => CarbonImmutable::now()->subDay(),
        'valid_until' => null,
    ]);

    // User with valid_until in the future and no valid_from (only valid_until check)
    $userUntilOnly = User::factory()->create();
    $userUntilOnly->user_groups()->attach($userGroup, [
        'valid_from' => null,
        'valid_until' => CarbonImmutable::now()->addDay(),
    ]);

    expect($resourceGroup->isAllowedUser($userFromOnly))->toBeTrue()
        ->and($resourceGroup->isAllowedUser($userUntilOnly))->toBeTrue();
});

// ── Role model: uncovered relationship and method lines ───────────────────────

test('role users and institutions relationships return BelongsToMany', function (): void {
    expect((new Role)->users())->toBeInstanceOf(BelongsToMany::class)
        ->and((new Role)->institutions())->toBeInstanceOf(BelongsToMany::class);
});

test('role getPermissionKeys returns all keys when permissions is null', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create([
        'name' => Utility::getTranslatable('TestRole'),
        'description' => Utility::getTranslatable('Desc'),
    ]);
    $permission = Permission::firstWhere('key', 'view_mails');
    $role->permissions()->attach($permission);
    $role->load('permissions');

    expect($role->getPermissionKeys(null))->toContain('view_mails')
        ->and($role->getPermissionKeys([]))->toContain('view_mails');
});

// ── InstitutionUserRole: uncovered relationship lines ─────────────────────────

test('institution user role institution relationship returns BelongsTo', function (): void {
    $pivot = new InstitutionUserRole;
    expect($pivot->institution())->toBeInstanceOf(BelongsTo::class);
});

test('institution user role hasPermission returns false when institution id does not match', function (): void {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();

    $role = Role::create([
        'name' => Utility::getTranslatable('SomeRole'),
        'description' => Utility::getTranslatable('Desc'),
    ]);
    $permission = Permission::firstWhere('key', 'view_mails');
    $role->permissions()->attach($permission);
    $role->load('permissions');

    $pivot = new InstitutionUserRole;
    $pivot->institution_id = $institution->id;
    $pivot->setRelation('role', $role);

    // Different institution: should return false
    expect($pivot->hasPermission('view_mails', $otherInstitution))->toBeFalse();
});

// ── Permission model: uncovered roles relationship ────────────────────────────

test('permission roles relationship returns BelongsToMany', function (): void {
    $permission = new Permission;
    expect($permission->roles())->toBeInstanceOf(BelongsToMany::class);
});

// ── Resource model: uncovered scope lines ────────────────────────────────────

test('resource scopeActive filters by is_active', function (): void {
    $sql = Resource::query()->active()->toSql();
    expect($sql)->toContain('is_active');
});

test('resource isVerificationRequired returns the boolean flag', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create([
        'resource_group_id' => $resourceGroup->id,
        'is_verification_required' => true,
    ]);
    expect($resource->isVerificationRequired())->toBeTrue();
});

test('closing and password rule handle affected users and current password checks', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $user = User::factory()->create(['name' => 'owner', 'password' => bcrypt('secret')]);
    $verifier = User::factory()->create(['name' => 'verifier']);

    $happening = Happening::create([
        'user_id_01' => $user->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'label' => Utility::getTranslatable('Study'),
    ]);

    $closing = Closing::create([
        'closable_id' => $resource->id,
        'closable_type' => $resource->getMorphClass(),
        'start' => CarbonImmutable::now()->addMinutes(30),
        'end' => CarbonImmutable::now()->addHours(3),
        'description' => Utility::getTranslatable('Maintenance'),
    ]);

    expect($closing->getHappeningsAffected()->pluck('id')->all())->toContain($happening->id)
        ->and($closing->getUsersAffected()->pluck('name')->all())->toContain($user->name, $verifier->name)
        ->and($closing->getUserHappeningsAffected($user)->pluck('id')->all())->toContain($happening->id)
        ->and($closing->getInstitution()->is($institution))->toBeTrue()
        ->and($resource->institutionForClosings()->is($institution))->toBeTrue()
        ->and($resourceGroup->institutionForSettings()->is($institution))->toBeTrue()
        ->and($closing->prunable()->toSql())->toContain('deleted_at');

    $errors = [];
    (new CurrentPasswordRule($user->name, 'wrong-secret'))
        ->validate('password', 'ignored', function ($message) use (&$errors): void {
            $errors[] = $message;
        });

    expect($errors)->toHaveCount(1);

    $errors = [];
    (new CurrentPasswordRule($user->name, 'secret'))
        ->validate('password', 'ignored', function ($message) use (&$errors): void {
            $errors[] = $message;
        });

    expect($errors)->toBe([]);
});

test('closing getClosableModel returns Resource for resource path', function (): void {
    expect(Closing::getClosableModel('resource'))->toBeInstanceOf(Resource::class);
});

test('role getPermissionKeys filters when non-empty permissions array is given', function (): void {
    $role = Role::create([
        'name' => Utility::getTranslatable('FilterRole'),
        'description' => Utility::getTranslatable('Desc'),
    ]);
    $permission = Permission::firstWhere('key', 'view_mails');
    $role->permissions()->attach($permission);
    $role->load('permissions');

    expect($role->getPermissionKeys(['view_mails']))->toContain('view_mails')
        ->and($role->getPermissionKeys(['nonexistent_perm']))->toBe([]);
});

test('resource group isAllowedUser returns true when only valid_until is set and now is before it', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $userGroup = UserGroup::create([
        'title' => ['en' => 'Group'],
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($userGroup);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup, [
        'valid_from' => null,
        'valid_until' => Carbon::now()->addDay(),
    ]);

    $resourceGroup->load('user_groups');
    $user->load('user_groups');

    expect($resourceGroup->isAllowedUser($user))->toBeTrue();
});

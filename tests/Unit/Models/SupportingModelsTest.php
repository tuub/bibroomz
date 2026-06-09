<?php

use App\Contracts\ClosingSubject;
use App\Contracts\SettingSubject;
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
    ClosingSubject::class,
    SettingSubject::class
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

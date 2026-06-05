<?php

covers(
    App\Services\ResourceGroupService::class,
    App\Services\UserGroupService::class
);

use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\ResourceGroupService;
use App\Services\UserGroupService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('resource group service stores updates loads and deletes groups', function () {
    $institution = Institution::factory()->create();
    $selectedUserGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Members'),
        'institution_id' => $institution->id,
    ]);
    $replacementUserGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Guests'),
        'institution_id' => $institution->id,
    ]);

    $service = new ResourceGroupService();

    $stored = $service->storeResourceGroup([
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'),
        'slug' => 'rooms',
        'term_singular' => Utility::getTranslatable('Room'),
        'term_plural' => Utility::getTranslatable('Rooms'),
        'description' => Utility::getTranslatable('Description'),
        'is_active' => true,
        'user_groups' => [$selectedUserGroup->id],
    ]);

    expect($stored->settings()->count())->toBe(count(Setting::getInitialValues()['resource_group']))
        ->and($stored->user_groups->pluck('id')->all())->toBe([$selectedUserGroup->id]);

    $loaded = $service->getResourceGroupById($stored->id);
    expect($loaded->relationLoaded('user_groups'))->toBeTrue();

    $updated = $service->updateResourceGroup($stored->id, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Study rooms'),
        'slug' => 'study-rooms',
        'term_singular' => Utility::getTranslatable('Study room'),
        'term_plural' => Utility::getTranslatable('Study rooms'),
        'description' => Utility::getTranslatable('Updated'),
        'is_active' => false,
        'user_groups' => [$replacementUserGroup->id],
    ]);

    expect($updated->slug)->toBe('study-rooms')
        ->and($updated->user_groups->pluck('id')->all())->toBe([$replacementUserGroup->id]);

    $deleted = $service->deleteResourceGroup($stored->id);

    expect($deleted->id)->toBe($stored->id);
    expect(ResourceGroup::find($stored->id))->toBeNull();
});

test('resource group service filters institutions and groups by user permissions', function () {
    $allowedInstitution = Institution::factory()->create();
    $deniedInstitution = Institution::factory()->create();
    $user = User::factory()->create();

    $allowedGroup = ResourceGroup::factory()->create(['institution_id' => $allowedInstitution->id]);
    ResourceGroup::factory()->create(['institution_id' => $deniedInstitution->id]);

    $this->grantPermission($user, $allowedInstitution, 'create_resource_groups');
    $this->grantPermission($user, $allowedInstitution, 'view_resource_groups');

    $service = new ResourceGroupService();

    expect($service->getInstitutionsForUser($user)->pluck('id')->all())->toBe([$allowedInstitution->id])
        ->and($service->getResourceGroupsForUser($user)->pluck('id')->all())->toBe([$allowedGroup->id]);
});

test('user group service stores imports updates lists removes and deletes users', function () {
    $institution = Institution::factory()->create();
    $service = new UserGroupService();

    $userGroup = $service->storeUserGroup([
        'title' => Utility::getTranslatable('Editors'),
        'institution_id' => $institution->id,
    ]);

    expect($userGroup)->toBeInstanceOf(UserGroup::class);

    $service->importUsers($userGroup->id, [
        'users' => [
            ['name' => 'Mixed.User'],
            ['name' => 'Second.User'],
        ],
        'valid_from' => CarbonImmutable::parse('2026-06-01 00:00:00'),
        'valid_until' => CarbonImmutable::parse('2026-06-30 23:59:59'),
    ]);

    $importedUsers = $service->getUsers($userGroup);
    expect($importedUsers->pluck('name')->all())->toBe(['mixed.user', 'second.user']);

    $service->importUsers($userGroup->id, [
        'users' => [
            ['name' => 'Mixed.User'],
        ],
        'valid_from' => null,
        'valid_until' => CarbonImmutable::parse('2026-07-31 23:59:59'),
    ]);

    $pivot = $userGroup->fresh()->users()->where('name', 'mixed.user')->first()->pivot;
    expect($pivot->valid_from)->toBeNull()
        ->and($pivot->valid_until->format('Y-m-d'))->toBe('2026-07-31');

    $updated = $service->updateUserGroup($userGroup->id, [
        'title' => Utility::getTranslatable('Updated editors'),
        'institution_id' => $institution->id,
    ]);
    expect($updated->getTranslation('title', 'en'))->toBe('Updated editors');

    $loaded = $service->getUserGroupById($userGroup->id);
    $service->removeUsers($loaded->id, $importedUsers->pluck('id')->all());

    expect($loaded->fresh()->users)->toHaveCount(0);

    $deleted = $service->deleteUserGroup($loaded->id);
    expect($deleted->id)->toBe($loaded->id)
        ->and(UserGroup::find($loaded->id))->toBeNull();
});

test('user group service filters institutions and groups by user permissions', function () {
    $allowedInstitution = Institution::factory()->create();
    $deniedInstitution = Institution::factory()->create();
    $user = User::factory()->create();

    $allowedGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Allowed'),
        'institution_id' => $allowedInstitution->id,
    ]);
    UserGroup::create([
        'title' => Utility::getTranslatable('Denied'),
        'institution_id' => $deniedInstitution->id,
    ]);

    $this->grantPermission($user, $allowedInstitution, 'create_user_groups');
    $this->grantPermission($user, $allowedInstitution, 'view_user_groups');

    $service = new UserGroupService();

    expect($service->getInstitutionsForUser($user)->pluck('id')->all())->toBe([$allowedInstitution->id])
        ->and($service->getUserGroupsForUser($user)->pluck('id')->all())->toBe([$allowedGroup->id]);
});

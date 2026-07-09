<?php

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceGroup::class);

uses(RefreshDatabase::class);

test('resource group model can be created', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    expect($resourceGroup)->toBeInstanceOf(ResourceGroup::class)
        ->and($resourceGroup->institution_id)->toBe($institution->id);
});

test('resource group model has translatable attributes', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    expect($resourceGroup->getTranslations('title'))->toBeArray();
});

test('resource group model has institution relationship', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    /** @var Institution $found */
    $found = $resourceGroup->institution()->firstOrFail();
    expect($found->id)->toBe($institution->id);
});

test('resource group model can be deleted', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $id = $resourceGroup->id;
    $resourceGroup->delete();

    expect(ResourceGroup::find($id))->toBeNull();
});

test('resource group model has resources relationship', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    expect($resourceGroup->resources())->not->toBeNull();
});

test('resource group scopeActive returns only active groups with active institutions', function (): void {
    $activeInstitution = Institution::factory()->create(['is_active' => true]);
    $inactiveInstitution = Institution::factory()->create(['is_active' => false]);

    $activeGroup = ResourceGroup::factory()->for($activeInstitution, 'institution')->create(['is_active' => true]);
    ResourceGroup::factory()->for($activeInstitution, 'institution')->create(['is_active' => false]);
    ResourceGroup::factory()->for($inactiveInstitution, 'institution')->create(['is_active' => true]);

    $results = ResourceGroup::active()->pluck('id')->all();

    expect($results)->toContain($activeGroup->id)
        ->and(count($results))->toBe(1);
});

test('resource group institutionForSettings returns institution', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rg->load('institution');

    expect($rg->institutionForSettings())->toBeInstanceOf(Institution::class)
        ->and($rg->institutionForSettings()->id)->toBe($institution->id);
});

test('resource group isAllowedUser returns true when no user groups are attached', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rg->load('user_groups');

    $user = User::factory()->create();

    expect($rg->isAllowedUser($user))->toBeTrue();
});

test('isAllowedUser returns true when user is in group with no time restrictions', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id, ['valid_from' => null, 'valid_until' => null]);
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeTrue();
});

test('isAllowedUser returns false when user is not in any of the groups', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeFalse();
});

test('isAllowedUser returns true when valid_until is in future and valid_from is null (isBefore boundary)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id, [
        'valid_from' => null,
        'valid_until' => now()->addDays(30)->toDateString(),
    ]);
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeTrue();
});

test('isAllowedUser returns true when valid_from is in past and valid_until is null (isAfter boundary)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id, [
        'valid_from' => now()->subDays(5)->toDateString(),
        'valid_until' => null,
    ]);
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeTrue();
});

test('isAllowedUser returns false when only valid_until is set and it is already in the past', function (): void {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id, [
        'valid_from' => null,
        'valid_until' => now()->subDay()->toDateString(),
    ]);
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeFalse();

    Carbon::setTestNow();
});

test('isAllowedUser returns false when only valid_from is set and it is still in the future', function (): void {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id, [
        'valid_from' => now()->addDay()->toDateString(),
        'valid_until' => null,
    ]);
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeFalse();

    Carbon::setTestNow();
});

test('isAllowedUser returns false when valid_from is in the past but valid_until is also in the past', function (): void {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create();
    $user->user_groups()->attach($userGroup->id, [
        'valid_from' => now()->subDays(10)->toDateString(),
        'valid_until' => now()->subDay()->toDateString(),
    ]);
    $rg->load('user_groups');
    $user->load('user_groups');

    expect($rg->isAllowedUser($user))->toBeFalse();

    Carbon::setTestNow();
});

test('isAllowedUser returns true for admin even when resource group has user groups and admin is not a member', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $admin = User::factory()->create(['is_admin' => true]);
    $rg->load('user_groups');
    $admin->load('user_groups');

    expect($rg->isAllowedUser($admin))->toBeTrue();
});

test('resource group isViewableByUser returns false for user without permission', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $user = User::factory()->create(['is_admin' => false]);

    expect($rg->isViewableByUser($user))->toBeFalse();
});

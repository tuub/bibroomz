<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\UserGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(UserGroupService::class);

uses(RefreshDatabase::class);

test('getUserGroupById returns the user group', function (): void {
    $institution = Institution::factory()->create();
    $ug = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    $service = new UserGroupService;
    $found = $service->getUserGroupById($ug->id);

    expect($found->id)->toBe($ug->id);
});

test('deleteUserGroup deletes and returns the user group', function (): void {
    $institution = Institution::factory()->create();
    $ug = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);
    $id = $ug->id;

    $service = new UserGroupService;
    $deleted = $service->deleteUserGroup($id);

    expect($deleted->id)->toBe($id)
        ->and(UserGroup::find($id))->toBeNull();
});

test('getUserGroupsForUser returns collection', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $service = new UserGroupService;
    $result = $service->getUserGroupsForUser($user);

    expect($result)->toBeInstanceOf(Collection::class);
});

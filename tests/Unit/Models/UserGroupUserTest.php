<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserGroupUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UserGroupUser::class);

uses(RefreshDatabase::class);

test('user group user pivot can be created via relationship', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);
    $group->users()->attach($user->id);

    $pivot = $group->users()->withPivot('valid_from', 'valid_until')->first()?->pivot;
    expect($pivot)->toBeInstanceOf(UserGroupUser::class);
});

test('user group user pivot has user relationship', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);
    $group->users()->attach($user->id);

    /** @var UserGroupUser $pivot */
    $pivot = UserGroupUser::where('user_id', $user->id)->where('user_group_id', $group->id)->firstOrFail();
    expect($pivot->user()->firstOrFail()->id)->toBe($user->id);
});

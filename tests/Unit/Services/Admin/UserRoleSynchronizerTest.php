<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserRoleSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UserRoleSynchronizer::class);

uses(RefreshDatabase::class);

test('sync attaches roles to user', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $actor = User::factory()->create(['is_admin' => true]);
    $role = Role::create(['name' => ['en' => 'Editor'], 'description' => ['en' => 'Edit']]);

    $synchronizer = app(UserRoleSynchronizer::class);
    $synchronizer->sync($user, [['role_id' => $role->id, 'institution_id' => $institution->id]], $actor);

    expect(User::findOrFail($user->id)->roles->count())->toBeGreaterThan(0);
});

test('sync with empty roles removes all roles', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $actor = User::factory()->create(['is_admin' => true]);
    $role = Role::create(['name' => ['en' => 'Editor'], 'description' => ['en' => 'Edit']]);
    $user->roles()->attach($role->id, ['institution_id' => $institution->id]);

    $synchronizer = app(UserRoleSynchronizer::class);
    $synchronizer->sync($user, [], $actor);

    expect(User::findOrFail($user->id)->roles->count())->toBe(0);
});

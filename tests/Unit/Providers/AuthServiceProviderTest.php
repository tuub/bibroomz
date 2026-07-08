<?php

declare(strict_types=1);

use App\Auth\AlmaUserProvider;
use App\Models\Institution;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

covers(AuthServiceProvider::class);

uses(RefreshDatabase::class);

test('auth service provider registers view-admin-panel gate', function (): void {
    expect(Gate::has('view-admin-panel'))->toBeTrue();
});

test('auth service provider registers viewPulse gate', function (): void {
    expect(Gate::has('viewPulse'))->toBeTrue();
});

test('view-admin-panel gate returns true when user has permissions', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $institution, 'view_users');
    $user->load('roles.permissions');

    $result = Gate::forUser($user)->allows('view-admin-panel');

    expect($result)->toBeTrue();
});

test('view-admin-panel gate returns false when user has no permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $user->load('roles.permissions');

    $result = Gate::forUser($user)->allows('view-admin-panel');

    expect($result)->toBeFalse();
});

test('Gate::before allows admin user on any ability', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $result = Gate::forUser($admin)->allows('some-arbitrary-ability');

    expect($result)->toBeTrue();
});

test('view-admin-panel gate returns true for admin user without permissions', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $admin->load('roles.permissions');

    $result = Gate::forUser($admin)->allows('view-admin-panel');

    expect($result)->toBeTrue();
});

test('Gate::before does not grant non-admin user on ability they lack', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $result = Gate::forUser($user)->allows('some-arbitrary-ability');

    expect($result)->toBeFalse();
});

test('Gate::before grants user with matching role permission', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $institution, 'view_users');
    $user->load('roles.permissions');

    $result = Gate::forUser($user)->allows('view_users', $institution);

    expect($result)->toBeTrue();
});

test('Gate::before returns null for user without matching role permission', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $user->load('roles.permissions');

    $result = Gate::forUser($user)->allows('view_users', $institution);

    expect($result)->toBeFalse();
});

test('Gate::before grants global permissions when the first argument is not an institution', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $institution, 'delete_roles');
    $user->load('roles.permissions');

    expect(Gate::forUser($user)->allows('delete_roles'))->toBeTrue();
});

test('Gate::before does not grant global permissions a user does not have', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create(['is_admin' => false]);
    $user->load('roles.permissions');

    expect(Gate::forUser($user)->allows('delete_roles'))->toBeFalse();
});

test('Gate::before does not treat one institution permission as valid for another institution', function (): void {
    $this->seed(PermissionSeeder::class);
    $grantedInstitution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);

    grantAdminPermission($user, $grantedInstitution, 'view_users');
    $user->load('roles.permissions');

    expect(Gate::forUser($user)->allows('view_users', $otherInstitution))->toBeFalse();
});

test('viewPulse gate allows admin users', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $result = Gate::forUser($admin)->allows('viewPulse');

    expect($result)->toBeTrue();
});

test('viewPulse gate denies non-admin users', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $result = Gate::forUser($user)->allows('viewPulse');

    expect($result)->toBeFalse();
});

test('alma auth provider is registered and can be resolved', function (): void {
    $provider = Auth::createUserProvider('users');

    expect($provider)->toBeInstanceOf(AlmaUserProvider::class);
});

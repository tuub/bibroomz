<?php

covers(App\Providers\AuthServiceProvider::class);

use App\Auth\AlmaUserProvider;
use App\Models\Institution;
use App\Models\User;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\InteractsWithPermissions;

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedPermissions();
    (new AuthServiceProvider(app()))->boot();
});

test('auth service provider registers the alma provider and authorization gates', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->grantPermission($user, $institution, 'view_users');
    Gate::define('always-null', fn (): ?bool => null);

    expect(Auth::createUserProvider('users'))->toBeInstanceOf(AlmaUserProvider::class)
        ->and(Gate::forUser($user)->allows('view-admin-panel'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view_users', $institution))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewPulse'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('always-null'))->toBeTrue();
});

test('auth service provider denies admin panel access without permissions', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('view-admin-panel'))->toBeFalse();
});

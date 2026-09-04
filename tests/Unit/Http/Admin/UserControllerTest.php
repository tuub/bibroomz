<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\UnbanUserRequest;
use App\Http\Requests\Admin\UserIdRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\Admin\UserAdminService;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(UserController::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('user controller renders the index payload after authorization', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);

    $this->actingAs($actor);
    $service->shouldReceive('getIndexData')->once()->andReturn(['users' => []]);

    $response = (new UserController($service))->getUsers();

    expect($response)->toBeInstanceOf(Response::class);
});

test('user controller form users query only returns id name and admin flag', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $first = User::factory()->create([
        'name' => 'alpha',
        'email' => 'alpha@example.test',
        'is_admin' => true,
    ]);
    User::factory()->create([
        'name' => 'beta',
        'email' => 'beta@example.test',
        'is_admin' => false,
    ]);

    $this->actingAs($actor);
    $controller = new UserController(Mockery::mock(UserAdminService::class));
    $users = $controller->getFormUsers();
    $attributes = $users->firstWhere('id', $first->id) ?? [];

    expect($users)->toHaveCount(3)
        ->and($attributes)->toHaveKey('id')
        ->and($attributes)->toHaveKey('name')
        ->and($attributes)->toHaveKey('is_admin')
        ->and($attributes)->not->toHaveKey('email');
});

// getFormUsers() bypasses Eloquent's 'is_admin' => 'boolean' cast by querying through the
// query builder directly, so it casts is_admin by hand. Without that cast, the raw driver
// value (an int or numeric string) would leak into the form payload instead of a real bool.
test('user controller form users casts is_admin to a real boolean for both admins and non-admins', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $admin = User::factory()->create(['is_admin' => true]);
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    $this->actingAs($actor);
    $controller = new UserController(Mockery::mock(UserAdminService::class));
    $users = $controller->getFormUsers();

    $adminAttributes = $users->firstWhere('id', $admin->id);
    $nonAdminAttributes = $users->firstWhere('id', $nonAdmin->id);

    expect($adminAttributes['is_admin'])->toBeBool()->toBeTrue()
        ->and($nonAdminAttributes['is_admin'])->toBeBool()->toBeFalse();
});

// The id/name type guard in getFormUsers() cannot be triggered through the real
// database (both columns are string-typed), so the raw query result is mocked here to force
// the defensive RuntimeException path.
test('user controller form users throws when a row has a non-string id or name', function (): void {
    $actor = User::factory()->create(['is_admin' => true]);
    $this->actingAs($actor);

    $builder = Mockery::mock(Builder::class);
    DB::shouldReceive('table')->once()->with('users')->andReturn($builder);
    $builder->shouldReceive('select')->once()->with(['id', 'name', 'is_admin'])->andReturnSelf();
    $builder->shouldReceive('get')->once()->andReturn(collect([
        (object) ['id' => 123, 'name' => 'not-a-string-id', 'is_admin' => 0],
    ]));

    $controller = new UserController(Mockery::mock(UserAdminService::class));

    expect(fn (): Collection => $controller->getFormUsers())
        ->toThrow(RuntimeException::class, 'Unexpected non-string id or name column value.');
});

test('user controller renders the create form for the authenticated admin user', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);

    $this->actingAs($actor);
    $service->shouldReceive('getCreateFormData')->once()->with($actor)->andReturn(['users' => []]);

    $response = (new UserController($service))->createUser();

    expect($response)->toBeInstanceOf(Response::class);
});

test('user controller stores a user and redirects to the user index', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $request = Mockery::mock(UserRequest::class);
    $userData = ['name' => 'created.user'];
    $roles = [['institution_id' => 'institution-1', 'role_id' => 'role-1']];

    $this->actingAs($actor);
    $request->shouldReceive('userData')->once()->andReturn($userData);
    $request->shouldReceive('roles')->once()->andReturn($roles);
    $service->shouldReceive('store')->once()->with($userData, $roles, $actor);

    $response = (new UserController($service))->storeUser($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.user.index'));
});

test('user controller renders the edit form for an authorized user', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $target = User::factory()->create();
    $request = Mockery::mock(UserIdRequest::class);

    $this->actingAs($actor);
    $request->shouldReceive('targetUser')->once()->andReturn($target);
    $service->shouldReceive('getEditFormData')->once()->with($target, $actor)->andReturn(['user' => []]);

    $response = (new UserController($service))->editUser($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('user controller updates a user and redirects to the user index', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $target = User::factory()->create();
    $request = Mockery::mock(UserRequest::class);
    $userData = ['name' => 'updated.user'];
    $roles = [['institution_id' => 'institution-1', 'role_id' => 'role-1']];

    $this->actingAs($actor);
    $request->shouldReceive('targetUser')->once()->andReturn($target);
    $request->shouldReceive('userData')->once()->andReturn($userData);
    $request->shouldReceive('roles')->once()->andReturn($roles);
    $service->shouldReceive('update')->once()->with($target, $userData, $roles, $actor);

    $response = (new UserController($service))->updateUser($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.user.index'));
});

test('user controller deletes a user and redirects to the user index', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $target = User::factory()->create();
    $request = Mockery::mock(DeleteUserRequest::class);

    $request->shouldReceive('targetUser')->once()->andReturn($target);
    $service->shouldReceive('delete')->once()->with($target);

    $response = (new UserController($service))->deleteUser($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.user.index'));
});

test('user controller bans and unbans users through the admin service', function (): void {
    $service = Mockery::mock(UserAdminService::class);
    $target = User::factory()->create();
    $banRequest = Mockery::mock(BanUserRequest::class);
    $unbanRequest = Mockery::mock(UnbanUserRequest::class);

    $banRequest->shouldReceive('targetUser')->once()->andReturn($target);
    $unbanRequest->shouldReceive('targetUser')->once()->andReturn($target);
    $service->shouldReceive('ban')->once()->with($target);
    $service->shouldReceive('unban')->once()->with($target);

    $controller = new UserController($service);
    $banResponse = $controller->banUser($banRequest);
    $unbanResponse = $controller->unbanUser($unbanRequest);

    expect($banResponse)->toBeInstanceOf(RedirectResponse::class)
        ->and($banResponse->getTargetUrl())->toBe(route('admin.user.index'))
        ->and($unbanResponse)->toBeInstanceOf(RedirectResponse::class)
        ->and($unbanResponse->getTargetUrl())->toBe(route('admin.user.index'));
});

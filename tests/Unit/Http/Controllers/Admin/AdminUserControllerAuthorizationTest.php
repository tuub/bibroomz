<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Requests\Admin\UserIdRequest;
use App\Models\User;
use App\Services\Admin\UserAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(AdminUserController::class);

uses(MockeryPHPUnitIntegration::class);

test('getUsers enforces authorization when no user is authenticated', function (): void {
    $controller = new AdminUserController(Mockery::mock(UserAdminService::class));

    expect(fn (): Response => $controller->getUsers())->toThrow(AuthorizationException::class);
});

test('createUser enforces authorization when no user is authenticated', function (): void {
    $controller = new AdminUserController(Mockery::mock(UserAdminService::class));

    expect(fn (): Response => $controller->createUser())->toThrow(AuthorizationException::class);
});

test('editUser enforces authorization when no user is authenticated', function (): void {
    $user = new User;

    $request = Mockery::mock(UserIdRequest::class);
    $request->shouldReceive('targetUser')->andReturn($user);

    $controller = new AdminUserController(Mockery::mock(UserAdminService::class));

    expect(fn (): Response => $controller->editUser($request))->toThrow(AuthorizationException::class);
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\RoleController;
use App\Http\Requests\Admin\RoleIdRequest;
use App\Models\Role;
use App\Services\Admin\RoleAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(RoleController::class);

uses(MockeryPHPUnitIntegration::class);

test('getRoles enforces authorization when no user is authenticated', function (): void {
    $controller = new RoleController(Mockery::mock(RoleAdminService::class));

    expect(fn (): Response => $controller->getRoles())->toThrow(AuthorizationException::class);
});

test('createRole enforces authorization when no user is authenticated', function (): void {
    $controller = new RoleController(Mockery::mock(RoleAdminService::class));

    expect(fn (): Response => $controller->createRole())->toThrow(AuthorizationException::class);
});

test('editRole enforces authorization when no user is authenticated', function (): void {
    $role = new Role;

    $request = Mockery::mock(RoleIdRequest::class);
    $request->shouldReceive('role')->andReturn($role);

    $controller = new RoleController(Mockery::mock(RoleAdminService::class));

    expect(fn (): Response => $controller->editRole($request))->toThrow(AuthorizationException::class);
});

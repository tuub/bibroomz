<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\HappeningController as AdminHappeningController;
use App\Http\Requests\Admin\HappeningIdRequest;
use App\Models\Happening;
use App\Services\Admin\HappeningAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(AdminHappeningController::class);

uses(MockeryPHPUnitIntegration::class);

test('getHappenings enforces authorization when no user is authenticated', function (): void {
    $controller = new AdminHappeningController(Mockery::mock(HappeningAdminService::class));

    expect(fn (): Response => $controller->getHappenings())->toThrow(AuthorizationException::class);
});

test('createHappening enforces authorization when no user is authenticated', function (): void {
    $controller = new AdminHappeningController(Mockery::mock(HappeningAdminService::class));

    expect(fn (): Response => $controller->createHappening())->toThrow(AuthorizationException::class);
});

test('editHappening enforces authorization when no user is authenticated', function (): void {
    $happening = new Happening;

    $request = Mockery::mock(HappeningIdRequest::class);
    $request->shouldReceive('happening')->andReturn($happening);

    $controller = new AdminHappeningController(Mockery::mock(HappeningAdminService::class));

    expect(fn (): Response => $controller->editHappening($request))->toThrow(AuthorizationException::class);
});

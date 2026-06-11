<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ClosingController;
use App\Http\Requests\Admin\ClosableContextRequest;
use App\Http\Requests\Admin\ClosingIdRequest;
use App\Models\Closing;
use App\Models\Institution;
use App\Services\Admin\ClosingAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ClosingController::class);

uses(MockeryPHPUnitIntegration::class);

test('getClosings enforces authorization when no user is authenticated', function (): void {
    $closable = new Institution;

    $service = Mockery::mock(ClosingAdminService::class);
    $service->shouldReceive('resolveClosable')->andReturn($closable);

    $request = Mockery::mock(ClosableContextRequest::class);
    $request->shouldReceive('closableType')->andReturn('institution');
    $request->shouldReceive('closableId')->andReturn(1);

    $controller = new ClosingController($service);

    expect(fn (): Response => $controller->getClosings($request))->toThrow(AuthorizationException::class);
});

test('createClosing enforces authorization when no user is authenticated', function (): void {
    $closable = new Institution;

    $service = Mockery::mock(ClosingAdminService::class);
    $service->shouldReceive('resolveClosable')->andReturn($closable);

    $request = Mockery::mock(ClosableContextRequest::class);
    $request->shouldReceive('closableType')->andReturn('institution');
    $request->shouldReceive('closableId')->andReturn(1);

    $controller = new ClosingController($service);

    expect(fn (): Response => $controller->createClosing($request))->toThrow(AuthorizationException::class);
});

test('editClosing enforces authorization when no user is authenticated', function (): void {
    $closing = new Closing;

    $request = Mockery::mock(ClosingIdRequest::class);
    $request->shouldReceive('closing')->andReturn($closing);

    $controller = new ClosingController(Mockery::mock(ClosingAdminService::class));

    expect(fn (): Response => $controller->editClosing($request))->toThrow(AuthorizationException::class);
});

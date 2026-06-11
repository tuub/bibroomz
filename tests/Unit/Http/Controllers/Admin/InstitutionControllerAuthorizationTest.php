<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Requests\Admin\InstitutionIdRequest;
use App\Http\Requests\Admin\InstitutionRequest;
use App\Models\Institution;
use App\Services\Admin\InstitutionAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(InstitutionController::class);

uses(MockeryPHPUnitIntegration::class);

test('createInstitution enforces authorization when no user is authenticated', function (): void {
    $controller = new InstitutionController(Mockery::mock(InstitutionAdminService::class));

    expect(fn (): Response => $controller->createInstitution())->toThrow(AuthorizationException::class);
});

test('storeInstitution enforces authorization when no user is authenticated', function (): void {
    $request = Mockery::mock(InstitutionRequest::class);

    $controller = new InstitutionController(Mockery::mock(InstitutionAdminService::class));

    expect(fn (): RedirectResponse => $controller->storeInstitution($request))->toThrow(AuthorizationException::class);
});

test('editInstitution enforces authorization when no user is authenticated', function (): void {
    $institution = Mockery::mock(Institution::class);
    $institution->shouldReceive('load')->andReturnSelf();

    $request = Mockery::mock(InstitutionIdRequest::class);
    $request->shouldReceive('institution')->andReturn($institution);

    $controller = new InstitutionController(Mockery::mock(InstitutionAdminService::class));

    expect(fn (): Response => $controller->editInstitution($request))->toThrow(AuthorizationException::class);
});

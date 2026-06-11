<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ResourceGroupController;
use App\Http\Requests\Admin\InstitutionContextRequest;
use App\Http\Requests\Admin\ResourceGroupIdRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Admin\ResourceGroupAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ResourceGroupController::class);

uses(MockeryPHPUnitIntegration::class);

test('getResourceGroups enforces authorization when no user is authenticated', function (): void {
    $institution = new Institution;

    $request = Mockery::mock(InstitutionContextRequest::class);
    $request->shouldReceive('institution')->andReturn($institution);

    $controller = new ResourceGroupController(Mockery::mock(ResourceGroupAdminService::class));

    expect(fn (): Response => $controller->getResourceGroups($request))->toThrow(AuthorizationException::class);
});

test('createResourceGroup enforces authorization when no user is authenticated', function (): void {
    $institution = new Institution;

    $request = Mockery::mock(InstitutionContextRequest::class);
    $request->shouldReceive('institution')->andReturn($institution);

    $controller = new ResourceGroupController(Mockery::mock(ResourceGroupAdminService::class));

    expect(fn (): Response => $controller->createResourceGroup($request))->toThrow(AuthorizationException::class);
});

test('editResourceGroup enforces authorization when no user is authenticated', function (): void {
    $resourceGroup = Mockery::mock(ResourceGroup::class);
    $resourceGroup->shouldReceive('load')->andReturnSelf();

    $request = Mockery::mock(ResourceGroupIdRequest::class);
    $request->shouldReceive('resourceGroup')->andReturn($resourceGroup);

    $controller = new ResourceGroupController(Mockery::mock(ResourceGroupAdminService::class));

    expect(fn (): Response => $controller->editResourceGroup($request))->toThrow(AuthorizationException::class);
});

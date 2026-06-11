<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ResourceController;
use App\Http\Requests\Admin\ResourceGroupContextRequest;
use App\Http\Requests\Admin\ResourceIdRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\ResourceAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ResourceController::class);

uses(MockeryPHPUnitIntegration::class);

test('createResource enforces authorization when no user is authenticated', function (): void {
    $resourceGroup = Mockery::mock(ResourceGroup::class);
    $resourceGroup->shouldReceive('load')->andReturnSelf();
    $resourceGroup->shouldReceive('getAttribute')->with('institution')->andReturn(new Institution);

    $request = Mockery::mock(ResourceGroupContextRequest::class);
    $request->shouldReceive('resourceGroup')->andReturn($resourceGroup);

    $controller = new ResourceController(Mockery::mock(ResourceAdminService::class));

    expect(fn (): Response => $controller->createResource($request))->toThrow(AuthorizationException::class);
});

test('editResource enforces authorization when no user is authenticated', function (): void {
    $resource = Mockery::mock(Resource::class);
    $resource->shouldReceive('load')->andReturnSelf();

    $request = Mockery::mock(ResourceIdRequest::class);
    $request->shouldReceive('resource')->andReturn($resource);

    $controller = new ResourceController(Mockery::mock(ResourceAdminService::class));

    expect(fn (): Response => $controller->editResource($request))->toThrow(AuthorizationException::class);
});

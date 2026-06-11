<?php

use App\Http\Controllers\Admin\ResourceController;
use App\Http\Requests\Admin\CloneResourceRequest;
use App\Http\Requests\Admin\DeleteResourceRequest;
use App\Http\Requests\Admin\ResourceGroupContextRequest;
use App\Http\Requests\Admin\ResourceIdRequest;
use App\Http\Requests\Admin\ResourceOrderRequest;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Admin\ResourceAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ResourceController::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function createAdminResourceFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    return [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
    ];
}

test('resource controller renders the index data for a resource group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(ResourceGroupContextRequest::class);

    $service->shouldReceive('getIndexData')->once()->with($resourceGroup->id)->andReturn(['resources' => []]);
    $request->shouldReceive('resourceGroup')->once()->andReturn($resourceGroup);

    $response = (new ResourceController($service))->getResources($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('resource controller forwards sort payloads to the admin service', function (): void {
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(ResourceOrderRequest::class);
    $rows = collect([['id' => 'first'], ['id' => 'second']]);

    $service->shouldReceive('reorder')->once()->with($rows->all());
    $request->shouldReceive('rows')->once()->andReturn($rows);

    (new ResourceController($service))->orderResources($request);

    expect($rows->count())->toBe(2);
});

test('resource controller renders the create form after authorization', function (): void {
    ['institution' => $institution, 'resourceGroup' => $resourceGroup] = createAdminResourceFixture();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(ResourceGroupContextRequest::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);

    $this->actingAs($actor);
    $request->shouldReceive('resourceGroup')->once()->andReturn($resourceGroup);
    $service->shouldReceive('getCreateFormData')->once()->with(Mockery::on(
        fn (ResourceGroup $group): bool => $group->is($resourceGroup) && $group->relationLoaded('institution'),
    ))->andReturn(['resourceGroup' => []]);

    $response = (new ResourceController($service))->createResource($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('resource controller stores a resource and redirects back to the resource group index', function (): void {
    ['resource' => $resource] = createAdminResourceFixture();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(StoreResourceRequest::class);
    $resourceData = ['title' => ['en' => 'Desk']];
    $businessHours = [['start' => '08:00', 'end' => '18:00']];

    $request->shouldReceive('resourceData')->once()->andReturn($resourceData);
    $request->shouldReceive('businessHours')->once()->andReturn($businessHours);
    $service->shouldReceive('store')->once()->with($resourceData, $businessHours)->andReturn($resource);

    $response = (new ResourceController($service))->storeResource($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]));
});

test('resource controller renders the edit form for an authorized resource', function (): void {
    ['resource' => $resource] = createAdminResourceFixture();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(ResourceIdRequest::class);
    $actor = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);

    $this->actingAs($actor);
    $request->shouldReceive('resource')->once()->andReturn($resource);
    $service->shouldReceive('getEditFormData')->once()->with(Mockery::on(
        fn (Resource $resolved): bool => $resolved->is($resource),
    ))->andReturn(['resource' => []]);

    $response = (new ResourceController($service))->editResource($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('resource controller updates a resource and redirects to its index', function (): void {
    ['resource' => $resource] = createAdminResourceFixture();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(UpdateResourceRequest::class);
    $resourceData = ['title' => ['en' => 'Updated']];
    $businessHours = [['start' => '09:00', 'end' => '17:00']];

    $request->shouldReceive('resource')->once()->andReturn($resource);
    $request->shouldReceive('resourceData')->once()->andReturn($resourceData);
    $request->shouldReceive('businessHours')->once()->andReturn($businessHours);
    $service->shouldReceive('update')->once()->with($resource, $resourceData, $businessHours);

    $response = (new ResourceController($service))->updateResource($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]));
});

test('resource controller deletes a resource and redirects to its index', function (): void {
    ['resource' => $resource] = createAdminResourceFixture();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(DeleteResourceRequest::class);

    $request->shouldReceive('resource')->once()->andReturn($resource);
    $service->shouldReceive('delete')->once()->with($resource);

    $response = (new ResourceController($service))->deleteResource($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]));
});

test('resource controller clones a resource and redirects to the copied resource form', function (): void {
    ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createAdminResourceFixture();
    $resourceCopy = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $service = Mockery::mock(ResourceAdminService::class);
    $request = Mockery::mock(CloneResourceRequest::class);

    $request->shouldReceive('resource')->once()->andReturn($resource);
    $service->shouldReceive('clone')->once()->with(Mockery::on(
        fn (Resource $resolved): bool => $resolved->is($resource),
    ))->andReturn($resourceCopy);

    $response = (new ResourceController($service))->cloneResource($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('admin.resource.edit', $resourceCopy->id));
});

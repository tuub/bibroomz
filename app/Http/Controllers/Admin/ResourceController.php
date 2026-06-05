<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CloneResourceRequest;
use App\Http\Requests\Admin\DeleteResourceRequest;
use App\Http\Requests\Admin\ResourceGroupContextRequest;
use App\Http\Requests\Admin\ResourceIdRequest;
use App\Http\Requests\Admin\ResourceOrderRequest;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Models\Resource;
use App\Services\Admin\ResourceAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends AdminController
{
    public function __construct(private ResourceAdminService $resourceAdminService)
    {
    }

    public function getResources(ResourceGroupContextRequest $request): Response
    {
        return Inertia::render('Admin/Resources/Index', $this->resourceAdminService->getIndexData(
            $request->resourceGroup()->id,
        ));
    }

    public function orderResources(ResourceOrderRequest $request): void
    {
        $this->resourceAdminService->reorder($request->rows()->all());
    }

    public function createResource(ResourceGroupContextRequest $request): Response
    {
        $resourceGroup = $request->resourceGroup()->load('institution');
        $this->authorize('create', [Resource::class, $resourceGroup->institution]);

        return Inertia::render('Admin/Resources/Form', $this->resourceAdminService->getCreateFormData($resourceGroup));
    }

    public function storeResource(StoreResourceRequest $request): RedirectResponse
    {
        $resource = $this->resourceAdminService->store(
            $request->resourceData(),
            $request->businessHours(),
        );

        return redirect()->route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]);
    }

    public function editResource(ResourceIdRequest $request): Response
    {
        $resource = $request->resource()->load(['business_hours', 'business_hours.week_days:id']);

        $this->authorize('edit', $resource);

        return Inertia::render('Admin/Resources/Form', $this->resourceAdminService->getEditFormData($resource));
    }

    public function updateResource(UpdateResourceRequest $request): RedirectResponse
    {
        $resource = $request->resource();
        $this->resourceAdminService->update(
            $resource,
            $request->resourceData(),
            $request->businessHours(),
        );

        return redirect()->route('admin.resource.index', [
            'resource_group_id' => $resource->resource_group_id,
        ]);
    }

    public function deleteResource(DeleteResourceRequest $request): RedirectResponse
    {
        $resource = $request->resource();
        $this->resourceAdminService->delete($resource);

        return redirect()->route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]);
    }

    public function cloneResource(CloneResourceRequest $request): RedirectResponse
    {
        $resource = $request->resource()->load(
            'resource_group',
            'closings',
            'business_hours',
            'business_hours.week_days',
        );

        $resourceCopy = $this->resourceAdminService->clone($resource);

        return redirect()->route('admin.resource.edit', $resourceCopy->id);
    }
}

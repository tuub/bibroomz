<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteResourceGroupRequest;
use App\Http\Requests\Admin\InstitutionContextRequest;
use App\Http\Requests\Admin\ResourceGroupIdRequest;
use App\Http\Requests\Admin\ResourceGroupOrderRequest;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Models\ResourceGroup;
use App\Services\Admin\ResourceGroupAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ResourceGroupController extends AdminController
{
    public function __construct(private readonly ResourceGroupAdminService $resourceGroupAdminService) {}

    public function getResourceGroups(InstitutionContextRequest $request): Response
    {
        $institution = $request->institution();

        $this->authorize('viewAny', [ResourceGroup::class, $institution]);

        return Inertia::render('Admin/ResourceGroups/Index', $this->resourceGroupAdminService->getIndexData(
            $institution,
        ));
    }

    public function orderResourceGroups(ResourceGroupOrderRequest $request): void
    {
        $this->resourceGroupAdminService->reorder($request->rows()->all());
    }

    public function createResourceGroup(InstitutionContextRequest $request): Response
    {
        $institution = $request->institution();
        $this->authorize('create', [ResourceGroup::class, $institution]);

        return Inertia::render('Admin/ResourceGroups/Form', $this->resourceGroupAdminService->getCreateFormData(
            $institution,
            $this->authenticatedUser(),
        ));
    }

    public function storeResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $rg = $this->resourceGroupAdminService->store($request->validated());

        return redirect()->route('admin.resource_group.index', [
            'institution_id' => $rg->institution_id,
        ]);
    }

    public function editResourceGroup(ResourceGroupIdRequest $request): Response
    {
        $resourceGroup = $request->resourceGroup()->load('user_groups');
        $this->authorize('edit', $resourceGroup);

        return Inertia::render('Admin/ResourceGroups/Form', $this->resourceGroupAdminService->getEditFormData(
            $resourceGroup,
            $this->authenticatedUser(),
        ));
    }

    public function updateResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $resourceGroup = $request->resourceGroup();
        $updated = $this->resourceGroupAdminService->update($resourceGroup, $request->validated());

        return redirect()->route('admin.resource_group.index', [
            'institution_id' => $updated->institution_id,
        ]);
    }

    public function deleteResourceGroup(DeleteResourceGroupRequest $request): RedirectResponse
    {
        $resourceGroup = $request->resourceGroup()->load('user_groups');
        $this->resourceGroupAdminService->delete($resourceGroup);

        return redirect()->route('admin.resource_group.index', [
            'institution_id' => $resourceGroup->institution_id,
        ]);
    }
}

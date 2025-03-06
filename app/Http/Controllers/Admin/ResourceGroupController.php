<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Services\AdminLoggingService;
use App\Services\ResourceGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceGroupController extends Controller
{
    public function __construct(
        private AdminLoggingService $adminLoggingService,
        private ResourceGroupService $resourceGroupService
    ) {
    }

    public function getResourceGroups(): Response
    {
        $user = auth()->user();

        $rgs = $this->resourceGroupService->getResourceGroupsForUser($user);

        return Inertia::render('Admin/ResourceGroups/Index', [
            'resource_groups' => $rgs,
        ]);
    }

    public function createResourceGroup(): Response
    {
        $user = auth()->user();

        $institutions = $this->resourceGroupService->getInstitutionsForUser($user);

        return Inertia::render('Admin/ResourceGroups/Form', [
            'institutions' => $institutions,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rg = $this->resourceGroupService->storeResourceGroup($validated);

        $this->adminLoggingService->log('created', $rg);

        return redirect()->route('admin.resource_group.index');
    }

    public function editResourceGroup(Request $request)
    {
        $id = $request->id;
        $user = auth()->user();

        $rg = $this->resourceGroupService->getResourceGroupById($id);
        $institutions = $this->resourceGroupService->getInstitutionsForUser($user);

        return Inertia::render('Admin/ResourceGroups/Form', [
            'resource_group' => $rg,
            'institutions' => $institutions,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $id = $request->id;
        $validated = $request->validated();

        $rg = $this->resourceGroupService->updateResourceGroup($id, $validated);

        $this->adminLoggingService->log('updated', $rg);

        return redirect()->route('admin.resource_group.index');
    }

    public function deleteResourceGroup(Request $request): RedirectResponse
    {
        $id = $request->id;

        $rg = $this->resourceGroupService->deleteResourceGroup($id);

        $this->adminLoggingService->log('deleted', $rg);

        return redirect()->route('admin.resource_group.index');
    }
}

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
use App\Models\Institution;
use App\Models\ResourceGroup;

class ResourceGroupController extends Controller
{
    public function __construct(
        private AdminLoggingService $adminLoggingService,
        private ResourceGroupService $resourceGroupService
    ) {
    }

    public function getResourceGroups(Request $request): Response
    {
        $resource_groups = ResourceGroup::with(['resources'])
            ->where('institution_id', $request->institution_id)
            ->orderBy('order')
            ->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/ResourceGroups/Index', [
            'institution' => Institution::findOrFail($request->institution_id),
            'resource_groups' => $resource_groups,
        ]);
    }

    public function orderResourceGroups(Request $request): void
    {
        foreach ($request->input() as $row) {
            $resource_group = ResourceGroup::findOrFail($row['id']);
            $resource_group->update([
                'order' => $row['order'],
            ]);
            $this->adminLoggingService->log('reordered resource group', $resource_group);
        }
    }

    public function createResourceGroup(Request $request): Response
    {
        return Inertia::render('Admin/ResourceGroups/Form', [
            'institution' => Institution::findOrFail($request->institution_id),
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

        return redirect()->route('admin.resource_group.index', [
            'institution_id' => $request->institution_id,
        ]);
    }

    public function deleteResourceGroup(Request $request): RedirectResponse
    {
        $id = $request->id;

        $rg = $this->resourceGroupService->deleteResourceGroup($id);

        $this->adminLoggingService->log('deleted', $rg);

        return redirect()->route('admin.resource_group.index');
    }
}

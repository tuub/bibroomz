<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
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

    public function getResourceGroups(Request $request): Response
    {
        $institution = Institution::findOrFail($request->institution_id);

        $this->authorize('viewAny', [ResourceGroup::class, $institution]);

        $resource_groups = ResourceGroup::with(['resources'])
            ->withCount('resources')
            ->where('institution_id', $request->institution_id)
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/ResourceGroups/Index', [
            'institution' => $institution,
            'resource_groups' => $resource_groups,
        ]);
    }

    public function orderResourceGroups(Request $request): void
    {
        $validated = $request->validate([
            '*.id' => ['required', 'uuid', 'exists:resource_groups,id'],
            '*.order' => ['required', 'integer'],
        ]);

        foreach ($request->input() as $row) {
            $resource_group = ResourceGroup::findOrFail($row['id']);
            $this->authorize('update', $resource_group);
            $resource_group->update([
                'order' => $row['order'],
            ]);
            $this->adminLoggingService->log('reordered resource group', $resource_group);
        }
    }

    public function createResourceGroup(Request $request): Response
    {
        $institution = Institution::findOrFail($request->institution_id);
        $this->authorize('create', [ResourceGroup::class, $institution]);

        return Inertia::render('Admin/ResourceGroups/Form', [
            'institution' => $institution,
            'institutions' => $this->resourceGroupService->getInstitutionsForUser(auth()->user()),
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rg = $this->resourceGroupService->storeResourceGroup($validated);

        $this->adminLoggingService->log('created', $rg);

        return redirect()->route('admin.resource_group.index', [
            'institution_id' => $rg->institution_id,
        ]);
    }

    public function editResourceGroup(Request $request)
    {
        $id = $request->id;
        $user = auth()->user();

        $rg = $this->resourceGroupService->getResourceGroupById($id);
        $this->authorize('edit', $rg);
        $rg->loadMissing('institution.user_groups');

        $institutions = $this->resourceGroupService->getInstitutionsForUser($user)
            ->prepend($rg->institution)
            ->unique('id')
            ->values();

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

        $rg = $this->resourceGroupService->getResourceGroupById($id);
        $this->authorize('delete', $rg);

        $this->resourceGroupService->deleteResourceGroup($id);

        $this->adminLoggingService->log('deleted', $rg);

        return redirect()->route('admin.resource_group.index', [
            'institution_id' => $rg->institution_id,
        ]);
    }
}

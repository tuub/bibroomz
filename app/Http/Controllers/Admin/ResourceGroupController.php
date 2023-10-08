<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\WeekDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceGroupController extends Controller
{
    public function getResourceGroups(): Response
    {
        $resource_groups = ResourceGroup::with(['institution'])
            ->orderBy('institution_id')->orderBy('name')->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/ResourceGroups/Index', [
            'resource_groups' => $resource_groups,
        ]);
    }

    public function createResourceGroup(): Response
    {
        $institutions = Institution::active()->orderBy('title')->without('closings')->get()
            ->filter->isUserAbleToCreateResourceGroup(auth()->user());

        return Inertia::render('Admin/ResourceGroups/Form', [
            'institutions' => $institutions,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        ResourceGroup::create($validated);

        return redirect()->route('admin.resource_group.index');
    }

    public function editResourceGroup(Request $request)
    {
        $resource_group = ResourceGroup::where('id', $request->id)->firstOrFail();

        $institutions = Institution::active()->orderBy('title')->without('closings')->get()
            ->filter->isUserAbleToCreateResource(auth()->user());

        return Inertia::render('Admin/ResourceGroups/Form', [
            'resource_group' => $resource_group,
            'institutions' => $institutions,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateResourceGroup(ResourceGroupRequest $request): RedirectResponse
    {
        $resource_group = ResourceGroup::where('id', $request->id)->firstOrFail();

        $validated = $request->validated();
        $resource_group->update($validated);

        return redirect()->route('admin.resource_group.index');
    }

    public function deleteResourceGroup(Request $request): RedirectResponse
    {
        $resource_group = ResourceGroup::where('id', $request->id)->firstOrFail();
        $resource_group->delete();

        return redirect()->route('admin.resource_group.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionGroupRequest;
use App\Models\PermissionGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PermissionGroupController extends Controller
{
    public function getPermissionGroups(): Response
    {
        $this->authorize('viewAny', PermissionGroup::class);

        return Inertia::render('Admin/PermissionGroups/Index', [
            'permissionGroups' => PermissionGroup::orderBy('name')->get()
        ]);
    }

    public function editPermissionGroup(PermissionGroup $permission_group): Response
    {
        $this->authorize('edit', $permission_group);

        return Inertia::render('Admin/PermissionGroups/Form', [
            'permissionGroup' => $permission_group,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updatePermissionGroup(PermissionGroupRequest $request): RedirectResponse
    {
        $permission_group = PermissionGroup::find($request->id);
        $this->authorize('edit', $permission_group->id);

        $permission_group->update($request->validated());

        return redirect()->route('admin.permission_group.index');
    }
}

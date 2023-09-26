<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function getPermissions()
    {
        $this->authorize('viewAny', Permission::class);

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::with(['roles'])->orderBy('name')->get()
        ]);
    }

    public function editPermission(Permission $permission)
    {
        $this->authorize('edit', $permission);

        return Inertia::render('Admin/Permissions/Form', [
            'permission' => $permission,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updatePermission(Permission $permission, PermissionRequest $request): RedirectResponse
    {
        $this->authorize('edit', $permission);

        $permission->update($request->validated());

        return redirect()->route('admin.permission.index');
    }
}

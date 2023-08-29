<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function createPermission(): Response
    {
        $this->authorize('create', Permission::class);

        return Inertia::render('Admin/Permissions/Form');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $this->authorize('create', Permission::class);

        Permission::create($request->only('name', 'description'));

        return redirect()->route('admin.permission.index');
    }

    public function editPermission(Permission $permission)
    {
        $this->authorize('edit', $permission);

        return Inertia::render('Admin/Permissions/Form', [
            'permission' => $permission,
        ]);
    }

    public function updatePermission(Permission $permission, Request $request): RedirectResponse
    {
        $this->authorize('edit', $permission);

        $permission->update($request->only('name', 'description'));

        return redirect()->route('admin.permission.index');
    }

    public function deletePermission(Permission $permission): RedirectResponse
    {
        $this->authorize('delete', $permission);

        $permission->delete();

        return redirect()->route('admin.permission.index');
    }
}

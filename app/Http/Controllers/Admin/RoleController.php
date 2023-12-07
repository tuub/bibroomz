<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Services\AdminLoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private AdminLoggingService $adminLoggingService)
    {
    }

    public function getRoles()
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::with(['permissions'])->orderBy('name')->get()
        ]);
    }

    public function createRole(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Admin/Roles/Form', [
            'permissions' => Permission::orderBy('name')->get(),
            'groups' => PermissionGroup::orderBy('name')->get(),
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeRole(RoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create($request->validated());
        $role->permissions()->sync($request->permissions);

        $this->adminLoggingService->log('created', $role);

        return redirect()->route('admin.role.index');
    }

    public function editRole(Request $request)
    {
        $role = Role::findOrFail($request->id);
        $this->authorize('edit', $role);

        return Inertia::render('Admin/Roles/Form', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
            'groups' => PermissionGroup::orderBy('name')->get(),
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateRole(RoleRequest $request): RedirectResponse
    {
        $role = Role::findOrFail($request->id);
        $this->authorize('edit', $role);

        $role->update($request->validated());
        $role->permissions()->sync($request->permissions);

        $this->adminLoggingService->log('updated', $role);

        return redirect()->route('admin.role.index');
    }

    public function deleteRole(Request $request): RedirectResponse
    {
        $role = Role::findOrFail($request->id);
        $this->authorize('delete', $role);

        $role->delete();

        $this->adminLoggingService->log('deleted', $role);

        return redirect()->route('admin.role.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
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
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeRole(RoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create($request->validated());
        $role->permissions()->sync($request->permissions);

        return redirect()->route('admin.role.index');
    }

    public function editRole(Role $role)
    {
        $this->authorize('edit', $role);

        return Inertia::render('Admin/Roles/Form', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateRole(Role $role, RoleRequest $request): RedirectResponse
    {
        $this->authorize('edit', $role);

        $role->update($request->validated());
        $role->permissions()->sync($request->permissions);

        return redirect()->route('admin.role.index');
    }

    public function deleteRole(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->delete();

        return redirect()->route('admin.role.index');
    }
}

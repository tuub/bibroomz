<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create($request->only('name', 'description'));
        $role->permissions()->sync($request->permissions);

        return redirect()->route('admin.role.index');
    }

    public function editRole(Role $role)
    {
        $this->authorize('edit', $role);

        return Inertia::render('Admin/Roles/Form', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function updateRole(Role $role, Request $request): RedirectResponse
    {
        $this->authorize('edit', $role);

        $role->update($request->only('name', 'description'));
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

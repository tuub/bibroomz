<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteRoleRequest;
use App\Http\Requests\Admin\RoleIdRequest;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Services\Admin\RoleAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends AdminController
{
    public function __construct(private readonly RoleAdminService $roleAdminService) {}

    public function getRoles(): Response
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Admin/Roles/Index', $this->roleAdminService->getIndexData());
    }

    public function createRole(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Admin/Roles/Form', $this->roleAdminService->getFormData());
    }

    public function storeRole(RoleRequest $request): RedirectResponse
    {
        $this->roleAdminService->store($request->roleData(), $request->permissions());

        return redirect()->route('admin.role.index');
    }

    public function editRole(RoleIdRequest $request): Response
    {
        $role = $request->role();
        $this->authorize('edit', $role);

        return Inertia::render('Admin/Roles/Form', $this->roleAdminService->getFormData($role));
    }

    public function updateRole(RoleRequest $request): RedirectResponse
    {
        $role = $request->role();
        $this->roleAdminService->update($role, $request->roleData(), $request->permissions());

        return redirect()->route('admin.role.index');
    }

    public function deleteRole(DeleteRoleRequest $request): RedirectResponse
    {
        $role = $request->role();
        $this->roleAdminService->delete($role);

        return redirect()->route('admin.role.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PermissionRequest;
use App\Models\Permission;
use App\Services\AdminLoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function __construct(private AdminLoggingService $adminLoggingService)
    {
    }

    public function getPermissions(): Response
    {
        $this->authorize('viewAny', Permission::class);

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::with(['roles'])->orderBy('name')->get()
        ]);
    }

    public function editPermission(Request $request): Response
    {
        $permission = Permission::findOrFail($request->id);
        $this->authorize('edit', $permission);

        return Inertia::render('Admin/Permissions/Form', [
            'permission' => $permission,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updatePermission(PermissionRequest $request): RedirectResponse
    {
        $permission = Permission::find($request->id);
        $this->authorize('edit', $permission->id);

        $permission->update($request->validated());

        $this->adminLoggingService->log('updated', $permission);

        return redirect()->route('admin.permission.index');
    }
}

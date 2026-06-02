<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUsersRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Models\UserGroup;
use App\Services\AdminLoggingService;
use App\Services\UserGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserGroupController extends Controller
{
    public function __construct(
        private AdminLoggingService $adminLoggingService,
        private UserGroupService $userGroupService
    ) {
    }

    public function getUserGroups(): Response
    {
        $this->authorize('viewAny', UserGroup::class);

        $user = auth()->user();

        $ugs = $this->userGroupService->getUserGroupsForUser($user);

        return Inertia::render('Admin/UserGroups/Index', [
            'user_groups' => $ugs,
        ]);
    }

    public function createUserGroup(): Response
    {
        $this->authorize('createAny', UserGroup::class);

        $user = auth()->user();

        $institutions = $this->userGroupService->getInstitutionsForUser($user);

        return Inertia::render('Admin/UserGroups/Form', [
            'institutions' => $institutions,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeUserGroup(StoreUserGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ug = $this->userGroupService->storeUserGroup($validated);

        $this->adminLoggingService->log('created', $ug);

        return redirect()->route('admin.user_group.index');
    }

    public function editUserGroup(Request $request)
    {
        $id = $request->id;

        $ug = $this->userGroupService->getUserGroupById($id);
        $this->authorize('edit', $ug);

        return Inertia::render('Admin/UserGroups/Form', [
            'user_group' => $ug,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateUserGroup(UpdateUserGroupRequest $request): RedirectResponse
    {
        $id = $request->id;
        $validated = $request->validated();

        $ug = $this->userGroupService->updateUserGroup($id, $validated);

        $this->adminLoggingService->log('updated', $ug);

        return redirect()->route('admin.user_group.index');
    }

    public function deleteUserGroup(Request $request): RedirectResponse
    {
        $id = $request->id;

        $ug = $this->userGroupService->getUserGroupById($id);
        $this->authorize('delete', $ug);

        $this->userGroupService->deleteUserGroup($id);

        $this->adminLoggingService->log('deleted', $ug);

        return redirect()->route('admin.user_group.index');
    }

    public function importForm(Request $request)
    {
        $id = $request->id;

        $ug = $this->userGroupService->getUserGroupById($id);
        $this->authorize('import', $ug);

        return Inertia::render('Admin/UserGroups/Import', [
            'user_group' => $ug,
        ]);
    }

    public function importUsers(ImportUsersRequest $request)
    {
        $id = $request->id;
        $validated = $request->safe()->merge($request->only(['valid_from', 'valid_until']))->toArray();

        $ug = $this->userGroupService->importUsers($id, $validated);

        $this->adminLoggingService->log('import', $ug);

        return redirect()->route('admin.user_group.index');
    }

    public function getUsers(Request $request)
    {
        $id = $request->id;

        $ug = $this->userGroupService->getUserGroupById($id);
        $this->authorize('import', $ug);
        $users = $this->userGroupService->getUsers($ug);

        return Inertia::render('Admin/UserGroups/Users', [
            'user_group' => $ug,
            'users' => $users,
        ]);
    }

    public function removeUsers(Request $request)
    {
        $id = $request->id;
        $validated = $request->validate([
            'id' => ['required', 'uuid', 'exists:user_groups,id'],
            'users' => ['required', 'array'],
            'users.*' => ['uuid', 'exists:users,id'],
        ]);

        $ug = $this->userGroupService->getUserGroupById($id);
        $this->authorize('import', $ug);

        $this->userGroupService->removeUsers($id, $validated['users']);

        return redirect()->route('admin.user_group.users', ['id' => $id]);
    }
}

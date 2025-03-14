<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportUsersRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
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
        $user = auth()->user();

        $ugs = $this->userGroupService->getUserGroupsForUser($user);

        return Inertia::render('Admin/UserGroups/Index', [
            'user_groups' => $ugs,
        ]);
    }

    public function createUserGroup(): Response
    {
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

        $ug = $this->userGroupService->deleteUserGroup($id);

        $this->adminLoggingService->log('deleted', $ug);

        return redirect()->route('admin.user_group.index');
    }

    public function importForm(Request $request)
    {
        $id = $request->id;

        $ug = $this->userGroupService->getUserGroupById($id);

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
        $users = $this->userGroupService->getUsers($ug);

        return Inertia::render('Admin/UserGroups/Users', [
            'user_group' => $ug,
            'users' => $users,
        ]);
    }

    public function removeUsers(Request $request)
    {
        $id = $request->id;
        $users = $request->users;

        $this->userGroupService->removeUsers($id, $users);

        return redirect()->route('admin.user_group.users', ['id' => $id]);
    }
}

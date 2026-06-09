<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteUserGroupRequest;
use App\Http\Requests\Admin\ImportUsersRequest;
use App\Http\Requests\Admin\RemoveUsersFromUserGroupRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Http\Requests\Admin\UserGroupIdRequest;
use App\Models\UserGroup;
use App\Services\Admin\UserGroupAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserGroupController extends AdminController
{
    public function __construct(private readonly UserGroupAdminService $userGroupAdminService) {}

    public function getUserGroups(): Response
    {
        $this->authorize('viewAny', UserGroup::class);

        return Inertia::render(
            'Admin/UserGroups/Index',
            $this->userGroupAdminService->getIndexData($this->authenticatedUser()),
        );
    }

    public function createUserGroup(): Response
    {
        $this->authorize('createAny', UserGroup::class);

        return Inertia::render('Admin/UserGroups/Form', $this->userGroupAdminService->getCreateFormData(
            $this->authenticatedUser(),
        ));
    }

    public function storeUserGroup(StoreUserGroupRequest $request): RedirectResponse
    {
        $this->userGroupAdminService->store($request->validated());

        return redirect()->route('admin.user_group.index');
    }

    public function editUserGroup(UserGroupIdRequest $request): Response
    {
        $userGroup = $request->userGroup();
        $this->authorize('edit', $userGroup);

        return Inertia::render('Admin/UserGroups/Form', $this->userGroupAdminService->getEditFormData($userGroup));
    }

    public function updateUserGroup(UpdateUserGroupRequest $request): RedirectResponse
    {
        $userGroup = $request->userGroup();
        $this->userGroupAdminService->update($userGroup, $request->validated());

        return redirect()->route('admin.user_group.index');
    }

    public function deleteUserGroup(DeleteUserGroupRequest $request): RedirectResponse
    {
        $this->userGroupAdminService->delete($request->userGroup());

        return redirect()->route('admin.user_group.index');
    }

    public function importForm(UserGroupIdRequest $request): Response
    {
        $userGroup = $request->userGroup();
        $this->authorize('import', $userGroup);

        return Inertia::render('Admin/UserGroups/Import', $this->userGroupAdminService->getImportFormData($userGroup));
    }

    public function importUsers(ImportUsersRequest $request): RedirectResponse
    {
        $this->userGroupAdminService->importUsers($request->userGroup(), $request->importData());

        return redirect()->route('admin.user_group.index');
    }

    public function getUsers(UserGroupIdRequest $request): Response
    {
        $userGroup = $request->userGroup();
        $this->authorize('import', $userGroup);

        return Inertia::render('Admin/UserGroups/Users', $this->userGroupAdminService->getUsersData($userGroup));
    }

    public function removeUsers(RemoveUsersFromUserGroupRequest $request): RedirectResponse
    {
        $this->userGroupAdminService->removeUsers($request->userGroup(), $request->userIds());

        return redirect()->route('admin.user_group.users', ['id' => $request->userGroup()->id]);
    }
}

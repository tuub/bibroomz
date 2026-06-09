<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\UserGroup;
use App\Services\AdminLoggingService;
use App\Services\UserGroupService;

class UserGroupAdminService
{
    public function __construct(
        private readonly AdminLoggingService $adminLoggingService,
        private readonly UserGroupService $userGroupService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(User $user): array
    {
        return [
            'user_groups' => $this->userGroupService->getUserGroupsForUser($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(User $user): array
    {
        return [
            'institutions' => $this->userGroupService->getInstitutionsForUser($user),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(UserGroup $userGroup): array
    {
        return [
            'user_group' => $userGroup,
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getImportFormData(UserGroup $userGroup): array
    {
        return [
            'user_group' => $userGroup,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsersData(UserGroup $userGroup): array
    {
        return [
            'user_group' => $userGroup,
            'users' => $this->userGroupService->getUsers($userGroup),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): UserGroup
    {
        $userGroup = $this->userGroupService->storeUserGroup($attributes);

        $this->adminLoggingService->log('created', $userGroup);

        return $userGroup;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UserGroup $userGroup, array $attributes): UserGroup
    {
        $updatedUserGroup = $this->userGroupService->updateUserGroup($userGroup->id, $attributes);

        $this->adminLoggingService->log('updated', $updatedUserGroup);

        return $updatedUserGroup;
    }

    public function delete(UserGroup $userGroup): void
    {
        $this->userGroupService->deleteUserGroup($userGroup->id);

        $this->adminLoggingService->log('deleted', $userGroup);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function importUsers(UserGroup $userGroup, array $attributes): UserGroup
    {
        $importedUserGroup = $this->userGroupService->importUsers($userGroup->id, $attributes);

        $this->adminLoggingService->log('import', $importedUserGroup);

        return $importedUserGroup;
    }

    /**
     * @param  list<string>  $userIds
     */
    public function removeUsers(UserGroup $userGroup, array $userIds): void
    {
        $this->userGroupService->removeUsers($userGroup->id, $userIds);
    }
}

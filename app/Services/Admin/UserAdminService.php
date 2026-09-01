<?php

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AdminLoggingService;
use Illuminate\Support\Carbon;

class UserAdminService
{
    public function __construct(
        private readonly AdminLoggingService $adminLoggingService,
        private readonly UserRoleSynchronizer $userRoleSynchronizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(): array
    {
        return [
            'users' => User::query()
                ->with(['roles', 'user_groups'])
                ->withCount('happenings')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'is_system_user' => $user->is_system_user,
                    'is_logged_in' => $user->isLoggedIn(),
                    'is_privileged' => $user->roles->count() > 0,
                    'is_banned' => $user->isBanned(),
                    'happenings_count' => $user->happenings_count,
                    'user_groups' => array_values(array_map(fn (UserGroup $ug): array => ['id' => $ug->id, 'title' => $ug->getTranslations('title')], $user->user_groups->all())),
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(User $actor): array
    {
        return [
            'is_system_user' => true,
            'is_set_password' => true,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'description']),
            'institutions' => Institution::query()
                ->get()
                ->filter(fn (Institution $institution): bool => $institution->isEditableByUser($actor))
                ->map->only(['id', 'title', 'short_title'])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(User $user, User $actor): array
    {
        $editableInstitutionIds = Institution::query()
            ->get()
            ->filter(fn (Institution $institution): bool => $institution->isEditableByUser($actor))
            ->modelKeys();

        $roles = Role::query()
            ->select('roles.id', 'institution_user_role.institution_id')
            ->join('institution_user_role', 'institution_user_role.role_id', '=', 'roles.id')
            ->where('institution_user_role.user_id', $user->id)
            ->whereIn('institution_user_role.institution_id', $editableInstitutionIds)
            ->get()
            ->map(function (Role $role): array {
                $institutionId = $role->getAttribute('institution_id');

                return [
                    'role_id' => $role->id,
                    'institution_id' => is_string($institutionId) ? $institutionId : '',
                ];
            })
            ->values()
            ->all();

        return [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'is_admin', 'is_system_user']),
                'roles' => $roles,
            ],
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'description']),
            'institutions' => Institution::query()
                ->get()
                ->filter(fn (Institution $institution): bool => $institution->isEditableByUser($actor))
                ->map->only(['id', 'title', 'short_title'])
                ->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<int, array{role_id: string, institution_id: string}>  $roles
     */
    public function store(array $userData, array $roles, User $actor): User
    {
        $user = User::create($userData);
        $this->userRoleSynchronizer->sync($user, $roles, $actor);

        $this->adminLoggingService->log('created', $user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $userData
     * @param  array<int, array{role_id: string, institution_id: string}>  $roles
     */
    public function update(User $user, array $userData, array $roles, User $actor): User
    {
        $user->update($userData);
        $this->userRoleSynchronizer->sync($user, $roles, $actor);

        $this->adminLoggingService->log('updated', $user);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();

        $this->adminLoggingService->log('deleted', $user);
    }

    public function ban(User $user): void
    {
        $suspensionDays = config('roomz.user.suspension_days');

        $user->ban([
            'expired_at' => Carbon::now()->addDays(is_int($suspensionDays) ? $suspensionDays : 0),
        ]);

        $this->adminLoggingService->log('banned', $user);
    }

    public function unban(User $user): void
    {
        $user->unban();

        $this->adminLoggingService->log('unbanned', $user);
    }
}

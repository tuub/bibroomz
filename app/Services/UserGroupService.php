<?php

namespace App\Services;

use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserGroupService
{
    public function deleteUserGroup(string $id): UserGroup
    {
        $ug = UserGroup::where('id', $id)->firstOrFail();
        $ug->deleteOrFail();

        return $ug;
    }

    /**
     * @return Collection<int, Institution>
     */
    public function getInstitutionsForUser(User $user): Collection
    {
        return Institution::active()
            ->orderBy('title')
            ->without('closings')
            ->get()
            ->filter
            ->isUserAbleToCreateUserGroup($user);
    }

    public function getUserGroupById(string $id): UserGroup
    {
        return UserGroup::where('id', $id)->firstOrFail();
    }

    /**
     * @return Collection<int, UserGroup>
     */
    public function getUserGroupsForUser(User $user): Collection
    {
        return UserGroup::with(['institution'])
            ->orderBy('institution_id')
            ->orderBy('title')
            ->get()
            ->filter
            ->isViewableByUser($user);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeUserGroup(array $data): UserGroup
    {
        return UserGroup::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUserGroup(string $id, array $data): UserGroup
    {
        $ug = UserGroup::where('id', $id)->firstOrFail();
        $ug->updateOrFail($data);

        return $ug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importUsers(string $id, array $data): UserGroup
    {
        $ug = UserGroup::where('id', $id)->firstOrFail();
        $users = $this->extractImportUsers($data);
        $pivot = Arr::only($data, ['valid_from', 'valid_until']);

        foreach ($users as $name) {
            $model = User::firstOrCreate(
                ['name' => Utility::normalizeLoginName($name)],
                ['password' => Hash::make(Str::password())],
            );

            try {
                $ug->users()->attach($model, $pivot);
            } catch (UniqueConstraintViolationException) {
                $ug->users()->updateExistingPivot($model, $pivot);
            }
        }

        return $ug;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(UserGroup $ug): Collection
    {
        return $ug->users()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(static fn (User $user): User => $user)
            ->values();
    }

    /**
     * @param  list<string>  $users
     */
    public function removeUsers(string $id, array $users): void
    {
        $ug = $this->getUserGroupById($id);

        foreach ($users as $user) {
            $ug->users()->detach($user);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function extractImportUsers(array $data): array
    {
        $users = $data['users'] ?? [];

        if (! is_array($users)) {
            return [];
        }

        $names = [];

        foreach ($users as $user) {
            if (! is_array($user)) {
                continue;
            }

            $name = $user['name'] ?? null;

            if (is_string($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}

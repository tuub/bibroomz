<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserGroupService
{
    public function deleteUserGroup($id)
    {
        $ug = UserGroup::where('id', $id)->firstOrFail();
        $ug->deleteOrFail();

        return $ug;
    }

    public function getInstitutionsForUser(User $user)
    {
        return Institution::active()
            ->orderBy('title')
            ->without('closings')
            ->get()
            ->filter
            ->isUserAbleToCreateUserGroup($user);
    }

    public function getUserGroupById($id)
    {
        return UserGroup::where('id', $id)->firstOrFail();
    }

    public function getUserGroupsForUser(User $user)
    {
        return UserGroup::with(['institution'])
            ->orderBy('institution_id')
            ->orderBy('title')
            ->get()
            ->filter
            ->isViewableByUser($user);
    }

    public function storeUserGroup(array $data)
    {
        return UserGroup::create($data);
    }

    public function updateUserGroup($id, array $data)
    {
        $ug = UserGroup::where('id', $id)->firstOrFail();
        $ug->updateOrFail($data);

        return $ug;
    }

    public function importUsers($id, array $data)
    {
        $ug = UserGroup::where('id', $id)->firstOrFail();

        foreach ($data['users'] as $user) {
            $model = User::firstOrCreate($user, ['password' => Hash::make(Str::password())]);

            $pivot = Arr::only($data, ['valid_from', 'valid_until']);

            try {
                $ug->users()->attach($model, $pivot);
            } catch (UniqueConstraintViolationException) {
                $ug->users()->updateExistingPivot($model, $pivot);
            }
        }

        return $ug;
    }

    public function getUsers(UserGroup $ug)
    {
        $users = $ug->users()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return $users;
    }

    public function removeUsers($id, array $users)
    {
        $ug = $this->getUserGroupById($id);

        foreach ($users as $user) {
            $ug->users()->detach($user);
        }
    }
}

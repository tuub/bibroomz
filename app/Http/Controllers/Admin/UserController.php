<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function getUsers()
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'users' => User::with(['happenings', 'roles'])->orderBy('name')->get()
        ]);
    }

    public function getFormUsers()
    {
        return User::get(['id', 'name', 'is_admin']);
    }

    public function editUser(Request $request)
    {
        $user = User::where('id', $request->id)->first();

        $this->authorize('edit', $user);

        // restrict roles to institutions the user can edit
        $user->roles = $user->roles->filter(function (Role $role) {
            return $role->pivot->institution->isEditableByUser(auth()->user());
        })->map(function (Role $role) {
            return [
                'role_id' => $role->id,
                'institution_id' => $role->pivot->institution_id
            ];
        });

        return Inertia::render('Admin/Users/Form', [
            'user' => $user->only(['id', 'name', 'email', 'is_admin', 'roles']),
            'roles' => Role::orderBy('name')->get(['id', 'name', 'description']),
            'institutions' => Institution::get()
                ->filter->isEditableByUser(auth()->user())
                ->map->only(['id', 'title', 'short_title'])
        ]);
    }

    public function updateUser(UpdateUserRequest $request): RedirectResponse
    {
        /** @var User */
        $user = User::find($request->id);

        $validated = $request->safe();
        $user->update($validated->except(['roles']));

        // detach old roles
        $user->roles()->each(function (Role $role) use ($user) {
            $institution = $role->pivot->institution;

            if (request()->user()->can('edit', $institution)) {
                $user->roles()->wherePivot('institution_id', $institution->id)->detach($role->id);
            }
        });

        // attach new roles
        collect($validated->roles)->each(function ($input) use ($user) {
            $user->roles()->attach($input['role_id'], ['institution_id' => $input['institution_id']]);
        });

        return redirect()->route('admin.user.index');
    }

    public function deleteUser(DeleteUserRequest $request): RedirectResponse
    {
        $user = User::find($request->id);
        $user->delete();

        return redirect()->route('admin.user.index');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminLoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private AdminLoggingService $adminLoggingService)
    {
    }

    public function getUsers()
    {
        $this->authorize('viewAny', User::class);

        $users = User::with(['happenings', 'roles'])->orderBy('name')->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'is_system_user' => $user->is_system_user,
                    'is_logged_in' => $user->isLoggedIn(),
                    'is_privileged' => $user->roles->count() > 0,
                    'is_banned' => $user->isBanned(),
                    'happenings_count' => $user->happenings->count(),
                ];
            });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function getFormUsers()
    {
        return User::get(['id', 'name', 'is_admin']);
    }

    public function createUser(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'is_system_user' => true,
            'is_set_password' => true,
            'roles' => Role::orderBy('name')->get(['id', 'name', 'description']),
            'institutions' => Institution::get()
                ->filter->isEditableByUser(auth()->user())
                ->map->only(['id', 'title', 'short_title'])
        ]);
    }

    public function storeUser(UserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Compile user data
        $user_data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'],
            'is_system_user' => $validated['is_system_user'],
        ];

        if ($request['is_set_password']) {
            $user_data['password'] = Hash::make($validated['password']);
        }

        $user = User::create($user_data);

        // Attach new roles
        collect($validated['roles'])->each(function ($input) use ($user) {
            $user->roles()->attach($input['role_id'], ['institution_id' => $input['institution_id']]);
        });

        $this->adminLoggingService->log('created', $user);

        return redirect()->route('admin.user.index');
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
            'user' => $user->only(['id', 'name', 'email', 'is_admin', 'is_system_user', 'roles']),
            'roles' => Role::orderBy('name')->get(['id', 'name', 'description']),
            'institutions' => Institution::get()
                ->filter->isEditableByUser(auth()->user())
                ->map->only(['id', 'title', 'short_title'])
        ]);
    }

    public function updateUser(UserRequest $request): RedirectResponse
    {
        $user = User::find($request->id);
        $validated = $request->validated();

        // Compile user data
        $user_data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'],
            'is_system_user' => $validated['is_system_user'],
        ];

        if ($request['is_set_password']) {
            $user_data['password'] = Hash::make($validated['password']);
        }

        $user->update($user_data);

        // Detach old roles
        $user->roles()->each(function (Role $role) use ($user) {
            $institution = $role->pivot->institution;

            if (request()->user()->can('edit', $institution)) {
                $user->roles()->wherePivot('institution_id', $institution->id)->detach($role->id);
            }
        });

        // Attach new roles
        collect($validated['roles'])->each(function ($input) use ($user) {
            $user->roles()->attach($input['role_id'], ['institution_id' => $input['institution_id']]);
        });

        $this->adminLoggingService->log('updated', $user);

        return redirect()->route('admin.user.index');
    }

    public function deleteUser(DeleteUserRequest $request): RedirectResponse
    {
        $user = User::find($request->id);
        $user->delete();

        $this->adminLoggingService->log('deleted', $user);

        return redirect()->route('admin.user.index');
    }

    public function banUser(Request $request)
    {
        $user = User::find($request->id);
        $this->authorize('ban', $user);

        $user->ban([
            'expired_at' => Carbon::now()->addDays(config('roomz.user.suspension_days')),
        ]);

        return redirect()->route('admin.user.index');
    }

    public function unbanUser(Request $request)
    {
        $user = User::find($request->id);
        $this->authorize('unban', $user);
        $user->unban();

        return redirect()->route('admin.user.index');
    }
}

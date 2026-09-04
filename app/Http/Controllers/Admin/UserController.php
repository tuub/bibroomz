<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\UnbanUserRequest;
use App\Http\Requests\Admin\UserIdRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\Admin\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class UserController extends AdminController
{
    public function __construct(private readonly UserAdminService $userAdminService) {}

    public function getUsers(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', $this->userAdminService->getIndexData());
    }

    /**
     * @return Collection<int, array{id: string, name: string, is_admin: bool}>
     */
    public function getFormUsers(): Collection
    {
        // Bypasses Eloquent model hydration: casting each attribute of every user
        // (UUID key resolution included) is expensive per access, and this listing
        // needs no relations or accessors, so a raw query is far cheaper here.
        return DB::table('users')
            ->select(['id', 'name', 'is_admin'])
            ->get()
            ->map(function (object $user): array {
                if (! is_string($user->id) || ! is_string($user->name)) {
                    throw new RuntimeException('Unexpected non-string id or name column value.');
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_admin' => (bool) $user->is_admin,
                ];
            });
    }

    public function createUser(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render(
            'Admin/Users/Form',
            $this->userAdminService->getCreateFormData($this->authenticatedUser()),
        );
    }

    public function storeUser(UserRequest $request): RedirectResponse
    {
        $this->userAdminService->store($request->userData(), $request->roles(), $this->authenticatedUser());

        return redirect()->route('admin.user.index');
    }

    public function editUser(UserIdRequest $request): Response
    {
        $user = $request->targetUser();

        $this->authorize('edit', $user);

        return Inertia::render(
            'Admin/Users/Form',
            $this->userAdminService->getEditFormData($user, $this->authenticatedUser()),
        );
    }

    public function updateUser(UserRequest $request): RedirectResponse
    {
        $user = $request->targetUser();
        $this->userAdminService->update($user, $request->userData(), $request->roles(), $this->authenticatedUser());

        return redirect()->route('admin.user.index');
    }

    public function deleteUser(DeleteUserRequest $request): RedirectResponse
    {
        $user = $request->targetUser();
        $this->userAdminService->delete($user);

        return redirect()->route('admin.user.index');
    }

    public function banUser(BanUserRequest $request): RedirectResponse
    {
        $user = $request->targetUser();
        $this->userAdminService->ban($user);

        return redirect()->route('admin.user.index');
    }

    public function unbanUser(UnbanUserRequest $request): RedirectResponse
    {
        $user = $request->targetUser();
        $this->userAdminService->unban($user);

        return redirect()->route('admin.user.index');
    }
}

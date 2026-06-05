<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserHappeningsRequest;
use App\Models\User;
use App\Services\Http\ListUserHappeningsAction;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    public function __construct(private ListUserHappeningsAction $listUserHappeningsAction)
    {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getUserHappenings(UserHappeningsRequest $request): Collection
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $this->listUserHappeningsAction->execute($request->resourceGroup(), $user);
    }
}

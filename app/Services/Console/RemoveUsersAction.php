<?php

namespace App\Services\Console;

use App\Models\User;
use Illuminate\Support\Collection;

class RemoveUsersAction
{
    /**
     * @param Collection<int, User> $users
     */
    public function execute(Collection $users): void
    {
        $users->each(fn (User $user): bool|null => $user->delete());
    }
}

<?php

namespace App\Services\Console;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

class RemoveUsersQueryBuilder
{
    /**
     * @return Builder<User>
     */
    public function build(int $days): Builder
    {
        $query = User::query()
            ->where('is_admin', false);

        $query->whereNotExists(function (QueryBuilder $builder): void {
            $builder->from('institution_user_role')
                ->whereColumn('user_id', 'users.id');
        });

        $query->whereNotExists(
            fn (QueryBuilder $builder) => $builder
                ->from('user_group_user')
                ->whereColumn('user_id', 'users.id')
                ->where(fn (QueryBuilder $nestedBuilder) => $nestedBuilder
                    ->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now()->subDays($days))),
        );

        $query->whereNotExists(function (QueryBuilder $builder) use ($days): void {
            $builder->from('happenings')
                ->where('end', '>', now()->subDays($days))
                ->where(function (QueryBuilder $nestedBuilder): void {
                    $nestedBuilder->whereColumn('user_id_01', 'users.id')
                        ->orWhereColumn('user_id_02', 'users.id');
                });
        });

        return $query;
    }

    /**
     * @return Collection<int, User>
     */
    public function candidates(int $days): Collection
    {
        return $this->build($days)
            ->get()
            ->filter(fn (User $user): bool => ! $user->isLoggedIn())
            ->values();
    }
}

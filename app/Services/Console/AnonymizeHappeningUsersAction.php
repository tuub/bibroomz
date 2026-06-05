<?php

namespace App\Services\Console;

use App\Models\Happening;
use Illuminate\Database\Eloquent\Builder;

class AnonymizeHappeningUsersAction
{
    /**
     * @return Builder<Happening>
     */
    public function query(int $days): Builder
    {
        return Happening::withTrashed()
            ->where('end', '<=', now()->subDays($days))
            ->where(function (Builder $query): Builder {
                return $query
                    ->whereNotNull('user_id_01')
                    ->orWhereNotNull('user_id_02')
                    ->orWhereNotNull('verifier');
            });
    }

    /**
     * @param Builder<Happening> $query
     */
    public function execute(Builder $query): void
    {
        $query->each(function (Happening $happening): void {
            $happening->user1()->dissociate();
            $happening->user2()->dissociate();
            $happening->verifier = null;
            $happening->save();
        });
    }
}

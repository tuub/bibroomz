<?php

namespace App\Services\Console;

use App\Models\Happening;
use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RemoveUnverifiedHappeningsQueryBuilder
{
    public function __construct(private readonly CleanupIntervalResolver $cleanupIntervalResolver) {}

    public function resolveInstitution(mixed $institutionOption): ?Institution
    {
        if (! is_int($institutionOption) && ! is_string($institutionOption)) {
            return null;
        }

        return Institution::query()->find($institutionOption)
            ?? Institution::where('slug', $institutionOption)->first();
    }

    /**
     * @return Builder<Happening>
     */
    public function baseQuery(): Builder
    {
        return Happening::query()
            ->where('is_verified', false)
            ->whereHas('resource', fn (Builder $query) => $query->where('is_verification_required', true));
    }

    /**
     * @param  Builder<Happening>  $query
     * @return Builder<Happening>
     */
    public function restrictToInstitution(Builder $query, Institution $institution): Builder
    {
        return $query->whereHas(
            'resource',
            fn (Builder $resourceQuery) => $resourceQuery->whereHas(
                'resource_group',
                fn (Builder $resourceGroupQuery) => $resourceGroupQuery->where('institution_id', $institution->id),
            ),
        );
    }

    /**
     * @param  Builder<Happening>  $query
     * @param  Collection<int, Institution>  $institutions
     * @param  callable(Institution, Carbon): void|null  $onInstitution
     * @return Builder<Happening>
     */
    public function applySettingsPerInstitution(
        Builder $query,
        Collection $institutions,
        ?callable $onInstitution = null,
    ): Builder {
        $firstInstitution = $institutions->shift();

        if (! $firstInstitution instanceof Institution) {
            return $query->whereRaw('1 = 0');
        }

        $firstTime = $this->cleanupIntervalResolver->fromInstitution($firstInstitution);
        $query->where(fn (Builder $builder) => $this->restrictToInstitution($builder, $firstInstitution)
            ->where('created_at', '<', $firstTime));
        if ($onInstitution !== null) {
            $onInstitution($firstInstitution, $firstTime);
        }

        foreach ($institutions as $institution) {
            $time = $this->cleanupIntervalResolver->fromInstitution($institution);

            $query->orWhere(fn (Builder $builder) => $this->restrictToInstitution($builder, $institution)
                ->where('created_at', '<', $time));
            if ($onInstitution !== null) {
                $onInstitution($institution, $time);
            }
        }

        return $query;
    }
}

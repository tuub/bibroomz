<?php

namespace App\Services\Admin;

use App\Models\BusinessHour;
use App\Models\Closing;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\WeekDay;
use App\Services\AdminLoggingService;
use App\Services\Resources\ResourceVisibilityService;
use Carbon\Carbon;

class ResourceAdminService
{
    public function __construct(
        private AdminLoggingService $adminLoggingService,
        private BusinessHourSynchronizer $businessHourSynchronizer,
        private ResourceVisibilityService $resourceVisibilityService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(string $resourceGroupId): array
    {
        $user = auth()->user();
        $resources = Resource::query()
            ->with(['resource_group', 'business_hours', 'business_hours.week_days', 'closings'])
            ->where('resource_group_id', $resourceGroupId)
            ->orderBy('order')
            ->get();

        if ($user instanceof User) {
            $resources = $resources
                ->filter(fn (Resource $resource): bool =>
                    $this->resourceVisibilityService->isViewableByUser($resource, $user))
                ->values();
        }

        return [
            'resources' => $resources,
            'resourceGroup' => ResourceGroup::query()
                ->with('institution')
                ->findOrFail($resourceGroupId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(ResourceGroup $resourceGroup): array
    {
        return [
            'resourceGroup' => $resourceGroup,
            'weekDays' => WeekDay::query()->get(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(Resource $resource): array
    {
        $resource->loadMissing(['business_hours.week_days:id', 'resource_group']);

        return [
            'resourceGroup' => $resource->resource_group,
            'resource' => [
                ...$resource->only([
                    'id',
                    'resource_group_id',
                    'location_uri',
                    'capacity',
                    'is_active',
                    'order',
                    'is_verification_required',
                ]),
                'title' => $resource->getTranslations('title'),
                'location' => $resource->getTranslations('location'),
                'description' => $resource->getTranslations('description'),
                'business_hours' => $resource->business_hours->map(
                    fn (BusinessHour $businessHour): array => [
                        'id' => $businessHour->id,
                        'start' => Carbon::parse($businessHour->start)->format('H:i'),
                        'end' => Carbon::parse($businessHour->end)->format('H:i'),
                        'start_date' => $businessHour->start_date?->format('d.m.Y'),
                        'end_date' => $businessHour->end_date?->format('d.m.Y'),
                        'week_days' => $businessHour->week_days->map->id,
                    ],
                ),
            ],
            'weekDays' => WeekDay::query()->get(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @param array<int, array{id: string, order: int}> $rows
     */
    public function reorder(array $rows): void
    {
        foreach ($rows as $row) {
            $resource = Resource::query()->findOrFail($row['id']);
            $resource->update([
                'order' => $row['order'],
            ]);

            $this->adminLoggingService->log('reordered resource', $resource);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>> $businessHours
     */
    public function store(array $attributes, array $businessHours): Resource
    {
        $resource = Resource::create($attributes);
        $this->businessHourSynchronizer->sync($resource, $businessHours);

        $this->adminLoggingService->log('created', $resource);

        return $resource;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>> $businessHours
     */
    public function update(Resource $resource, array $attributes, array $businessHours): Resource
    {
        $resource->update($attributes);
        $this->businessHourSynchronizer->sync($resource, $businessHours);

        $this->adminLoggingService->log('updated', $resource);

        return $resource;
    }

    public function delete(Resource $resource): void
    {
        $resource->delete();

        $this->adminLoggingService->log('deleted', $resource);
    }

    public function clone(Resource $resource): Resource
    {
        $resource->loadMissing('resource_group', 'closings', 'business_hours.week_days');

        $resourceCopy = $resource->replicate();
        $resourceCopy->title = $resource->title . ' ' . trans('admin.general.label.clone');
        $resourceCopy->is_active = false;
        $resourceCopy->save();

        $resourceCopy->closings()->createMany(
            $resource->closings->map(
                fn (Closing $closing): array => [
                    'closable_id' => $resourceCopy->id,
                    'closable_type' => Resource::class,
                    'start' => $closing->start,
                    'end' => $closing->end,
                    'description' => $closing->description,
                ],
            )->all(),
        );

        $resource->business_hours->each(function (BusinessHour $businessHour) use ($resourceCopy): void {
            $copiedBusinessHour = new BusinessHour($businessHour->toArray());
            $resourceCopy->business_hours()->save($copiedBusinessHour);
            $copiedBusinessHour->week_days()->sync($businessHour->week_days->pluck('id'));
        });

        $this->adminLoggingService->log('created clone', $resourceCopy);

        return $resourceCopy;
    }
}

<?php

namespace App\Services\Admin;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use App\Services\AdminLoggingService;
use App\Services\Happenings\CreateHappeningAction;
use App\Services\Happenings\DeleteHappeningAction;
use App\Services\Happenings\ListAdminHappeningsAction;
use App\Services\Happenings\UpdateHappeningAction;
use App\Services\Resources\ResourceVisibilityService;
use Carbon\Carbon;

class HappeningAdminService
{
    public function __construct(
        private AdminLoggingService $adminLoggingService,
        private ListAdminHappeningsAction $listAdminHappeningsAction,
        private CreateHappeningAction $createHappeningAction,
        private UpdateHappeningAction $updateHappeningAction,
        private DeleteHappeningAction $deleteHappeningAction,
        private ResourceVisibilityService $resourceVisibilityService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(User $user): array
    {
        return [
            'happenings' => $this->listAdminHappeningsAction->execute($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(User $user): array
    {
        return [
            'resources' => $this->getCreatableResources($user),
            'users' => $this->getUserOptions(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(Happening $happening): array
    {
        return [
            'happening' => [
                ...$happening->only([
                    'id',
                    'user_id_01',
                    'user_id_02',
                    'resource_id',
                    'is_verified',
                    'verifier',
                ]),
                'start_date' => Carbon::parse($happening->start)->format('d.m.Y'),
                'start_time' => Carbon::parse($happening->start)->format('H:i'),
                'end_date' => Carbon::parse($happening->end)->format('d.m.Y'),
                'end_time' => Carbon::parse($happening->end)->format('H:i'),
                'label' => $happening->getTranslations('label'),
            ],
            'resources' => $this->getEditableResources($happening),
            'users' => $this->getUserOptions(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function store(array $attributes): Happening
    {
        $happening = $this->createHappeningAction->executeForAdmin($attributes);

        $this->adminLoggingService->log('created', $happening);

        return $happening;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Happening $happening, array $attributes): Happening
    {
        $updatedHappening = $this->updateHappeningAction->executeForAdmin($happening, $attributes);

        $this->adminLoggingService->log('updated', $updatedHappening);

        return $updatedHappening;
    }

    public function delete(Happening $happening): void
    {
        $this->deleteHappeningAction->execute($happening);

        $this->adminLoggingService->log('deleted', $happening);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *   id: string,
     *   title: array<string, mixed>,
     *   institution_id: string,
     *   is_verification_required: bool
     * }>
     */
    private function getCreatableResources(User $user)
    {
        return Resource::query()
            ->with('resource_group.institution')
            ->where('is_active', true)
            ->orderBy('title')
            ->without('closings')
            ->get()
            ->filter(fn (Resource $resource): bool => $this->resourceVisibilityService
                ->isUserAbleToCreateHappening($resource, $user))
            ->values()
            ->map(fn (Resource $resource): array => [
                'id' => $resource->id,
                'title' => $this->resourceTranslations($resource, 'title'),
                'institution_id' => $resource->resource_group->institution->id,
                'is_verification_required' => $resource->is_verification_required,
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *   id: string,
     *   title: array<string, mixed>,
     *   resource_group_id: string,
     *   is_verification_required: bool
     * }>
     */
    private function getEditableResources(Happening $happening)
    {
        return Resource::query()
            ->where('resource_group_id', $happening->resource->resource_group_id)
            ->where('is_active', true)
            ->orderBy('title')
            ->without('closings')
            ->get()
            ->map(fn (Resource $resource): array => [
                'id' => $resource->id,
                'title' => $this->resourceTranslations($resource, 'title'),
                'resource_group_id' => $resource->resource_group->id,
                'is_verification_required' => $resource->is_verification_required,
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *   id: string,
     *   name: string,
     *   permissions: \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, string>>
     * }>
     */
    private function getUserOptions()
    {
        return User::query()
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'permissions' => $this->userPermissions($user),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceTranslations(Resource $resource, string $attribute): array
    {
        $translations = $resource->getTranslations($attribute);

        $normalized = [];

        foreach ($translations as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, string>>
     */
    private function userPermissions(User $user): \Illuminate\Support\Collection
    {
        return $user->getPermissions(['no_verifier']);
    }
}

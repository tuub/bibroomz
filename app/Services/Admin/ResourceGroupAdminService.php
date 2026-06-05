<?php

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\AdminLoggingService;
use App\Services\ResourceGroupService;

class ResourceGroupAdminService
{
    public function __construct(
        private AdminLoggingService $adminLoggingService,
        private ResourceGroupService $resourceGroupService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(Institution $institution): array
    {
        return [
            'institution' => $institution,
            'resource_groups' => ResourceGroup::query()
                ->with('resources')
                ->withCount('resources')
                ->where('institution_id', $institution->id)
                ->orderBy('order')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(Institution $institution, User $user): array
    {
        return [
            'institution' => $institution,
            'institutions' => $this->resourceGroupService->getInstitutionsForUser($user),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(ResourceGroup $resourceGroup, User $user): array
    {
        $resourceGroup->loadMissing('institution.user_groups');

        return [
            'resource_group' => $resourceGroup,
            'institutions' => $this->resourceGroupService->getInstitutionsForUser($user)
                ->prepend($resourceGroup->institution)
                ->unique('id')
                ->values(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @param array<int, array{id: string, order: int}> $rows
     */
    public function reorder(array $rows): void
    {
        foreach ($rows as $row) {
            $resourceGroup = ResourceGroup::query()->findOrFail($row['id']);
            $resourceGroup->update([
                'order' => $row['order'],
            ]);

            $this->adminLoggingService->log('reordered resource group', $resourceGroup);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function store(array $attributes): ResourceGroup
    {
        $resourceGroup = $this->resourceGroupService->storeResourceGroup($attributes);

        $this->adminLoggingService->log('created', $resourceGroup);

        return $resourceGroup;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(ResourceGroup $resourceGroup, array $attributes): ResourceGroup
    {
        $updatedResourceGroup = $this->resourceGroupService->updateResourceGroup($resourceGroup->id, $attributes);

        $this->adminLoggingService->log('updated', $updatedResourceGroup);

        return $updatedResourceGroup;
    }

    public function delete(ResourceGroup $resourceGroup): void
    {
        $this->resourceGroupService->deleteResourceGroup($resourceGroup->id);

        $this->adminLoggingService->log('deleted', $resourceGroup);
    }
}

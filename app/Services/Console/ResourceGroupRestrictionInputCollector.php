<?php

namespace App\Services\Console;

use App\Models\Institution;
use App\Models\ResourceGroup;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class ResourceGroupRestrictionInputCollector
{
    /**
     * @return array{resource_group: ResourceGroup, user_group_ids: array<int, string>}
     */
    public function collect(): array
    {
        $institutionOptions = $this->stringOptions(Institution::orderBy('title')->pluck('title', 'id')->all());
        $institutionId = $this->resolveSelectedKey(select(
            'Select an institution',
            $institutionOptions,
        ), $institutionOptions);

        $institution = Institution::findOrFail($institutionId);

        $resourceGroupOptions = $this->stringOptions($institution->resource_groups->pluck('title', 'id')->all());
        $resourceGroupId = $this->resolveSelectedKey(select(
            label: 'Select a resource group',
            options: $resourceGroupOptions,
        ), $resourceGroupOptions);

        $resourceGroup = ResourceGroup::findOrFail($resourceGroupId);

        $userGroupOptions = $this->stringOptions($institution->user_groups->pluck('title', 'id')->all());
        $userGroupIds = $this->resolveSelectedKeys(multiselect(
            label: 'Select some user groups to restrict this resource group to',
            options: $userGroupOptions,
            default: $this->normalizeSelections($resourceGroup->user_groups->pluck('id')->all()),
        ), $userGroupOptions);

        return [
            'resource_group' => $resourceGroup,
            'user_group_ids' => $userGroupIds,
        ];
    }

    /**
     * @param array<int|string, string> $options
     */
    private function resolveSelectedKey(mixed $selection, array $options): string
    {
        $selection = $this->normalizeSelection($selection);

        if ($selection === null) {
            return '';
        }

        if (array_key_exists($selection, $options)) {
            return $selection;
        }

        $resolved = array_search($selection, $options, true);

        return is_string($resolved) || is_int($resolved) ? (string) $resolved : '';
    }

    /**
     * @param array<int|string> $selections
     * @param array<int|string, string> $options
     * @return array<int, string>
     */
    private function resolveSelectedKeys(array $selections, array $options): array
    {
        return array_values(array_map(
            fn (mixed $selection): string => $this->resolveSelectedKey($selection, $options),
            $selections,
        ));
    }

    /**
     * @param array<mixed> $options
     * @return array<int|string, string>
     */
    private function stringOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $selections
     * @return array<int, int|string>
     */
    private function normalizeSelections(array $selections): array
    {
        $normalized = [];

        foreach ($selections as $selection) {
            if (is_int($selection) || is_string($selection)) {
                $normalized[] = $selection;
            }
        }

        return $normalized;
    }

    private function normalizeSelection(mixed $selection): ?string
    {
        if (is_string($selection)) {
            return $selection;
        }

        if (is_int($selection)) {
            return (string) $selection;
        }

        return null;
    }
}

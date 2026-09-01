import type { InstitutionStatistic, ResourceGroupStatistic, ResourceStatistic } from "@/Types/Admin";

import type { ComputedRef } from "vue";
import { computed, ref, watch } from "vue";

export type SelectionValue = number | string | null;

export function sameId(left: number | string | null | undefined, right: number | string | null | undefined): boolean {
    return left != null && right != null && String(left) === String(right);
}

export function readStoredSelection(key: string, validIds: (number | string)[]): SelectionValue {
    if (typeof window === "undefined") {
        return null;
    }

    try {
        const stored = window.localStorage.getItem(key);

        if (stored === null) {
            return null;
        }

        const match = validIds.find((id) => String(id) === stored);

        if (match !== undefined) {
            return match;
        }

        window.localStorage.removeItem(key);
    } catch {
        return null;
    }

    return null;
}

export function writeStoredSelection(key: string, value: SelectionValue) {
    if (typeof window === "undefined") {
        return;
    }

    try {
        if (value === null) {
            window.localStorage.removeItem(key);

            return;
        }

        window.localStorage.setItem(key, String(value));
    } catch {
        // localStorage can be unavailable in private browsing or hardened browsers.
    }
}

const selectionStorageKeys = {
    institution: "roomz.admin.statistics.selectedInstitutionId",
    resourceGroup: "roomz.admin.statistics.selectedResourceGroupId",
} as const;

export function useDrilldownSelection(
    institutions: ComputedRef<InstitutionStatistic[]>,
    resourceGroups: ComputedRef<ResourceGroupStatistic[]>,
    resources: ComputedRef<ResourceStatistic[]>,
    translate: (title: Record<string, string>) => string,
) {
    function resourceGroupsForInstitutionId(institutionId: SelectionValue): ResourceGroupStatistic[] {
        return resourceGroups.value.filter((resourceGroup) => sameId(resourceGroup.institution_id, institutionId));
    }

    const selectedInstitutionId = ref<SelectionValue>(
        readStoredSelection(
            selectionStorageKeys.institution,
            institutions.value.map((institution) => institution.id),
        ) ??
            institutions.value[0]?.id ??
            null,
    );

    const selectedResourceGroupId = ref<SelectionValue>(
        readStoredSelection(
            selectionStorageKeys.resourceGroup,
            resourceGroupsForInstitutionId(selectedInstitutionId.value).map((resourceGroup) => resourceGroup.id),
        ),
    );

    const institutionOptions = computed(() =>
        institutions.value.map((institution) => ({ id: institution.id, label: translate(institution.title) })),
    );

    const resourceGroupsForInstitution = computed(() => resourceGroupsForInstitutionId(selectedInstitutionId.value));

    const resourceGroupOptions = computed(() =>
        resourceGroupsForInstitution.value.map((resourceGroup) => ({
            id: resourceGroup.id,
            label: translate(resourceGroup.title),
        })),
    );

    const resourcesForResourceGroup = computed(() =>
        resources.value.filter((resource) => sameId(resource.resource_group_id, selectedResourceGroupId.value)),
    );

    watch(
        resourceGroupsForInstitution,
        (groups) => {
            if (!groups.some((resourceGroup) => sameId(resourceGroup.id, selectedResourceGroupId.value))) {
                selectedResourceGroupId.value = groups[0]?.id ?? null;
            }
        },
        { immediate: true },
    );

    watch(selectedInstitutionId, (value) => writeStoredSelection(selectionStorageKeys.institution, value));
    watch(selectedResourceGroupId, (value) => writeStoredSelection(selectionStorageKeys.resourceGroup, value));

    return {
        selectedInstitutionId,
        selectedResourceGroupId,
        institutionOptions,
        resourceGroupsForInstitution,
        resourceGroupOptions,
        resourcesForResourceGroup,
    };
}

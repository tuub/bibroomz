import { sameId } from "@/Composables/AdminStatisticsSelection";
import type {
    InstitutionStatistic,
    ResourceGroupStatistic,
    ResourceStatistic,
    StatisticsComparison,
} from "@/Types/Admin";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, ref, watch } from "vue";

interface StatisticsFilterProps {
    range: string;
    from: string | null;
    to: string | null;
    granularity: string;
    comparison: StatisticsComparison | null;
    timeSeriesInstitutionId: number | string | null;
    timeSeriesResourceGroupId: number | string | null;
    timeSeriesResourceId: number | string | null;
    institutions: InstitutionStatistic[];
    resourceGroups: ResourceGroupStatistic[];
    resources: ResourceStatistic[];
}

function toDateString(date: Date | null): string | null {
    if (!date) return null;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

export function useStatisticsFilters(
    props: StatisticsFilterProps,
    translate: (title: Record<string, string>) => string,
    route: ZiggyRouteFn,
) {
    // ------------------------------------------------
    // Range filter
    // ------------------------------------------------
    const rangeOptions = computed(() => [
        { id: "all", label: trans("admin.statistics.index.range.all") },
        { id: "this_week", label: trans("admin.statistics.index.range.this_week") },
        { id: "this_month", label: trans("admin.statistics.index.range.this_month") },
        { id: "this_year", label: trans("admin.statistics.index.range.this_year") },
        { id: "last_7_days", label: trans("admin.statistics.index.range.last_7_days") },
        { id: "last_30_days", label: trans("admin.statistics.index.range.last_30_days") },
        { id: "last_3_months", label: trans("admin.statistics.index.range.last_3_months") },
        { id: "last_12_months", label: trans("admin.statistics.index.range.last_12_months") },
        { id: "custom", label: trans("admin.statistics.index.range.custom") },
    ]);

    const selectedRange = ref(props.range);
    const customFrom = ref<Date | null>(props.from ? new Date(props.from) : null);
    const customTo = ref<Date | null>(props.to ? new Date(props.to) : null);
    const comparisonEnabled = ref(props.comparison !== null);
    const compareFrom = ref<Date | null>(props.comparison?.from ? new Date(props.comparison.from) : null);
    const compareTo = ref<Date | null>(props.comparison?.to ? new Date(props.comparison.to) : null);
    const hasComparison = computed(() => comparisonEnabled.value && props.comparison !== null);

    // ------------------------------------------------
    // Time series granularity
    // ------------------------------------------------
    const granularityOptions = computed(() => [
        { id: "week", label: trans("admin.statistics.index.time_series.week") },
        { id: "month", label: trans("admin.statistics.index.time_series.month") },
        { id: "year", label: trans("admin.statistics.index.time_series.year") },
    ]);

    const selectedGranularity = ref(props.granularity);

    // ------------------------------------------------
    // Time series scope filter (institution / resource group / resource)
    // ------------------------------------------------
    const selectedTimeSeriesInstitutionId = ref<number | string | null>(props.timeSeriesInstitutionId);
    const selectedTimeSeriesResourceGroupId = ref<number | string | null>(props.timeSeriesResourceGroupId);
    const selectedTimeSeriesResourceId = ref<number | string | null>(props.timeSeriesResourceId);

    const timeSeriesInstitutionOptions = computed(() => [
        { id: null, label: trans("admin.statistics.index.time_series.all_institutions") },
        ...props.institutions.map((institution) => ({ id: institution.id, label: translate(institution.title) })),
    ]);

    const timeSeriesResourceGroupsForInstitution = computed(() =>
        selectedTimeSeriesInstitutionId.value
            ? props.resourceGroups.filter((resourceGroup) =>
                  sameId(resourceGroup.institution_id, selectedTimeSeriesInstitutionId.value),
              )
            : props.resourceGroups,
    );

    const timeSeriesResourceGroupOptions = computed(() => [
        { id: null, label: trans("admin.statistics.index.time_series.all_resource_groups") },
        ...timeSeriesResourceGroupsForInstitution.value.map((resourceGroup) => ({
            id: resourceGroup.id,
            label: translate(resourceGroup.title),
        })),
    ]);

    const timeSeriesResourcesForGroup = computed(() => {
        if (selectedTimeSeriesResourceGroupId.value) {
            return props.resources.filter((resource) =>
                sameId(resource.resource_group_id, selectedTimeSeriesResourceGroupId.value),
            );
        }

        const groupIds = timeSeriesResourceGroupsForInstitution.value.map((resourceGroup) => resourceGroup.id);

        return props.resources.filter((resource) => groupIds.some((id) => sameId(id, resource.resource_group_id)));
    });

    const timeSeriesResourceOptions = computed(() => [
        { id: null, label: trans("admin.statistics.index.time_series.all_resources") },
        ...timeSeriesResourcesForGroup.value.map((resource) => ({ id: resource.id, label: translate(resource.title) })),
    ]);

    function toDateStringOrParams() {
        const compareFromString = toDateString(compareFrom.value);
        const compareToString = toDateString(compareTo.value);

        return {
            range: selectedRange.value,
            ...(selectedRange.value === "custom"
                ? { from: toDateString(customFrom.value), to: toDateString(customTo.value) }
                : {}),
            granularity: selectedGranularity.value,
            ...(selectedTimeSeriesInstitutionId.value ? { institution_id: selectedTimeSeriesInstitutionId.value } : {}),
            ...(selectedTimeSeriesResourceGroupId.value
                ? { resource_group_id: selectedTimeSeriesResourceGroupId.value }
                : {}),
            ...(selectedTimeSeriesResourceId.value ? { resource_id: selectedTimeSeriesResourceId.value } : {}),
            ...(comparisonEnabled.value && compareFromString && compareToString
                ? { compare_from: compareFromString, compare_to: compareToString }
                : {}),
        };
    }

    function applyFilters() {
        router.get(route("admin.statistics.index"), toDateStringOrParams(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function exportUrl(type: string): string {
        return route("admin.statistics.export", { ...toDateStringOrParams(), type });
    }

    function onTimeSeriesInstitutionChange(value: number | string | null) {
        selectedTimeSeriesInstitutionId.value = value;
        selectedTimeSeriesResourceGroupId.value = null;
        selectedTimeSeriesResourceId.value = null;
        applyFilters();
    }

    function onTimeSeriesResourceGroupChange(value: number | string | null) {
        selectedTimeSeriesResourceGroupId.value = value;
        selectedTimeSeriesResourceId.value = null;
        applyFilters();
    }

    function onTimeSeriesResourceChange(value: number | string | null) {
        selectedTimeSeriesResourceId.value = value;
        applyFilters();
    }

    watch(selectedRange, (range) => {
        if (range !== "custom") {
            applyFilters();
        }
    });

    watch(selectedGranularity, () => applyFilters());
    watch(comparisonEnabled, (enabled) => {
        if (!enabled && props.comparison !== null) {
            applyFilters();
        }
    });

    return {
        rangeOptions,
        selectedRange,
        customFrom,
        customTo,
        comparisonEnabled,
        compareFrom,
        compareTo,
        hasComparison,
        granularityOptions,
        selectedGranularity,
        selectedTimeSeriesInstitutionId,
        selectedTimeSeriesResourceGroupId,
        selectedTimeSeriesResourceId,
        timeSeriesInstitutionOptions,
        timeSeriesResourceGroupOptions,
        timeSeriesResourceOptions,
        onTimeSeriesInstitutionChange,
        onTimeSeriesResourceGroupChange,
        onTimeSeriesResourceChange,
        applyFilters,
        exportUrl,
    };
}

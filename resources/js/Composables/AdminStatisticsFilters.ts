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

type SelectionId = number | string;

interface StatisticsFilterProps {
    range: string;
    from: string | null;
    to: string | null;
    granularity: string;
    comparison: StatisticsComparison | null;
    timeSeriesInstitutionIds: SelectionId[];
    timeSeriesResourceGroupIds: SelectionId[];
    timeSeriesResourceIds: SelectionId[];
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

function includesId(values: SelectionId[], id: SelectionId | null | undefined): boolean {
    return values.some((value) => sameId(value, id));
}

function withParentQualifiedLabels(
    options: { id: SelectionId; label: string; parentId: SelectionId; parentLabel: string }[],
): { id: SelectionId; label: string }[] {
    const hasMultipleParents = new Set(options.map((option) => String(option.parentId))).size > 1;

    return options.map((option) => ({
        id: option.id,
        label: hasMultipleParents ? `${option.label} — ${option.parentLabel}` : option.label,
    }));
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
    const selectedTimeSeriesInstitutionIds = ref<SelectionId[]>([...props.timeSeriesInstitutionIds]);
    const selectedTimeSeriesResourceGroupIds = ref<SelectionId[]>([...props.timeSeriesResourceGroupIds]);
    const selectedTimeSeriesResourceIds = ref<SelectionId[]>([...props.timeSeriesResourceIds]);

    const timeSeriesInstitutionOptions = computed(() =>
        props.institutions.map((institution) => ({ id: institution.id, label: translate(institution.title) })),
    );

    const timeSeriesResourceGroupsForInstitution = computed(() =>
        selectedTimeSeriesInstitutionIds.value.length > 0
            ? props.resourceGroups.filter((resourceGroup) =>
                  includesId(selectedTimeSeriesInstitutionIds.value, resourceGroup.institution_id),
              )
            : props.resourceGroups,
    );

    const timeSeriesResourceGroupOptions = computed(() =>
        withParentQualifiedLabels(
            timeSeriesResourceGroupsForInstitution.value.map((resourceGroup) => ({
                id: resourceGroup.id,
                label: translate(resourceGroup.title),
                parentId: resourceGroup.institution_id,
                parentLabel: translate(
                    props.institutions.find((institution) => sameId(institution.id, resourceGroup.institution_id))
                        ?.title ?? {},
                ),
            })),
        ),
    );

    const timeSeriesResourcesForGroup = computed(() => {
        if (selectedTimeSeriesResourceGroupIds.value.length > 0) {
            return props.resources.filter((resource) =>
                includesId(selectedTimeSeriesResourceGroupIds.value, resource.resource_group_id),
            );
        }

        const groupIds = timeSeriesResourceGroupsForInstitution.value.map((resourceGroup) => resourceGroup.id);

        return props.resources.filter((resource) => groupIds.some((id) => sameId(id, resource.resource_group_id)));
    });

    const timeSeriesResourceOptions = computed(() =>
        withParentQualifiedLabels(
            timeSeriesResourcesForGroup.value.map((resource) => ({
                id: resource.id,
                label: translate(resource.title),
                parentId: resource.resource_group_id,
                parentLabel: translate(
                    props.resourceGroups.find((resourceGroup) => sameId(resourceGroup.id, resource.resource_group_id))
                        ?.title ?? {},
                ),
            })),
        ),
    );

    function toDateStringOrParams() {
        const compareFromString = toDateString(compareFrom.value);
        const compareToString = toDateString(compareTo.value);

        return {
            range: selectedRange.value,
            ...(selectedRange.value === "custom"
                ? { from: toDateString(customFrom.value), to: toDateString(customTo.value) }
                : {}),
            granularity: selectedGranularity.value,
            ...(selectedTimeSeriesInstitutionIds.value.length > 0
                ? { institution_id: selectedTimeSeriesInstitutionIds.value }
                : {}),
            ...(selectedTimeSeriesResourceGroupIds.value.length > 0
                ? { resource_group_id: selectedTimeSeriesResourceGroupIds.value }
                : {}),
            ...(selectedTimeSeriesResourceIds.value.length > 0
                ? { resource_id: selectedTimeSeriesResourceIds.value }
                : {}),
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

    function onTimeSeriesInstitutionChange(value: SelectionId[]) {
        selectedTimeSeriesInstitutionIds.value = value;
        selectedTimeSeriesResourceGroupIds.value = [];
        selectedTimeSeriesResourceIds.value = [];
        applyFilters();
    }

    function onTimeSeriesResourceGroupChange(value: SelectionId[]) {
        selectedTimeSeriesResourceGroupIds.value = value;
        selectedTimeSeriesResourceIds.value = [];
        applyFilters();
    }

    function onTimeSeriesResourceChange(value: SelectionId[]) {
        selectedTimeSeriesResourceIds.value = value;
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
        selectedTimeSeriesInstitutionIds,
        selectedTimeSeriesResourceGroupIds,
        selectedTimeSeriesResourceIds,
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

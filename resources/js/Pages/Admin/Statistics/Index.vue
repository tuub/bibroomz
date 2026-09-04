<script setup lang="ts">
import { useCancellationStatus } from "@/Composables/AdminStatisticsCancellation";
import { useStatisticsFilters } from "@/Composables/AdminStatisticsFilters";
import {
    comparisonDeltaClass,
    formatDateRange,
    formatNumber,
    formatSignedPercent,
} from "@/Composables/AdminStatisticsFormat";
import { heatmapGridStyle, heatmapHours, usePeakTimesHeatmap } from "@/Composables/AdminStatisticsHeatmap";
import { pieChartOptions, slicePalette, usePieChartSection } from "@/Composables/AdminStatisticsPieChart";
import { sameId, useDrilldownSelection } from "@/Composables/AdminStatisticsSelection";
import { useChartThemeColors } from "@/Composables/AdminStatisticsTheme";
import { useAppStore } from "@/Stores/AppStore";
import type {
    CancellationStatistic,
    InstitutionStatistic,
    PeakTimesHeatmap,
    ResourceGroupStatistic,
    ResourceStatistic,
    StatisticsComparison,
    TimeSeriesEntry,
} from "@/Types/Admin";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import FiltersCard from "./Components/FiltersCard.vue";
import PeakTimesHeatmapCard from "./Components/PeakTimesHeatmapCard.vue";
import PieChartCard from "./Components/PieChartCard.vue";
import TimeSeriesCard from "./Components/TimeSeriesCard.vue";

import { Deferred } from "@inertiajs/vue3";
import type { ChartData, ChartOptions } from "chart.js";
import { trans } from "laravel-vue-i18n";
import { computed, inject, ref } from "vue";

type TimeSeriesChartMode = "stacked" | "grouped";

const route = inject<ZiggyRouteFn>("ziggyRoute")!;

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        institutions?: InstitutionStatistic[];
        resourceGroups?: ResourceGroupStatistic[];
        resources?: ResourceStatistic[];
        range?: string;
        from?: string | null;
        to?: string | null;
        timeSeries?: TimeSeriesEntry[];
        granularity?: string;
        timeSeriesSplit?: string;
        timeSeriesInstitutionIds?: (number | string)[];
        timeSeriesResourceGroupIds?: (number | string)[];
        timeSeriesResourceIds?: (number | string)[];
        cancellations?: CancellationStatistic;
        heatmap?: PeakTimesHeatmap;
        comparison?: StatisticsComparison | null;
    }>(),
    {
        institutions: () => [],
        resourceGroups: () => [],
        resources: () => [],
        range: "all",
        from: null,
        to: null,
        timeSeries: () => [],
        granularity: "month",
        timeSeriesSplit: "none",
        timeSeriesInstitutionIds: () => [],
        timeSeriesResourceGroupIds: () => [],
        timeSeriesResourceIds: () => [],
        cancellations: () => ({
            cancelled: 0,
            active: 0,
            rate: 0,
            retentionDays: 0,
            retentionExceeded: false,
        }),
        heatmap: () => ({
            cells: [],
            maxCount: 0,
            totalCount: 0,
        }),
        comparison: null,
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const { translate } = appStore;

const {
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
} = useStatisticsFilters(props, translate, route);

// ------------------------------------------------
// Theme-aware chart colors
// ------------------------------------------------
const { textColor, surfaceColor, borderColor } = useChartThemeColors();

// ------------------------------------------------
// Selection state
// ------------------------------------------------
const {
    selectedInstitutionId,
    selectedResourceGroupId,
    institutionOptions,
    resourceGroupsForInstitution,
    resourceGroupOptions,
    resourcesForResourceGroup,
} = useDrilldownSelection(
    computed(() => props.institutions),
    computed(() => props.resourceGroups),
    computed(() => props.resources),
    translate,
);

// ------------------------------------------------
// Cancellation status tables
// ------------------------------------------------
const institutionCancellationEntries = useCancellationStatus(
    computed(() => props.institutions),
    translate,
);
const resourceGroupCancellationEntries = useCancellationStatus(resourceGroupsForInstitution, translate);
const resourceCancellationEntries = useCancellationStatus(resourcesForResourceGroup, translate);

// ------------------------------------------------
// Pie charts
// ------------------------------------------------
const comparisonInstitutions = computed<InstitutionStatistic[]>(() => props.comparison?.institutions ?? []);

const comparisonResourceGroupsForInstitution = computed<ResourceGroupStatistic[]>(() =>
    selectedInstitutionId.value
        ? (props.comparison?.resourceGroups ?? []).filter((resourceGroup) =>
              sameId(resourceGroup.institution_id, selectedInstitutionId.value),
          )
        : (props.comparison?.resourceGroups ?? []),
);

const comparisonResourcesForResourceGroup = computed<ResourceStatistic[]>(() =>
    (props.comparison?.resources ?? []).filter((resource) =>
        sameId(resource.resource_group_id, selectedResourceGroupId.value),
    ),
);

const {
    chartData: institutionChartData,
    legendItems: institutionLegendItems,
    onLoaded: onInstitutionChartLoaded,
    toggle: toggleInstitutionLegendItem,
    singleEntryLabel: institutionSingleEntryLabel,
    singleEntryCount: institutionSingleEntryCount,
} = usePieChartSection(
    computed(() => props.institutions),
    translate,
    surfaceColor,
);

const {
    chartData: resourceGroupChartData,
    legendItems: resourceGroupLegendItems,
    onLoaded: onResourceGroupChartLoaded,
    toggle: toggleResourceGroupLegendItem,
    singleEntryLabel: resourceGroupSingleEntryLabel,
    singleEntryCount: resourceGroupSingleEntryCount,
} = usePieChartSection(resourceGroupsForInstitution, translate, surfaceColor);

const {
    chartData: resourceChartData,
    legendItems: resourceLegendItems,
    onLoaded: onResourceChartLoaded,
    toggle: toggleResourceLegendItem,
    singleEntryLabel: resourceSingleEntryLabel,
    singleEntryCount: resourceSingleEntryCount,
} = usePieChartSection(resourcesForResourceGroup, translate, surfaceColor);

const {
    chartData: comparisonInstitutionChartData,
    legendItems: comparisonInstitutionLegendItems,
    onLoaded: onComparisonInstitutionChartLoaded,
    toggle: toggleComparisonInstitutionLegendItem,
    singleEntryLabel: comparisonInstitutionSingleEntryLabel,
    singleEntryCount: comparisonInstitutionSingleEntryCount,
} = usePieChartSection(comparisonInstitutions, translate, surfaceColor);

const {
    chartData: comparisonResourceGroupChartData,
    legendItems: comparisonResourceGroupLegendItems,
    onLoaded: onComparisonResourceGroupChartLoaded,
    toggle: toggleComparisonResourceGroupLegendItem,
    singleEntryLabel: comparisonResourceGroupSingleEntryLabel,
    singleEntryCount: comparisonResourceGroupSingleEntryCount,
} = usePieChartSection(comparisonResourceGroupsForInstitution, translate, surfaceColor);

const {
    chartData: comparisonResourceChartData,
    legendItems: comparisonResourceLegendItems,
    onLoaded: onComparisonResourceChartLoaded,
    toggle: toggleComparisonResourceLegendItem,
    singleEntryLabel: comparisonResourceSingleEntryLabel,
    singleEntryCount: comparisonResourceSingleEntryCount,
} = usePieChartSection(comparisonResourcesForResourceGroup, translate, surfaceColor);

// ------------------------------------------------
// Pie card display props (empty-state messages)
// ------------------------------------------------
const resourceGroupUnselectedMessageKey = computed(() =>
    selectedInstitutionId.value ? null : "admin.statistics.index.resource_groups.select_institution",
);

const resourceUnselectedMessageKey = computed(() =>
    selectedResourceGroupId.value ? null : "admin.statistics.index.resources.select_resource_group",
);

// ------------------------------------------------
// Time series chart
// ------------------------------------------------
function hasTimeSeriesSegments(entries: TimeSeriesEntry[]): boolean {
    return entries.some((entry) => (entry.segments?.length ?? 0) > 0);
}

function buildTimeSeriesChartData(
    entries: TimeSeriesEntry[],
    backgroundColor: string,
    mode: TimeSeriesChartMode,
): ChartData<"bar"> {
    const firstSegments = entries.find((entry) => (entry.segments?.length ?? 0) > 0)?.segments ?? [];

    if (firstSegments.length > 0) {
        const colors = slicePalette(firstSegments.length);

        return {
            labels: entries.map((entry) => entry.label),
            datasets: firstSegments.map((segment, index) => ({
                label: translate(segment.title),
                backgroundColor: colors[index]!,
                data: entries.map(
                    (entry) => entry.segments?.find((candidate) => sameId(candidate.id, segment.id))?.count ?? 0,
                ),
                ...(mode === "stacked" ? { stack: "bookings" } : {}),
            })),
        };
    }

    return {
        labels: entries.map((entry) => entry.label),
        datasets: [
            {
                label: trans("admin.statistics.index.bookings"),
                backgroundColor,
                data: entries.map((entry) => entry.count),
            },
        ],
    };
}

const timeSeriesChartMode = ref<TimeSeriesChartMode>("stacked");

const timeSeriesIsSplit = computed(
    () => hasTimeSeriesSegments(props.timeSeries) || hasTimeSeriesSegments(props.comparison?.timeSeries ?? []),
);

const timeSeriesChartData = computed<ChartData<"bar">>(() =>
    buildTimeSeriesChartData(props.timeSeries, "#3b82f6", timeSeriesChartMode.value),
);

const comparisonTimeSeriesChartData = computed<ChartData<"bar">>(() =>
    buildTimeSeriesChartData(props.comparison?.timeSeries ?? [], "#14b8a6", timeSeriesChartMode.value),
);

const comparisonDeltaLabel = computed(() =>
    props.comparison
        ? trans("admin.statistics.index.comparison.delta", {
              delta: formatSignedPercent(props.comparison.deltaPct),
          })
        : "",
);

const comparisonCountLabel = computed(() =>
    props.comparison
        ? trans("admin.statistics.index.comparison.comparison_count", {
              count: formatNumber(props.comparison.comparisonCount),
          })
        : "",
);

const currentPeriodRangeLabel = computed(() => {
    if (props.from && props.to) {
        return formatDateRange(props.from, props.to, appStore.formatDate);
    }

    return rangeOptions.value.find((option) => option.id === props.range)?.label ?? "";
});

const currentPeriodCountLabel = computed(() =>
    props.comparison
        ? trans("admin.statistics.index.comparison.current_count", {
              count: formatNumber(props.comparison.currentCount),
          })
        : "",
);

const comparisonDateRangeLabel = computed(() =>
    props.comparison ? formatDateRange(props.comparison.from, props.comparison.to, appStore.formatDate) : "",
);

const comparisonDeltaClassValue = computed(() =>
    props.comparison ? comparisonDeltaClass(props.comparison.deltaPct) : "text-app-muted",
);

const timeSeriesIsStacked = computed(() => timeSeriesIsSplit.value && timeSeriesChartMode.value === "stacked");

const timeSeriesChartOptions = computed<ChartOptions<"bar">>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: timeSeriesIsSplit.value,
            position: "bottom",
            labels: { color: textColor.value },
        },
    },
    scales: {
        x: {
            stacked: timeSeriesIsStacked.value,
            ticks: { color: textColor.value },
            grid: { color: borderColor.value },
        },
        y: {
            stacked: timeSeriesIsStacked.value,
            beginAtZero: true,
            ticks: { color: textColor.value },
            grid: { color: borderColor.value },
        },
    },
}));

// ------------------------------------------------
// Peak-times heatmap
// ------------------------------------------------
const heatmapRows = usePeakTimesHeatmap(computed(() => props.heatmap));
</script>

<template>
    <div class="flex flex-col gap-6">
        <FiltersCard
            v-model:range="selectedRange"
            v-model:comparison-enabled="comparisonEnabled"
            v-model:custom-from="customFrom"
            v-model:custom-to="customTo"
            v-model:compare-from="compareFrom"
            v-model:compare-to="compareTo"
            :range-options="rangeOptions"
            @apply="applyFilters"
        />

        <Deferred :data="['timeSeries', 'cancellations']">
            <template #fallback>
                <div
                    class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface min-w-0 border p-4 shadow-sm"
                >
                    <div class="text-lg font-semibold">{{ $t("admin.statistics.index.time_series.title") }}</div>
                    <p class="text-app-muted mt-3 italic" data-test="time-series-loading">
                        {{ $t("admin.statistics.index.loading") }}
                    </p>
                </div>
            </template>

            <TimeSeriesCard
                v-model:granularity="selectedGranularity"
                v-model:chart-mode="timeSeriesChartMode"
                :time-series-is-split="timeSeriesIsSplit"
                :granularity-options="granularityOptions"
                :time-series-institution-options="timeSeriesInstitutionOptions"
                :time-series-resource-group-options="timeSeriesResourceGroupOptions"
                :time-series-resource-options="timeSeriesResourceOptions"
                :selected-time-series-institution-ids="selectedTimeSeriesInstitutionIds"
                :selected-time-series-resource-group-ids="selectedTimeSeriesResourceGroupIds"
                :selected-time-series-resource-ids="selectedTimeSeriesResourceIds"
                :retention-exceeded="cancellations.retentionExceeded"
                :retention-days="cancellations.retentionDays"
                :has-comparison="hasComparison"
                :comparison="comparison"
                :time-series="timeSeries"
                :time-series-chart-data="timeSeriesChartData"
                :comparison-time-series-chart-data="comparisonTimeSeriesChartData"
                :time-series-chart-options="timeSeriesChartOptions"
                :current-period-range-label="currentPeriodRangeLabel"
                :current-period-count-label="currentPeriodCountLabel"
                :comparison-date-range-label="comparisonDateRangeLabel"
                :comparison-delta-label="comparisonDeltaLabel"
                :comparison-delta-class="comparisonDeltaClassValue"
                :comparison-count-label="comparisonCountLabel"
                :export-url="exportUrl('time_series')"
                @institution-change="onTimeSeriesInstitutionChange"
                @resource-group-change="onTimeSeriesResourceGroupChange"
                @resource-change="onTimeSeriesResourceChange"
            />
        </Deferred>

        <Deferred data="heatmap">
            <template #fallback>
                <div
                    class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface min-w-0 border p-4 shadow-sm"
                >
                    <div class="text-lg font-semibold">{{ $t("admin.statistics.index.heatmap.title") }}</div>
                    <p class="text-app-muted mt-3 italic" data-test="heatmap-loading">
                        {{ $t("admin.statistics.index.loading") }}
                    </p>
                </div>
            </template>

            <PeakTimesHeatmapCard
                :rows="heatmapRows"
                :hours="heatmapHours"
                :grid-style="heatmapGridStyle"
                :export-url="exportUrl('heatmap')"
            />
        </Deferred>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <PieChartCard
                title-key="admin.statistics.index.institutions.title"
                :export-url="exportUrl('institutions')"
                export-test-id="export-institutions"
                :unselected-message-key="null"
                :entry-count="institutions.length"
                :single-entry-label="institutionSingleEntryLabel"
                :single-entry-count="institutionSingleEntryCount"
                :chart-data="institutionChartData"
                :chart-options="pieChartOptions"
                :legend-items="institutionLegendItems"
                :on-chart-loaded="onInstitutionChartLoaded"
                :on-legend-toggle="toggleInstitutionLegendItem"
                :comparison-visible="hasComparison && comparison !== null"
                comparison-test-id="institution-comparison"
                comparison-chart-test-id="comparison-institution-chart"
                :comparison-entry-count="comparisonInstitutions.length"
                :comparison-single-entry-label="comparisonInstitutionSingleEntryLabel"
                :comparison-single-entry-count="comparisonInstitutionSingleEntryCount"
                :comparison-chart-data="comparisonInstitutionChartData"
                :comparison-legend-items="comparisonInstitutionLegendItems"
                :on-comparison-chart-loaded="onComparisonInstitutionChartLoaded"
                :on-comparison-legend-toggle="toggleComparisonInstitutionLegendItem"
                :cancellation-entries="institutionCancellationEntries"
                cancellations-test-id="institution-cancellations"
                cancellation-row-test-id="institution-cancellation-row"
            />

            <PieChartCard
                title-key="admin.statistics.index.resource_groups.title"
                :export-url="exportUrl('resource_groups')"
                export-test-id="export-resource-groups"
                :unselected-message-key="resourceGroupUnselectedMessageKey"
                :entry-count="resourceGroupsForInstitution.length"
                :single-entry-label="resourceGroupSingleEntryLabel"
                :single-entry-count="resourceGroupSingleEntryCount"
                :chart-data="resourceGroupChartData"
                :chart-options="pieChartOptions"
                :legend-items="resourceGroupLegendItems"
                :on-chart-loaded="onResourceGroupChartLoaded"
                :on-legend-toggle="toggleResourceGroupLegendItem"
                :comparison-visible="hasComparison && comparison !== null"
                comparison-test-id="resource-group-comparison"
                comparison-chart-test-id="comparison-resource-group-chart"
                :comparison-entry-count="comparisonResourceGroupsForInstitution.length"
                :comparison-single-entry-label="comparisonResourceGroupSingleEntryLabel"
                :comparison-single-entry-count="comparisonResourceGroupSingleEntryCount"
                :comparison-chart-data="comparisonResourceGroupChartData"
                :comparison-legend-items="comparisonResourceGroupLegendItems"
                :on-comparison-chart-loaded="onComparisonResourceGroupChartLoaded"
                :on-comparison-legend-toggle="toggleComparisonResourceGroupLegendItem"
                :cancellation-entries="resourceGroupCancellationEntries"
                cancellations-test-id="resource-group-cancellations"
                cancellation-row-test-id="resource-group-cancellation-row"
            >
                <template v-if="institutionOptions.length > 1" #filter>
                    <span id="statistics-pie-institution-select-label" class="sr-only">
                        {{ $t("admin.statistics.index.resource_groups.select_institution") }}
                    </span>
                    <Select
                        v-model="selectedInstitutionId"
                        :options="institutionOptions"
                        option-label="label"
                        option-value="id"
                        :placeholder="$t('admin.general.form.choose')"
                        class="w-full max-w-56"
                        input-id="statistics-pie-institution-select"
                        aria-labelledby="statistics-pie-institution-select-label"
                        data-test="pie-institution-select"
                    />
                </template>
            </PieChartCard>

            <PieChartCard
                title-key="admin.statistics.index.resources.title"
                :export-url="exportUrl('resources')"
                export-test-id="export-resources"
                :unselected-message-key="resourceUnselectedMessageKey"
                :entry-count="resourcesForResourceGroup.length"
                :single-entry-label="resourceSingleEntryLabel"
                :single-entry-count="resourceSingleEntryCount"
                :chart-data="resourceChartData"
                :chart-options="pieChartOptions"
                :legend-items="resourceLegendItems"
                :on-chart-loaded="onResourceChartLoaded"
                :on-legend-toggle="toggleResourceLegendItem"
                :comparison-visible="hasComparison && comparison !== null"
                comparison-test-id="resource-comparison"
                comparison-chart-test-id="comparison-resource-chart"
                :comparison-entry-count="comparisonResourcesForResourceGroup.length"
                :comparison-single-entry-label="comparisonResourceSingleEntryLabel"
                :comparison-single-entry-count="comparisonResourceSingleEntryCount"
                :comparison-chart-data="comparisonResourceChartData"
                :comparison-legend-items="comparisonResourceLegendItems"
                :on-comparison-chart-loaded="onComparisonResourceChartLoaded"
                :on-comparison-legend-toggle="toggleComparisonResourceLegendItem"
                :cancellation-entries="resourceCancellationEntries"
                cancellations-test-id="resource-cancellations"
                cancellation-row-test-id="resource-cancellation-row"
            >
                <template v-if="resourceGroupOptions.length > 1" #filter>
                    <span id="statistics-pie-resource-group-select-label" class="sr-only">
                        {{ $t("admin.statistics.index.resources.select_resource_group") }}
                    </span>
                    <Select
                        v-model="selectedResourceGroupId"
                        :options="resourceGroupOptions"
                        option-label="label"
                        option-value="id"
                        :placeholder="$t('admin.general.form.choose')"
                        class="w-full max-w-56"
                        input-id="statistics-pie-resource-group-select"
                        aria-labelledby="statistics-pie-resource-group-select-label"
                        data-test="pie-resource-group-select"
                    />
                </template>
            </PieChartCard>
        </div>
    </div>
</template>

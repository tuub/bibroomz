<script setup lang="ts">
import type { StatisticsComparison, TimeSeriesEntry } from "@/Types/Admin";

import MultiSelectToggleAllLabel from "@/Components/MultiSelectToggleAllLabel.vue";
import type { ChartData, ChartOptions } from "chart.js";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

type SelectionId = number | string;
type SelectOption = { id: SelectionId; label: string };

defineProps<{
    granularityOptions: { id: string; label: string }[];
    timeSeriesInstitutionOptions: SelectOption[];
    timeSeriesResourceGroupOptions: SelectOption[];
    timeSeriesResourceOptions: SelectOption[];
    selectedTimeSeriesInstitutionIds: SelectionId[];
    selectedTimeSeriesResourceGroupIds: SelectionId[];
    selectedTimeSeriesResourceIds: SelectionId[];
    timeSeriesIsSplit: boolean;
    retentionExceeded: boolean;
    retentionDays: number;
    hasComparison: boolean;
    comparison: StatisticsComparison | null;
    timeSeries: TimeSeriesEntry[];
    timeSeriesChartData: ChartData<"bar">;
    comparisonTimeSeriesChartData: ChartData<"bar">;
    timeSeriesChartOptions: ChartOptions<"bar">;
    currentPeriodRangeLabel: string;
    currentPeriodCountLabel: string;
    comparisonDateRangeLabel: string;
    comparisonDeltaLabel: string;
    comparisonDeltaClass: string;
    comparisonCountLabel: string;
    exportUrl: string;
}>();

const selectedGranularity = defineModel<string>("granularity", { required: true });
const selectedChartMode = defineModel<"stacked" | "grouped">("chartMode", { required: true });

const chartModeOptions = computed(() => [
    { id: "stacked" as const, label: trans("admin.statistics.index.time_series.chart_mode.stacked") },
    { id: "grouped" as const, label: trans("admin.statistics.index.time_series.chart_mode.grouped") },
]);

const emit = defineEmits<{
    "institution-change": [value: SelectionId[]];
    "resource-group-change": [value: SelectionId[]];
    "resource-change": [value: SelectionId[]];
}>();

function toSelectionIds(value: unknown): SelectionId[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.filter((id): id is SelectionId => typeof id === "string" || typeof id === "number");
}
</script>

<template>
    <div
        class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface min-w-0 border p-4 shadow-sm"
    >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="text-lg font-semibold">{{ $t("admin.statistics.index.time_series.title") }}</div>
            <div class="flex flex-wrap items-center gap-2">
                <SelectButton
                    v-if="timeSeriesIsSplit"
                    v-model="selectedChartMode"
                    :options="chartModeOptions"
                    option-label="label"
                    option-value="id"
                    :allow-empty="false"
                    :aria-label="$t('admin.statistics.index.time_series.chart_mode.label')"
                    data-test="time-series-chart-mode"
                />
                <span id="statistics-granularity-select-label" class="sr-only">
                    {{ $t("admin.statistics.index.time_series.granularity") }}
                </span>
                <Select
                    v-model="selectedGranularity"
                    :options="granularityOptions"
                    option-label="label"
                    option-value="id"
                    class="w-48"
                    input-id="statistics-granularity-select"
                    aria-labelledby="statistics-granularity-select-label"
                    data-test="granularity-select"
                />
                <a :href="exportUrl" download>
                    <Button
                        :label="$t('admin.statistics.index.export.button')"
                        icon="pi pi-download"
                        severity="secondary"
                        size="small"
                        data-test="export-time-series"
                    />
                </a>
            </div>
        </div>
        <div class="mb-3 flex flex-wrap items-end gap-3">
            <div v-if="timeSeriesInstitutionOptions.length > 1" class="flex flex-col gap-1">
                <label for="statistics-time-series-institution" class="text-sm font-medium">{{
                    $t("admin.statistics.index.time_series.institution")
                }}</label>
                <MultiSelect
                    :model-value="selectedTimeSeriesInstitutionIds"
                    :options="timeSeriesInstitutionOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="$t('admin.statistics.index.time_series.all_institutions')"
                    :max-selected-labels="2"
                    display="chip"
                    class="w-56"
                    input-id="statistics-time-series-institution"
                    data-test="time-series-institution-select"
                    @update:model-value="emit('institution-change', toSelectionIds($event))"
                >
                    <template #header="{ value, options }">
                        <MultiSelectToggleAllLabel :value="value" :options="options" />
                    </template>
                </MultiSelect>
            </div>
            <div v-if="timeSeriesResourceGroupOptions.length > 1" class="flex flex-col gap-1">
                <label for="statistics-time-series-resource-group" class="text-sm font-medium">{{
                    $t("admin.statistics.index.time_series.resource_group")
                }}</label>
                <MultiSelect
                    :model-value="selectedTimeSeriesResourceGroupIds"
                    :options="timeSeriesResourceGroupOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="$t('admin.statistics.index.time_series.all_resource_groups')"
                    :max-selected-labels="2"
                    display="chip"
                    class="w-56"
                    input-id="statistics-time-series-resource-group"
                    data-test="time-series-resource-group-select"
                    @update:model-value="emit('resource-group-change', toSelectionIds($event))"
                >
                    <template #header="{ value, options }">
                        <MultiSelectToggleAllLabel :value="value" :options="options" />
                    </template>
                </MultiSelect>
            </div>
            <div v-if="timeSeriesResourceOptions.length > 1" class="flex flex-col gap-1">
                <label for="statistics-time-series-resource" class="text-sm font-medium">{{
                    $t("admin.statistics.index.time_series.resource")
                }}</label>
                <MultiSelect
                    :model-value="selectedTimeSeriesResourceIds"
                    :options="timeSeriesResourceOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="$t('admin.statistics.index.time_series.all_resources')"
                    :max-selected-labels="2"
                    display="chip"
                    class="w-56"
                    input-id="statistics-time-series-resource"
                    data-test="time-series-resource-select"
                    @update:model-value="emit('resource-change', toSelectionIds($event))"
                >
                    <template #header="{ value, options }">
                        <MultiSelectToggleAllLabel :value="value" :options="options" />
                    </template>
                </MultiSelect>
            </div>
        </div>
        <p v-if="retentionExceeded" class="text-app-muted mb-3 text-sm italic" data-test="retention-notice">
            {{
                $t("admin.statistics.index.cancellations.retention_notice", {
                    days: String(retentionDays),
                })
            }}
        </p>
        <div class="grid grid-cols-1 gap-4" :class="{ 'xl:grid-cols-2': hasComparison }">
            <div>
                <div v-if="hasComparison && comparison" class="mb-2 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <div class="text-sm font-semibold">
                            {{ $t("admin.statistics.index.comparison.current_period_title") }}
                        </div>
                        <div class="text-app-muted text-xs">
                            {{ currentPeriodRangeLabel }}
                        </div>
                    </div>
                </div>
                <div
                    v-if="hasComparison && comparison"
                    class="text-app-muted mb-2 text-xs"
                    data-test="current-period-counts"
                >
                    {{ currentPeriodCountLabel }}
                </div>
                <p v-if="timeSeries.length === 0" class="text-app-muted italic">
                    {{ $t("admin.statistics.index.no_data") }}
                </p>
                <div v-else class="h-80">
                    <Chart
                        type="bar"
                        :data="timeSeriesChartData"
                        :options="timeSeriesChartOptions"
                        class="h-full w-full"
                        data-test="time-series-chart"
                    />
                </div>
            </div>

            <div v-if="hasComparison && comparison" data-test="comparison-time-series">
                <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <div class="text-sm font-semibold">
                            {{ $t("admin.statistics.index.comparison.title") }}
                        </div>
                        <div class="text-app-muted text-xs">
                            {{ comparisonDateRangeLabel }}
                        </div>
                    </div>
                    <div
                        class="text-sm font-semibold whitespace-nowrap"
                        :class="comparisonDeltaClass"
                        data-test="comparison-delta"
                    >
                        {{ comparisonDeltaLabel }}
                    </div>
                </div>
                <div class="text-app-muted mb-2 text-xs" data-test="comparison-counts">
                    {{ comparisonCountLabel }}
                </div>
                <p v-if="comparison.timeSeries.length === 0" class="text-app-muted italic">
                    {{ $t("admin.statistics.index.no_data") }}
                </p>
                <div v-else class="h-80">
                    <Chart
                        type="bar"
                        :data="comparisonTimeSeriesChartData"
                        :options="timeSeriesChartOptions"
                        class="h-full w-full"
                        data-test="comparison-time-series-chart"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

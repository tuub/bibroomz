<script setup lang="ts">
import type { StatisticsComparison, TimeSeriesEntry } from "@/Types/Admin";

import type { ChartData, ChartOptions } from "chart.js";

type SelectOption = { id: number | string | null; label: string };

defineProps<{
    granularityOptions: { id: string; label: string }[];
    timeSeriesInstitutionOptions: SelectOption[];
    timeSeriesResourceGroupOptions: SelectOption[];
    timeSeriesResourceOptions: SelectOption[];
    selectedTimeSeriesInstitutionId: number | string | null;
    selectedTimeSeriesResourceGroupId: number | string | null;
    selectedTimeSeriesResourceId: number | string | null;
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

defineEmits<{
    "institution-change": [value: number | string | null];
    "resource-group-change": [value: number | string | null];
    "resource-change": [value: number | string | null];
}>();
</script>

<template>
    <div class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface border p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="text-lg font-semibold">{{ $t("admin.statistics.index.time_series.title") }}</div>
            <div class="flex items-center gap-2">
                <Select
                    v-model="selectedGranularity"
                    :options="granularityOptions"
                    option-label="label"
                    option-value="id"
                    class="w-48"
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
        <!-- options[0] is the synthetic "All ..." entry, so ">2" means more than one real entry exists -->
        <div class="mb-3 flex flex-wrap items-end gap-3">
            <div v-if="timeSeriesInstitutionOptions.length > 2" class="flex flex-col gap-1">
                <label class="text-sm font-medium">{{ $t("admin.statistics.index.time_series.institution") }}</label>
                <Select
                    :model-value="selectedTimeSeriesInstitutionId"
                    :options="timeSeriesInstitutionOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="timeSeriesInstitutionOptions[0]!.label"
                    class="w-56"
                    data-test="time-series-institution-select"
                    @update:model-value="$emit('institution-change', $event)"
                />
            </div>
            <div v-if="timeSeriesResourceGroupOptions.length > 2" class="flex flex-col gap-1">
                <label class="text-sm font-medium">{{ $t("admin.statistics.index.time_series.resource_group") }}</label>
                <Select
                    :model-value="selectedTimeSeriesResourceGroupId"
                    :options="timeSeriesResourceGroupOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="timeSeriesResourceGroupOptions[0]!.label"
                    class="w-56"
                    data-test="time-series-resource-group-select"
                    @update:model-value="$emit('resource-group-change', $event)"
                />
            </div>
            <div v-if="timeSeriesResourceOptions.length > 2" class="flex flex-col gap-1">
                <label class="text-sm font-medium">{{ $t("admin.statistics.index.time_series.resource") }}</label>
                <Select
                    :model-value="selectedTimeSeriesResourceId"
                    :options="timeSeriesResourceOptions"
                    option-label="label"
                    option-value="id"
                    :placeholder="timeSeriesResourceOptions[0]!.label"
                    class="w-56"
                    data-test="time-series-resource-select"
                    @update:model-value="$emit('resource-change', $event)"
                />
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

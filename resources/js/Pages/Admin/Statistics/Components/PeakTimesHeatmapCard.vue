<script setup lang="ts">
import type { heatmapHours } from "@/Composables/AdminStatisticsHeatmap";

type HeatmapRow = {
    dayOfWeek: number;
    label: string;
    cells: {
        dayOfWeek: number;
        hour: number;
        count: number;
        percentage: number;
        percentageLabel: string;
        title: string;
        style: Record<string, string>;
    }[];
};

defineProps<{
    rows: HeatmapRow[];
    hours: typeof heatmapHours;
    gridStyle: Record<string, string>;
    exportUrl: string;
}>();
</script>

<template>
    <div
        class="border border-app-border bg-app-surface p-4 shadow dark:border-app-border dark:bg-app-surface"
        data-test="peak-times-heatmap"
    >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="text-lg font-semibold">{{ $t("admin.statistics.index.heatmap.title") }}</div>
                <div class="text-sm text-app-muted">{{ $t("admin.statistics.index.heatmap.hour_of_day") }}</div>
            </div>
            <a :href="exportUrl" download>
                <Button
                    :label="$t('admin.statistics.index.export.button')"
                    icon="pi pi-download"
                    severity="secondary"
                    size="small"
                    data-test="export-heatmap"
                />
            </a>
        </div>
        <div class="overflow-x-auto">
            <div class="grid min-w-[62rem] gap-1" :style="gridStyle">
                <div class="text-xs font-medium text-app-muted">
                    {{ $t("admin.statistics.index.heatmap.day_of_week") }}
                </div>
                <div v-for="hour in hours" :key="hour.hour" class="text-center text-xs font-medium text-app-muted">
                    {{ hour.label }}
                </div>

                <template v-for="row in rows" :key="row.dayOfWeek">
                    <div class="truncate py-1 pr-2 text-sm font-medium">{{ row.label }}</div>
                    <div
                        v-for="cell in row.cells"
                        :key="`${cell.dayOfWeek}-${cell.hour}`"
                        class="flex h-8 items-center justify-center rounded-sm border border-app-border px-1 text-center text-[0.625rem] font-semibold leading-none text-slate-900 dark:text-white"
                        :style="cell.style"
                        :title="cell.title"
                        :aria-label="cell.title"
                        data-test="heatmap-cell"
                    >
                        <span aria-hidden="true">{{ cell.percentageLabel }}</span>
                        <span class="sr-only">{{ cell.title }}</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

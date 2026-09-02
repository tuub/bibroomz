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
        class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface border p-4 shadow-sm"
        data-test="peak-times-heatmap"
    >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="text-lg font-semibold">{{ $t("admin.statistics.index.heatmap.title") }}</div>
                <div class="text-app-muted text-sm">{{ $t("admin.statistics.index.heatmap.hour_of_day") }}</div>
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
            <div class="grid min-w-248 gap-1" :style="gridStyle">
                <div class="text-app-muted text-xs font-medium">
                    {{ $t("admin.statistics.index.heatmap.day_of_week") }}
                </div>
                <div v-for="hour in hours" :key="hour.hour" class="text-app-muted text-center text-xs font-medium">
                    {{ hour.label }}
                </div>

                <template v-for="row in rows" :key="row.dayOfWeek">
                    <div class="truncate py-1 pr-2 text-sm font-medium">{{ row.label }}</div>
                    <div
                        v-for="cell in row.cells"
                        :key="`${cell.dayOfWeek}-${cell.hour}`"
                        class="border-app-border flex h-8 items-center justify-center rounded-xs border px-1 text-center text-[0.625rem] leading-none font-semibold text-slate-900 dark:text-white"
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

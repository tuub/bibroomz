<script setup lang="ts">
import type { CancellationStatusEntry } from "@/Composables/AdminStatisticsCancellation";
import type { PieLegendItem } from "@/Composables/AdminStatisticsPieChart";

import type { ChartData, Chart as ChartJS, ChartOptions } from "chart.js";

defineProps<{
    titleKey: string;
    exportUrl: string;
    exportTestId: string;
    unselectedMessageKey: string | null;
    entryCount: number;
    singleEntryLabel: string;
    singleEntryCount: string;
    chartData: ChartData<"pie">;
    chartOptions: ChartOptions<"pie">;
    legendItems: PieLegendItem[];
    onChartLoaded: (instance: ChartJS<"pie">) => void;
    onLegendToggle: (index: number) => void;
    comparisonVisible: boolean;
    comparisonTestId: string;
    comparisonChartTestId: string;
    comparisonEntryCount: number;
    comparisonSingleEntryLabel: string;
    comparisonSingleEntryCount: string;
    comparisonChartData: ChartData<"pie">;
    comparisonLegendItems: PieLegendItem[];
    onComparisonChartLoaded: (instance: ChartJS<"pie">) => void;
    onComparisonLegendToggle: (index: number) => void;
    cancellationEntries: CancellationStatusEntry[];
    cancellationsTestId: string;
    cancellationRowTestId: string;
}>();
</script>

<template>
    <div class="border-app-border bg-app-surface dark:border-app-border dark:bg-app-surface border p-4 shadow-sm">
        <div class="mb-3 flex flex-col items-start gap-2">
            <div class="flex w-full items-center justify-between gap-2">
                <div class="text-lg font-semibold">{{ $t(titleKey) }}</div>
                <a :href="exportUrl" download>
                    <Button
                        :label="$t('admin.statistics.index.export.button')"
                        icon="pi pi-download"
                        severity="secondary"
                        size="small"
                        :data-test="exportTestId"
                    />
                </a>
            </div>
            <slot name="filter" />
        </div>

        <p v-if="unselectedMessageKey" class="text-app-muted italic">{{ $t(unselectedMessageKey) }}</p>
        <p v-else-if="entryCount === 0" class="text-app-muted italic">
            {{ $t("admin.statistics.index.no_data") }}
        </p>
        <p v-else-if="entryCount === 1" class="text-lg">
            {{
                $t("admin.statistics.index.single_entry", {
                    title: singleEntryLabel,
                    count: singleEntryCount,
                })
            }}
        </p>
        <div v-else class="flex h-80 gap-4">
            <div class="min-w-0 flex-1">
                <Chart
                    type="pie"
                    :data="chartData"
                    :options="chartOptions"
                    class="h-full w-full"
                    @loaded="onChartLoaded"
                />
            </div>
            <ul class="flex w-56 shrink-0 flex-col gap-1 overflow-y-auto text-sm">
                <li
                    v-for="(item, index) in legendItems"
                    :key="item.label"
                    class="flex cursor-pointer items-start gap-2"
                    :class="{ 'text-app-muted line-through': item.hidden }"
                    @click="onLegendToggle(index)"
                >
                    <span class="mt-1 h-3 w-3 shrink-0 rounded-xs" :style="{ backgroundColor: item.color }" />
                    <span>{{ item.label }}</span>
                </li>
            </ul>
        </div>

        <div v-if="comparisonVisible" class="border-app-border mt-4 border-t pt-3" :data-test="comparisonTestId">
            <div class="mb-2 text-sm font-semibold">
                {{ $t("admin.statistics.index.comparison.period_title") }}
            </div>
            <p v-if="unselectedMessageKey" class="text-app-muted italic">{{ $t(unselectedMessageKey) }}</p>
            <p v-else-if="comparisonEntryCount === 0" class="text-app-muted italic">
                {{ $t("admin.statistics.index.no_data") }}
            </p>
            <p v-else-if="comparisonEntryCount === 1" class="text-lg">
                {{
                    $t("admin.statistics.index.single_entry", {
                        title: comparisonSingleEntryLabel,
                        count: comparisonSingleEntryCount,
                    })
                }}
            </p>
            <div v-else class="flex h-64 gap-4">
                <div class="min-w-0 flex-1">
                    <Chart
                        type="pie"
                        :data="comparisonChartData"
                        :options="chartOptions"
                        class="h-full w-full"
                        :data-test="comparisonChartTestId"
                        @loaded="onComparisonChartLoaded"
                    />
                </div>
                <ul class="flex w-56 shrink-0 flex-col gap-1 overflow-y-auto text-sm">
                    <li
                        v-for="(item, index) in comparisonLegendItems"
                        :key="item.label"
                        class="flex cursor-pointer items-start gap-2"
                        :class="{ 'text-app-muted line-through': item.hidden }"
                        @click="onComparisonLegendToggle(index)"
                    >
                        <span class="mt-1 h-3 w-3 shrink-0 rounded-xs" :style="{ backgroundColor: item.color }" />
                        <span>{{ item.label }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div
            v-if="cancellationEntries.length > 0"
            class="border-app-border mt-4 border-t pt-3"
            :data-test="cancellationsTestId"
        >
            <div class="mb-2 text-sm font-semibold">
                {{ $t("admin.statistics.index.cancellations.title") }}
            </div>
            <div class="max-h-64 overflow-auto">
                <table class="w-full min-w-md text-left text-sm">
                    <thead class="text-app-muted text-xs">
                        <tr>
                            <th class="w-2/5 py-1 pr-2 font-medium">
                                {{ $t("admin.statistics.index.cancellations.subject") }}
                            </th>
                            <th class="px-2 py-1 text-right font-medium">
                                {{ $t("admin.statistics.index.cancellations.active") }}
                            </th>
                            <th class="px-2 py-1 text-right font-medium">
                                {{ $t("admin.statistics.index.cancellations.cancelled") }}
                            </th>
                            <th class="py-1 pl-2 text-right font-medium">
                                {{ $t("admin.statistics.index.cancellations.rate") }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="entry in cancellationEntries"
                            :key="entry.id"
                            class="border-app-border border-t"
                            :data-test="cancellationRowTestId"
                        >
                            <td class="truncate py-1 pr-2">{{ entry.label }}</td>
                            <td class="px-2 py-1 text-right whitespace-nowrap">{{ entry.active }}</td>
                            <td class="px-2 py-1 text-right whitespace-nowrap">{{ entry.cancelled }}</td>
                            <td class="py-1 pl-2 text-right whitespace-nowrap">{{ entry.rate }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

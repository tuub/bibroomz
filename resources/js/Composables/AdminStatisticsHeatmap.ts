import { formatNumber, formatPercent } from "@/Composables/AdminStatisticsFormat";
import type { PeakTimesHeatmap } from "@/Types/Admin";

import { trans } from "laravel-vue-i18n";
import type { ComputedRef } from "vue";
import { computed } from "vue";

const heatmapDayKeys = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as const;

export const heatmapHours = Array.from({ length: 24 }, (_, hour) => ({
    hour,
    label: String(hour).padStart(2, "0"),
    title: `${String(hour).padStart(2, "0")}:00`,
}));

export const heatmapGridStyle = {
    gridTemplateColumns: "5rem repeat(24, minmax(2.25rem, 1fr))",
};

function heatmapCellStyle(count: number, maxCount: number): Record<string, string> {
    if (count === 0 || maxCount === 0) {
        return { backgroundColor: "rgba(20, 184, 166, 0.08)" };
    }

    const intensity = 0.18 + Math.min(count / maxCount, 1) * 0.72;

    return { backgroundColor: `rgba(20, 184, 166, ${intensity.toFixed(2)})` };
}

export function usePeakTimesHeatmap(heatmap: ComputedRef<PeakTimesHeatmap>) {
    const cellsByKey = computed(() => {
        const cells = new Map<string, { count: number; percentage: number }>();

        for (const cell of heatmap.value.cells) {
            cells.set(`${cell.dayOfWeek}-${cell.hour}`, {
                count: cell.count,
                percentage: cell.percentage,
            });
        }

        return cells;
    });

    return computed(() =>
        heatmapDayKeys.map((dayKey, index) => {
            const dayOfWeek = index + 1;
            const label = trans(`admin.general.week_days.${dayKey}.short_label`);

            return {
                dayOfWeek,
                label,
                cells: heatmapHours.map((hour) => {
                    const cell = cellsByKey.value.get(`${dayOfWeek}-${hour.hour}`) ?? { count: 0, percentage: 0 };

                    return {
                        dayOfWeek,
                        hour: hour.hour,
                        count: cell.count,
                        percentage: cell.percentage,
                        percentageLabel: cell.percentage > 0 ? formatPercent(cell.percentage) : "",
                        title: `${label} ${hour.title}: ${formatNumber(cell.count)} (${formatPercent(cell.percentage)})`,
                        style: heatmapCellStyle(cell.count, heatmap.value.maxCount),
                    };
                }),
            };
        }),
    );
}

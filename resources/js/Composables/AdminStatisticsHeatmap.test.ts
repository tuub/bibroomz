import { heatmapHours, usePeakTimesHeatmap } from "@/Composables/AdminStatisticsHeatmap";
import type { PeakTimesHeatmap } from "@/Types/Admin";

import { describe, expect, test, vi } from "vitest";
import { computed } from "vue";

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
}));

function buildHeatmap(cells: PeakTimesHeatmap["cells"], maxCount = 0): PeakTimesHeatmap {
    return { cells, maxCount, totalCount: cells.reduce((total, cell) => total + cell.count, 0) };
}

describe("heatmapHours", () => {
    test("lists 24 hours with zero-padded labels", () => {
        expect(heatmapHours).toHaveLength(24);
        expect(heatmapHours[0]).toEqual({ hour: 0, label: "00", title: "00:00" });
        expect(heatmapHours[9]).toEqual({ hour: 9, label: "09", title: "09:00" });
        expect(heatmapHours[23]).toEqual({ hour: 23, label: "23", title: "23:00" });
    });
});

describe("usePeakTimesHeatmap", () => {
    test("builds one row per day of the week and one cell per hour", () => {
        const rows = usePeakTimesHeatmap(computed(() => buildHeatmap([])));

        expect(rows.value).toHaveLength(7);
        rows.value.forEach((row, index) => {
            expect(row.dayOfWeek).toBe(index + 1);
            expect(row.cells).toHaveLength(24);
        });
    });

    test("fills in the count and percentage for a matching cell", () => {
        const rows = usePeakTimesHeatmap(
            computed(() => buildHeatmap([{ dayOfWeek: 1, hour: 10, count: 2, percentage: 66.7 }], 2)),
        );

        const cell = rows.value[0]!.cells[10]!;

        expect(cell.count).toBe(2);
        expect(cell.percentage).toBe(66.7);
        expect(cell.percentageLabel).toBe("66.7%");
        expect(cell.title).toContain("10:00");
        expect(cell.title).toContain("2");
        expect(cell.title).toContain("66.7%");
    });

    test("defaults to zero count and an empty percentage label when a cell is missing", () => {
        const rows = usePeakTimesHeatmap(computed(() => buildHeatmap([])));

        const cell = rows.value[0]!.cells[0]!;

        expect(cell.count).toBe(0);
        expect(cell.percentage).toBe(0);
        expect(cell.percentageLabel).toBe("");
    });

    test("gives empty cells a faint background regardless of the maximum count", () => {
        const rows = usePeakTimesHeatmap(
            computed(() => buildHeatmap([{ dayOfWeek: 1, hour: 10, count: 0, percentage: 0 }], 5)),
        );

        expect(rows.value[0]!.cells[10]!.style).toEqual({ backgroundColor: "rgba(20, 184, 166, 0.08)" });
    });

    test("scales the cell intensity toward the maximum count", () => {
        const rows = usePeakTimesHeatmap(
            computed(() => buildHeatmap([{ dayOfWeek: 1, hour: 10, count: 4, percentage: 100 }], 4)),
        );

        expect(rows.value[0]!.cells[10]!.style).toEqual({ backgroundColor: "rgba(20, 184, 166, 0.90)" });
    });
});

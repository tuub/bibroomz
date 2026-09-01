import { pieChartOptions, slicePalette, usePieChart, usePieChartSection } from "@/Composables/AdminStatisticsPieChart";

import type { Chart as ChartJS } from "chart.js";
import { describe, expect, test, vi } from "vitest";
import { computed, ref } from "vue";

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
}));

function buildChartInstance() {
    return {
        toggleDataVisibility: vi.fn(),
        update: vi.fn(),
    } as unknown as ChartJS<"pie">;
}

describe("slicePalette", () => {
    test("generates one hsl color per entry with an evenly spread hue", () => {
        expect(slicePalette(3)).toEqual(["hsl(0, 65%, 55%)", "hsl(137.508, 65%, 55%)", "hsl(275.016, 65%, 55%)"]);
    });

    test("returns an empty palette for zero entries", () => {
        expect(slicePalette(0)).toEqual([]);
    });
});

describe("pieChartOptions", () => {
    test("hides the built-in legend so the custom HTML legend is used instead", () => {
        expect(pieChartOptions.plugins?.legend).toEqual({ display: false });
    });
});

describe("usePieChart", () => {
    test("builds chart data with a bar-free pie dataset per entry", () => {
        const { chartData } = usePieChart(
            computed(() => [
                { label: "A", count: 4 },
                { label: "B", count: 1 },
            ]),
            computed(() => "#fff"),
        );

        expect(chartData.value.labels).toEqual(["A", "B"]);
        expect(chartData.value.datasets[0]!.data).toEqual([4, 1]);
        expect(chartData.value.datasets[0]!.borderColor).toBe("#fff");
        expect(chartData.value.datasets[0]!.backgroundColor).toEqual(slicePalette(2));
    });

    test("builds one legend item per entry, none hidden initially", () => {
        const { legendItems } = usePieChart(
            computed(() => [
                { label: "A", count: 4 },
                { label: "B", count: 1 },
            ]),
            computed(() => "#fff"),
        );

        expect(legendItems.value).toEqual([
            { label: "A", count: 4, color: slicePalette(2)[0], hidden: false },
            { label: "B", count: 1, color: slicePalette(2)[1], hidden: false },
        ]);
    });

    test("toggle hides a visible slice and shows it again on the next toggle", () => {
        const { legendItems, onLoaded, toggle } = usePieChart(
            computed(() => [
                { label: "A", count: 4 },
                { label: "B", count: 1 },
            ]),
            computed(() => "#fff"),
        );
        const chart = buildChartInstance();
        onLoaded(chart);

        toggle(1);

        expect(chart.toggleDataVisibility).toHaveBeenCalledWith(1);
        expect(chart.update).toHaveBeenCalledTimes(1);
        expect(legendItems.value[1]!.hidden).toBe(true);
        expect(legendItems.value[0]!.hidden).toBe(false);

        toggle(1);

        expect(legendItems.value[1]!.hidden).toBe(false);
    });

    test("does nothing when toggled before the chart has loaded", () => {
        const { legendItems, toggle } = usePieChart(
            computed(() => [{ label: "A", count: 4 }]),
            computed(() => "#fff"),
        );

        toggle(0);

        expect(legendItems.value[0]!.hidden).toBe(false);
    });

    test("resets the hidden slices when a new chart instance loads", () => {
        const { legendItems, onLoaded, toggle } = usePieChart(
            computed(() => [{ label: "A", count: 4 }]),
            computed(() => "#fff"),
        );
        onLoaded(buildChartInstance());
        toggle(0);
        expect(legendItems.value[0]!.hidden).toBe(true);

        onLoaded(buildChartInstance());

        expect(legendItems.value[0]!.hidden).toBe(false);
    });
});

describe("usePieChartSection", () => {
    const translate = (title: Record<string, string>) => title.en ?? "";

    test("maps items to chart entries using the translated title", () => {
        const { chartData } = usePieChartSection(
            computed(() => [
                { title: { en: "Institution A" }, count: 4 },
                { title: { en: "Institution B" }, count: 1 },
            ]),
            translate,
            computed(() => "#fff"),
        );

        expect(chartData.value.labels).toEqual(["Institution A", "Institution B"]);
    });

    test("exposes the single entry label and count for a one-item list", () => {
        const { singleEntryLabel, singleEntryCount } = usePieChartSection(
            computed(() => [{ title: { en: "Institution A" }, count: 4 }]),
            translate,
            computed(() => "#fff"),
        );

        expect(singleEntryLabel.value).toBe("Institution A");
        expect(singleEntryCount.value).toBe("4");
    });

    test("leaves the single entry label and count empty when there are no items", () => {
        const { singleEntryLabel, singleEntryCount } = usePieChartSection(
            computed(() => []),
            translate,
            computed(() => "#fff"),
        );

        expect(singleEntryLabel.value).toBe("");
        expect(singleEntryCount.value).toBe("");
    });

    test("recomputes the single entry values once the item list changes", () => {
        const items = ref([{ title: { en: "Institution A" }, count: 4 }]);
        const { singleEntryLabel, singleEntryCount } = usePieChartSection(
            computed(() => items.value),
            translate,
            computed(() => "#fff"),
        );

        items.value = [{ title: { en: "Institution B" }, count: 9 }];

        expect(singleEntryLabel.value).toBe("Institution B");
        expect(singleEntryCount.value).toBe("9");
    });
});

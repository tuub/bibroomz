import { useStatisticsFilters } from "@/Composables/AdminStatisticsFilters";
import type { InstitutionStatistic, ResourceGroupStatistic, ResourceStatistic } from "@/Types/Admin";

import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick } from "vue";

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
}));

const routerGetMock = vi.fn();

vi.mock("@inertiajs/vue3", () => ({
    router: {
        get: (...args: unknown[]) => routerGetMock(...args),
    },
}));

const translate = (title: Record<string, string>) => title.en ?? "";
const route = vi.fn((name: string, params?: unknown) => JSON.stringify({ name, params }));

beforeEach(() => {
    vi.clearAllMocks();
});

function institution(id: number | string): InstitutionStatistic {
    return { id, title: { en: `Institution ${id}` }, count: 0, active: 0, cancelled: 0, cancellationRate: 0 };
}

function resourceGroup(id: number | string, institutionId: number | string): ResourceGroupStatistic {
    return {
        id,
        institution_id: institutionId,
        title: { en: `Group ${id}` },
        count: 0,
        active: 0,
        cancelled: 0,
        cancellationRate: 0,
    };
}

function resource(id: number | string, resourceGroupId: number | string): ResourceStatistic {
    return {
        id,
        resource_group_id: resourceGroupId,
        title: { en: `Resource ${id}` },
        count: 0,
        active: 0,
        cancelled: 0,
        cancellationRate: 0,
    };
}

function buildFilters(overrides: Partial<Parameters<typeof useStatisticsFilters>[0]> = {}) {
    return useStatisticsFilters(
        {
            range: "all",
            from: null,
            to: null,
            granularity: "month",
            comparison: null,
            timeSeriesInstitutionId: null,
            timeSeriesResourceGroupId: null,
            timeSeriesResourceId: null,
            institutions: [institution(1), institution(2)],
            resourceGroups: [resourceGroup(10, 1), resourceGroup(20, 2)],
            resources: [resource(100, 10), resource(200, 20)],
            ...overrides,
        },
        translate,
        route,
    );
}

describe("useStatisticsFilters", () => {
    test("scopes time series resource group options to the selected institution", () => {
        const filters = buildFilters({ timeSeriesInstitutionId: 1 });

        expect(filters.timeSeriesResourceGroupOptions.value.map((option) => option.id)).toEqual([null, 10]);
    });

    test("lists every resource group when no institution is selected", () => {
        const filters = buildFilters();

        expect(filters.timeSeriesResourceGroupOptions.value.map((option) => option.id)).toEqual([null, 10, 20]);
    });

    test("scopes time series resource options to the selected resource group", () => {
        const filters = buildFilters({ timeSeriesResourceGroupId: 10 });

        expect(filters.timeSeriesResourceOptions.value.map((option) => option.id)).toEqual([null, 100]);
    });

    test("falls back to resources across the institution's groups when no group is selected", () => {
        const filters = buildFilters({ timeSeriesInstitutionId: 1 });

        expect(filters.timeSeriesResourceOptions.value.map((option) => option.id)).toEqual([null, 100]);
    });

    test("resets the resource group and resource selection when the institution filter changes", async () => {
        const filters = buildFilters({
            timeSeriesInstitutionId: 1,
            timeSeriesResourceGroupId: 10,
            timeSeriesResourceId: 100,
        });

        filters.onTimeSeriesInstitutionChange(2);
        await nextTick();

        expect(filters.selectedTimeSeriesResourceGroupId.value).toBeNull();
        expect(filters.selectedTimeSeriesResourceId.value).toBeNull();
        expect(routerGetMock).toHaveBeenCalledWith(expect.any(String), expect.objectContaining({ institution_id: 2 }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    });

    test("resets the resource selection when the resource group filter changes", async () => {
        const filters = buildFilters({ timeSeriesResourceGroupId: 10, timeSeriesResourceId: 100 });

        filters.onTimeSeriesResourceGroupChange(20);
        await nextTick();

        expect(filters.selectedTimeSeriesResourceId.value).toBeNull();
    });

    test("reloads immediately when a preset range is selected", async () => {
        const filters = buildFilters();

        filters.selectedRange.value = "last_7_days";
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({ range: "last_7_days" }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("does not reload when switching to a custom range", async () => {
        const filters = buildFilters();

        filters.selectedRange.value = "custom";
        await nextTick();

        expect(routerGetMock).not.toHaveBeenCalled();
    });

    test("omits from/to params for a preset range even if custom dates are set", () => {
        const filters = buildFilters();
        filters.customFrom.value = new Date(2026, 0, 1);
        filters.customTo.value = new Date(2026, 0, 31);

        filters.applyFilters();

        expect(routerGetMock).toHaveBeenCalledWith(
            expect.any(String),
            { range: "all", granularity: "month" },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("includes from/to params formatted as local dates for a custom range", () => {
        const filters = buildFilters({ range: "custom" });
        filters.customFrom.value = new Date(2026, 0, 5);
        filters.customTo.value = new Date(2026, 5, 20);

        filters.applyFilters();

        expect(routerGetMock).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({ from: "2026-01-05", to: "2026-06-20" }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("only includes compare params once both comparison dates are set and enabled", () => {
        const filters = buildFilters();
        filters.comparisonEnabled.value = true;
        filters.compareFrom.value = new Date(2026, 0, 1);

        filters.applyFilters();

        expect(routerGetMock).toHaveBeenLastCalledWith(
            expect.any(String),
            expect.not.objectContaining({ compare_from: expect.anything() }),
            { preserveState: true, preserveScroll: true, replace: true },
        );

        filters.compareTo.value = new Date(2026, 0, 31);
        filters.applyFilters();

        expect(routerGetMock).toHaveBeenLastCalledWith(
            expect.any(String),
            expect.objectContaining({ compare_from: "2026-01-01", compare_to: "2026-01-31" }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reloads without compare params once an active comparison is turned off", async () => {
        const filters = buildFilters({
            comparison: {
                from: "2026-01-01",
                to: "2026-01-31",
                currentCount: 1,
                comparisonCount: 1,
                deltaPct: 0,
                timeSeries: [],
                institutions: [],
                resourceGroups: [],
                resources: [],
            },
        });

        filters.comparisonEnabled.value = false;
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            expect.any(String),
            expect.not.objectContaining({ compare_from: expect.anything() }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reloads when the time series granularity changes", async () => {
        const filters = buildFilters();

        filters.selectedGranularity.value = "week";
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({ granularity: "week" }),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("builds an export url with the current filters and export type", () => {
        const filters = buildFilters({ timeSeriesInstitutionId: 1 });

        const url = filters.exportUrl("heatmap");

        expect(route).toHaveBeenCalledWith(
            "admin.statistics.export",
            expect.objectContaining({ type: "heatmap", institution_id: 1 }),
        );
        expect(url).toContain("admin.statistics.export");
    });
});

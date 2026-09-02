import StatisticsIndex from "@/Pages/Admin/Statistics/Index.vue";
import { useAppStore } from "@/Stores/AppStore";

import { mount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick } from "vue";

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string, replacements: Record<string, string> = {}) =>
        [key, ...Object.values(replacements)].filter(Boolean).join(" "),
    getActiveLanguage: () => "en",
}));

const routerGetMock = vi.fn();

vi.mock("@inertiajs/vue3", () => ({
    router: {
        get: (...args: unknown[]) => routerGetMock(...args),
    },
}));

const routeMock = vi.fn((name: string) => name);

let appStore: ReturnType<typeof useAppStore>;

beforeEach(() => {
    vi.clearAllMocks();
    window.localStorage.clear();

    setActivePinia(createPinia());
    appStore = useAppStore();
    vi.spyOn(appStore, "formatDate").mockImplementation((date) => date as string);
});

function buildHeatmapCells(overrides: { dayOfWeek: number; hour: number; count: number }[] = []) {
    const totalCount = overrides.reduce((total, cell) => total + cell.count, 0);

    return Array.from({ length: 7 }, (_, dayIndex) =>
        Array.from({ length: 24 }, (_, hour) => {
            const dayOfWeek = dayIndex + 1;
            const override = overrides.find((cell) => cell.dayOfWeek === dayOfWeek && cell.hour === hour);
            const count = override?.count ?? 0;

            return {
                dayOfWeek,
                hour,
                count,
                percentage: totalCount === 0 ? 0 : Math.round((count / totalCount) * 1000) / 10,
            };
        }),
    ).flat();
}

function buildComparison(props: Record<string, unknown> = {}) {
    return {
        from: "2026-01-01",
        to: "2026-01-31",
        currentCount: 5,
        comparisonCount: 3,
        deltaPct: 66.7,
        timeSeries: [
            { label: "2026-01", count: 1 },
            { label: "2026-02", count: 2 },
        ],
        institutions: [
            { id: 1, title: { en: "Institution A" }, count: 2, active: 2, cancelled: 0, cancellationRate: 0 },
            { id: 2, title: { en: "Institution B" }, count: 1, active: 1, cancelled: 0, cancellationRate: 0 },
        ],
        resourceGroups: [
            {
                id: 10,
                title: { en: "Group A" },
                institution_id: 1,
                count: 1,
                active: 1,
                cancelled: 0,
                cancellationRate: 0,
            },
            {
                id: 11,
                title: { en: "Group A2" },
                institution_id: 1,
                count: 1,
                active: 1,
                cancelled: 0,
                cancellationRate: 0,
            },
        ],
        resources: [
            {
                id: 100,
                title: { en: "Resource A" },
                resource_group_id: 10,
                count: 1,
                active: 1,
                cancelled: 0,
                cancellationRate: 0,
            },
            {
                id: 101,
                title: { en: "Resource A2" },
                resource_group_id: 10,
                count: 1,
                active: 1,
                cancelled: 0,
                cancellationRate: 0,
            },
        ],
        ...props,
    };
}

function render(props: Record<string, unknown> = {}) {
    return mount(StatisticsIndex, {
        props: {
            institutions: [
                { id: 1, title: { en: "Institution A" }, count: 4, active: 4, cancelled: 1, cancellationRate: 20 },
                { id: 2, title: { en: "Institution B" }, count: 1, active: 1, cancelled: 0, cancellationRate: 0 },
            ],
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 3,
                    active: 3,
                    cancelled: 1,
                    cancellationRate: 25,
                },
                {
                    id: 11,
                    title: { en: "Group A2" },
                    institution_id: 1,
                    count: 1,
                    active: 1,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
            resources: [
                {
                    id: 100,
                    title: { en: "Resource A" },
                    resource_group_id: 10,
                    count: 2,
                    active: 2,
                    cancelled: 1,
                    cancellationRate: 33.3,
                },
                {
                    id: 101,
                    title: { en: "Resource A2" },
                    resource_group_id: 10,
                    count: 1,
                    active: 1,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
            range: "all",
            from: null,
            to: null,
            timeSeries: [
                { label: "2026-06", count: 2 },
                { label: "2026-07", count: 3 },
            ],
            granularity: "month",
            cancellations: {
                cancelled: 1,
                active: 4,
                rate: 20,
                retentionDays: 1000,
                retentionExceeded: false,
            },
            heatmap: {
                cells: buildHeatmapCells([
                    { dayOfWeek: 1, hour: 10, count: 2 },
                    { dayOfWeek: 2, hour: 15, count: 1 },
                ]),
                maxCount: 2,
                totalCount: 3,
            },
            comparison: null,
            ...props,
        },
        global: {
            provide: {
                ziggyRoute: routeMock,
            },
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                Chart: {
                    name: "ChartStub",
                    props: ["type", "data", "options"],
                    template: "<div :data-test=\"$attrs['data-test'] ?? 'chart'\" />",
                },
                Select: {
                    name: "SelectStub",
                    props: ["modelValue", "options", "optionLabel", "optionValue", "disabled"],
                    emits: ["update:modelValue"],
                    template: "<div :data-test=\"$attrs['data-test'] ?? 'select'\" />",
                },
                DatePicker: {
                    name: "DatePickerStub",
                    props: ["modelValue"],
                    emits: ["update:modelValue"],
                    template: "<div :data-test=\"$attrs['data-test'] ?? 'date-picker'\" />",
                },
                Button: {
                    name: "ButtonStub",
                    props: ["label"],
                    template: "<button :data-test=\"$attrs['data-test'] ?? 'apply'\" @click=\"$emit('click')\" />",
                },
                ToggleSwitch: {
                    name: "ToggleSwitchStub",
                    props: ["modelValue"],
                    emits: ["update:modelValue"],
                    template:
                        "<button :data-test=\"$attrs['data-test'] ?? 'toggle-switch'\" @click=\"$emit('update:modelValue', !modelValue)\" />",
                },
            },
        },
    });
}

function getStub(wrapper: ReturnType<typeof render>, dataTest: string): VueWrapper {
    return wrapper.getComponent(`[data-test="${dataTest}"]`) as unknown as VueWrapper;
}

describe("Admin/Statistics/Index", () => {
    test("renders a bookings-over-time bar chart plus pie charts for institutions, resource groups and resources", () => {
        const wrapper = render();
        const charts = wrapper.findAllComponents({ name: "ChartStub" });

        expect(charts).toHaveLength(4);
        expect(charts[0]!.props("type")).toBe("bar");
        charts.slice(1).forEach((chart) => expect(chart.props("type")).toBe("pie"));

        const timeSeriesChart = charts[0]!.props("data") as { labels: string[] };
        expect(timeSeriesChart.labels).toEqual(["2026-06", "2026-07"]);

        const institutionChart = charts[1]!.props("data") as { labels: string[] };
        expect(institutionChart.labels).toEqual(["Institution A", "Institution B"]);
    });

    test("reloads with the selected preset range immediately", async () => {
        const wrapper = render();

        const rangeSelect = getStub(wrapper, "range-select");
        rangeSelect.vm.$emit("update:modelValue", "last_30_days");
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "last_30_days", granularity: "month" },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reloads with the selected time series granularity", async () => {
        const wrapper = render();

        const granularitySelect = getStub(wrapper, "granularity-select");
        granularitySelect.vm.$emit("update:modelValue", "week");
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "week" },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reloads with the selected time series institution filter", async () => {
        const wrapper = render();

        const timeSeriesInstitutionSelect = getStub(wrapper, "time-series-institution-select");
        timeSeriesInstitutionSelect.vm.$emit("update:modelValue", 2);
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "month", institution_id: 2 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reloads with the selected time series resource group filter", async () => {
        const wrapper = render();

        const timeSeriesResourceGroupSelect = getStub(wrapper, "time-series-resource-group-select");
        timeSeriesResourceGroupSelect.vm.$emit("update:modelValue", 10);
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "month", resource_group_id: 10 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reloads with the selected time series resource filter", async () => {
        const wrapper = render();

        const timeSeriesResourceSelect = getStub(wrapper, "time-series-resource-select");
        timeSeriesResourceSelect.vm.$emit("update:modelValue", 100);
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "month", resource_id: 100 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("changing the time series institution filter resets the resource group and resource selections", async () => {
        const wrapper = render({
            timeSeriesInstitutionId: 1,
            timeSeriesResourceGroupId: 10,
            timeSeriesResourceId: 100,
        });

        const timeSeriesInstitutionSelect = getStub(wrapper, "time-series-institution-select");
        timeSeriesInstitutionSelect.vm.$emit("update:modelValue", 2);
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "month", institution_id: 2 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("only reloads a custom range once the Apply button is clicked", async () => {
        const wrapper = render();

        const rangeSelect = getStub(wrapper, "range-select");
        rangeSelect.vm.$emit("update:modelValue", "custom");
        await nextTick();

        expect(routerGetMock).not.toHaveBeenCalled();

        const fromDatePicker = getStub(wrapper, "range-from-date-picker");
        const toDatePicker = getStub(wrapper, "range-to-date-picker");
        fromDatePicker.vm.$emit("update:modelValue", new Date(2026, 0, 15));
        toDatePicker.vm.$emit("update:modelValue", new Date(2026, 5, 20));
        await nextTick();

        await wrapper.get('[data-test="apply"]').trigger("click");

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "custom", from: "2026-01-15", to: "2026-06-20", granularity: "month" },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("reveals comparison date filters and reloads with compare params once applied", async () => {
        const wrapper = render();

        expect(wrapper.find('[data-test="comparison-fields"]').exists()).toBe(false);

        const comparisonToggle = getStub(wrapper, "comparison-toggle");
        comparisonToggle.vm.$emit("update:modelValue", true);
        await nextTick();

        expect(wrapper.find('[data-test="comparison-fields"]').exists()).toBe(true);

        const compareFromDatePicker = getStub(wrapper, "compare-from-date-picker");
        const compareToDatePicker = getStub(wrapper, "compare-to-date-picker");
        compareFromDatePicker.vm.$emit("update:modelValue", new Date(2026, 0, 1));
        compareToDatePicker.vm.$emit("update:modelValue", new Date(2026, 0, 31));
        await nextTick();

        await wrapper.get('[data-test="apply"]').trigger("click");

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "month", compare_from: "2026-01-01", compare_to: "2026-01-31" },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("turning off an active comparison reloads without compare params", async () => {
        const wrapper = render({ comparison: buildComparison() });

        expect((getStub(wrapper, "comparison-toggle").props() as { modelValue: unknown }).modelValue).toBe(true);

        const comparisonToggle = getStub(wrapper, "comparison-toggle");
        comparisonToggle.vm.$emit("update:modelValue", false);
        await nextTick();

        expect(routerGetMock).toHaveBeenCalledWith(
            "admin.statistics.index",
            { range: "all", granularity: "month" },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    });

    test("selecting an institution filters the resource group chart", async () => {
        const wrapper = render({
            institutions: [
                { id: 1, title: { en: "Institution A" }, count: 4, active: 4, cancelled: 0, cancellationRate: 0 },
                { id: 2, title: { en: "Institution B" }, count: 1, active: 1, cancelled: 0, cancellationRate: 0 },
            ],
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
                {
                    id: 20,
                    title: { en: "Group B" },
                    institution_id: 2,
                    count: 1,
                    active: 1,
                    cancelled: 1,
                    cancellationRate: 50,
                },
                {
                    id: 21,
                    title: { en: "Group B2" },
                    institution_id: 2,
                    count: 1,
                    active: 1,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
        });

        const institutionSelect = getStub(wrapper, "pie-institution-select");
        institutionSelect.vm.$emit("update:modelValue", 2);
        await nextTick();

        const charts = wrapper.findAllComponents({ name: "ChartStub" });
        const resourceGroupChart = charts[2]!.props("data") as { labels: string[] };
        expect(resourceGroupChart.labels).toEqual(["Group B", "Group B2"]);
    });

    test("persists the client-side chart drill-down selections", async () => {
        const wrapper = render({
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
                {
                    id: 20,
                    title: { en: "Group B" },
                    institution_id: 2,
                    count: 1,
                    active: 1,
                    cancelled: 1,
                    cancellationRate: 50,
                },
                {
                    id: 21,
                    title: { en: "Group B2" },
                    institution_id: 2,
                    count: 1,
                    active: 1,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
        });

        const institutionSelect = getStub(wrapper, "pie-institution-select");
        institutionSelect.vm.$emit("update:modelValue", 2);
        await nextTick();

        const resourceGroupSelect = getStub(wrapper, "pie-resource-group-select");
        resourceGroupSelect.vm.$emit("update:modelValue", 21);
        await nextTick();

        expect(window.localStorage.getItem("roomz.admin.statistics.selectedInstitutionId")).toBe("2");
        expect(window.localStorage.getItem("roomz.admin.statistics.selectedResourceGroupId")).toBe("21");
    });

    test("restores client-side chart drill-down selections after a page refresh", () => {
        window.localStorage.setItem("roomz.admin.statistics.selectedInstitutionId", "2");
        window.localStorage.setItem("roomz.admin.statistics.selectedResourceGroupId", "21");

        const wrapper = render({
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
                {
                    id: 20,
                    title: { en: "Group B" },
                    institution_id: 2,
                    count: 1,
                    active: 1,
                    cancelled: 1,
                    cancellationRate: 50,
                },
                {
                    id: 21,
                    title: { en: "Group B2" },
                    institution_id: 2,
                    count: 1,
                    active: 1,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
        });

        expect((getStub(wrapper, "pie-institution-select").props() as { modelValue: unknown }).modelValue).toBe(2);
        expect((getStub(wrapper, "pie-resource-group-select").props() as { modelValue: unknown }).modelValue).toBe(21);
    });

    test("hides the pie chart filter selects when there is only one entry to choose from", () => {
        const wrapper = render({
            institutions: [
                { id: 1, title: { en: "Institution A" }, count: 4, active: 4, cancelled: 0, cancellationRate: 0 },
            ],
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
        });

        expect(wrapper.find('[data-test="pie-institution-select"]').exists()).toBe(false);
        expect(wrapper.find('[data-test="pie-resource-group-select"]').exists()).toBe(false);
    });

    test("hides the time series filter selects when there is only one entry to choose from", () => {
        const wrapper = render({
            institutions: [
                { id: 1, title: { en: "Institution A" }, count: 4, active: 4, cancelled: 0, cancellationRate: 0 },
            ],
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
            resources: [
                {
                    id: 100,
                    title: { en: "Resource A" },
                    resource_group_id: 10,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
        });

        expect(wrapper.find('[data-test="time-series-institution-select"]').exists()).toBe(false);
        expect(wrapper.find('[data-test="time-series-resource-group-select"]').exists()).toBe(false);
        expect(wrapper.find('[data-test="time-series-resource-select"]').exists()).toBe(false);
    });

    test("renders a separate CSV export link for each statistic", () => {
        const wrapper = render();

        expect(wrapper.find('[data-test="export-time-series"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="export-institutions"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="export-resource-groups"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="export-resources"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="export-heatmap"]').exists()).toBe(true);
    });

    test("renders a peak-times heatmap with one cell per day and hour", () => {
        const wrapper = render();

        const cells = wrapper.findAll('[data-test="heatmap-cell"]');

        expect(wrapper.find('[data-test="peak-times-heatmap"]').exists()).toBe(true);
        expect(cells).toHaveLength(168);
        expect(cells[10]!.attributes("aria-label")).toContain("10:00");
        expect(cells[10]!.attributes("aria-label")).toContain("2");
        expect(cells[10]!.attributes("aria-label")).toContain("66.7%");
        expect(cells[10]!.text()).toContain("66.7%");
    });

    test("renders period comparison charts and summary values", () => {
        const wrapper = render({ comparison: buildComparison() });

        expect(wrapper.find('[data-test="comparison-time-series"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="comparison-time-series-chart"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="comparison-delta"]').text()).toContain("+66.7%");
        expect(wrapper.find('[data-test="current-period-counts"]').text()).toContain("5");
        expect(wrapper.find('[data-test="comparison-counts"]').text()).toContain("3");
        expect(wrapper.find('[data-test="institution-comparison"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="resource-group-comparison"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="resource-comparison"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="comparison-institution-chart"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="comparison-resource-group-chart"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="comparison-resource-chart"]').exists()).toBe(true);
        expect(wrapper.findAllComponents({ name: "ChartStub" })).toHaveLength(8);
    });

    test("renders cancellation status for institutions, resource groups and resources", () => {
        const wrapper = render();

        const institutionRows = wrapper.findAll('[data-test="institution-cancellation-row"]');
        expect(institutionRows).toHaveLength(2);
        expect(institutionRows[0]!.text()).toContain("Institution A");
        expect(institutionRows[0]!.text()).toContain("20%");

        const resourceGroupRows = wrapper.findAll('[data-test="resource-group-cancellation-row"]');
        expect(resourceGroupRows).toHaveLength(2);
        expect(resourceGroupRows[0]!.text()).toContain("Group A");
        expect(resourceGroupRows[0]!.text()).toContain("25%");

        const resourceRows = wrapper.findAll('[data-test="resource-cancellation-row"]');
        expect(resourceRows).toHaveLength(2);
        expect(resourceRows[0]!.text()).toContain("Resource A");
        expect(resourceRows[0]!.text()).toContain("33.3%");
    });

    test("shows the cancellation retention notice when the selected range exceeds cleanup retention", () => {
        const wrapper = render({
            cancellations: {
                cancelled: 1,
                active: 4,
                rate: 20,
                retentionDays: 1000,
                retentionExceeded: true,
            },
        });

        expect(wrapper.find('[data-test="retention-notice"]').exists()).toBe(true);
    });

    test("shows a single entry as text instead of a chart with only one item", () => {
        const wrapper = render({
            institutions: [
                { id: 1, title: { en: "Institution A" }, count: 4, active: 4, cancelled: 0, cancellationRate: 0 },
            ],
            resourceGroups: [
                {
                    id: 10,
                    title: { en: "Group A" },
                    institution_id: 1,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
            resources: [
                {
                    id: 100,
                    title: { en: "Resource A" },
                    resource_group_id: 10,
                    count: 4,
                    active: 4,
                    cancelled: 0,
                    cancellationRate: 0,
                },
            ],
            timeSeries: [],
        });

        expect(wrapper.findAllComponents({ name: "ChartStub" })).toHaveLength(0);
        expect(wrapper.text()).toContain("admin.statistics.index.single_entry");
    });
});

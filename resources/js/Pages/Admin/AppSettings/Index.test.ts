import AppSettingsIndex from "@/Pages/Admin/AppSettings/Index.vue";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent, h, nextTick } from "vue";

const transChoiceMock = vi.fn((key: string, count: number, params: { count: string }) => {
    return `${key}:${count}:${params.count}`;
});

vi.mock("laravel-vue-i18n", () => ({
    transChoice: (key: string, count: number, params: { count: string }) => transChoiceMock(key, count, params),
}));

let processedDataOverride: unknown[] | undefined;

const DataTableStub = defineComponent({
    name: "DataTableStub",
    props: {
        value: {
            type: Array,
            default: (): unknown[] => [],
        },
    },
    setup(_, { slots, expose }) {
        expose(
            processedDataOverride === undefined
                ? {}
                : {
                      processedData: processedDataOverride,
                  },
        );

        return () => h("div", { "data-test": "data-table" }, [slots.header?.(), slots.default?.()]);
    },
});

beforeEach(() => {
    vi.clearAllMocks();
    processedDataOverride = undefined;
});

function render(settings: Array<Record<string, unknown>>) {
    return mount(AppSettingsIndex, {
        props: {
            settings,
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                ActionLink: true,
                Column: true,
                DataTable: DataTableStub,
            },
        },
    });
}

describe("AppSettings/Index", () => {
    test("uses the props length when the DataTable has no processed data yet", () => {
        const wrapper = render([{ key: "one" }, { key: "two" }]);

        expect(transChoiceMock).toHaveBeenLastCalledWith("admin.general.records_count", 2, { count: "2" });
        expect(wrapper.text()).toContain("admin.general.records_count:2:2");
    });

    test("uses the processed table length when the DataTable exposes processed data", async () => {
        processedDataOverride = [{ key: "one" }, { key: "two" }, { key: "three" }];

        const wrapper = render([{ key: "one" }]);
        await nextTick();

        expect(transChoiceMock).toHaveBeenLastCalledWith("admin.general.records_count", 3, { count: "3" });
        expect(wrapper.text()).toContain("admin.general.records_count:3:3");
    });
});

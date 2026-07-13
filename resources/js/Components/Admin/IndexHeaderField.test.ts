import IndexHeaderField from "@/Components/Admin/IndexHeaderField.vue";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test } from "vitest";

beforeEach(() => {
    document.body.innerHTML = "";
});

function render(props: Record<string, unknown> = {}, slots: Record<string, string> = {}) {
    return mount(IndexHeaderField, {
        attachTo: document.body,
        props: {
            label: "Name",
            sortDirection: "desc",
            ...props,
        },
        slots,
        global: {
            stubs: {
                SortDirectionIcon: true,
            },
        },
    });
}

describe("IndexHeaderField", () => {
    test("emits sort when the header is clicked for a non-sort field", async () => {
        const wrapper = render();

        await wrapper.get("div").trigger("click");

        expect(wrapper.emitted("sort")).toEqual([[]]);
    });

    test("emits the toggled sort direction when the header is clicked for a sort field", async () => {
        const wrapper = render({
            isSortField: true,
            sortDirection: "desc",
        });

        await wrapper.get("div").trigger("click");

        expect(wrapper.emitted("update:sort-direction")).toEqual([["asc"]]);
    });

    test("shows the filter input, focuses it, and emits visibility when the filter button is clicked", async () => {
        const wrapper = render({ isFilterField: true });

        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("toggle-filter")).toEqual([[true]]);
        const input = wrapper.get("input");
        expect(input.element).toBe(document.activeElement);
    });

    test("emits updated filter text from the input", async () => {
        const wrapper = render({ isFilterField: true, filter: "old" });

        await wrapper.get("button").trigger("click");
        await wrapper.get("input").setValue("new");

        expect(wrapper.emitted("update:filter")).toEqual([["new"]]);
    });

    test("toggles the filter closed on escape", async () => {
        const wrapper = render({ isFilterField: true });

        await wrapper.get("button").trigger("click");
        await wrapper.get("input").trigger("keyup.escape");

        expect(wrapper.emitted("toggle-filter")).toEqual([[true], [false]]);
    });

    test("renders slot content instead of the label when provided", () => {
        const wrapper = render({}, { default: "Custom Label" });

        expect(wrapper.text()).toContain("Custom Label");
        expect(wrapper.text()).not.toContain("Name");
    });
});

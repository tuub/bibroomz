import Paginator from "@/Components/Admin/Paginator.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test } from "vitest";

function render(props: Record<string, unknown> = {}) {
    return mount(Paginator, {
        props: {
            currentPage: 2,
            lastPage: 12,
            nextPage: 3,
            prevPage: 1,
            perPage: 20,
            ...props,
        },
    });
}

describe("Paginator", () => {
    test("emits page-changed for the navigation buttons", async () => {
        const wrapper = render();
        const buttons = wrapper.findAll("button");

        await buttons[0].trigger("click");
        await buttons[1].trigger("click");
        await buttons[2].trigger("click");
        await buttons[3].trigger("click");

        expect(wrapper.emitted("page-changed")).toEqual([[1], [1], [3], [12]]);
    });

    test("emits page-changed with the typed input value", async () => {
        const wrapper = render();

        await wrapper.get("input").setValue("9");

        expect(wrapper.emitted("page-changed")).toContainEqual(["9"]);
    });

    test("emits update:per-page for preset buttons and show all", async () => {
        const wrapper = render({ perPage: 20 });
        const buttons = wrapper.findAll("button");

        await buttons[4].trigger("click");
        await buttons[6].trigger("click");
        await buttons[7].trigger("click");

        expect(wrapper.emitted("update:per-page")).toEqual([[10], [30], [-1]]);
    });

    test("sizes the page input based on the last page length", () => {
        const wrapper = render({ lastPage: 999 });

        expect(wrapper.get("input").attributes("style")).toContain("width: 3em");
    });
});

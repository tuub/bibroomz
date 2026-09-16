import Breadcrumbs from "@/Shared/Breadcrumbs.vue";

import { mount } from "@vue/test-utils";
import PrimeVue from "primevue/config";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { ref } from "vue";

const pageProps = ref<{ route?: string }>({});

vi.mock("@inertiajs/vue3", async () => {
    const { defineComponent, h } = await import("vue");

    return {
        usePage: () => ({ props: pageProps.value }),
        Link: defineComponent({
            name: "InertiaLink",
            props: {
                href: { type: String, required: true },
            },
            setup:
                (props, { slots }) =>
                () =>
                    h("a", { href: props.href }, slots.default?.()),
        }),
    };
});

beforeEach(() => {
    pageProps.value = {};
});

function render(route?: string) {
    pageProps.value = { route };

    return mount(Breadcrumbs, {
        global: {
            plugins: [PrimeVue],
            provide: {
                ziggyRoute: (name: string) => `/${name}`,
            },
        },
    });
}

describe("Breadcrumbs", () => {
    test("handles a missing route without rendering crumbs", () => {
        const wrapper = render();

        expect(wrapper.findAll(".p-breadcrumb-item-label")).toHaveLength(0);
        expect(wrapper.find(".p-breadcrumb-home-item a").attributes("href")).toBe("/start");
    });

    test("links the home icon back to the start page", () => {
        const wrapper = render("site_credits");

        const homeLink = wrapper.find(".p-breadcrumb-home-item a");

        expect(homeLink.attributes("href")).toBe("/start");
        expect(homeLink.find(".p-breadcrumb-item-icon").classes()).toContain("pi-home");
    });

    test("maps known routes into linked breadcrumb items", () => {
        const wrapper = render("site_credits");

        const crumbs = wrapper.findAll(".p-breadcrumb-item:not(.p-breadcrumb-home-item) a");

        expect(crumbs).toHaveLength(1);
        expect(crumbs[0]!.text()).toBe("IMPRINT");
        expect(crumbs[0]!.attributes("href")).toBe("/site_credits");
    });
});

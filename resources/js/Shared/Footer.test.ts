import Footer from "@/Shared/Footer.vue";

import { mount } from "@vue/test-utils";
import { describe, expect, test, vi } from "vitest";

vi.mock("@inertiajs/vue3", () => ({
    Link: {
        name: "Link",
        props: ["href"],
        template: '<a :href="href"><slot /></a>',
    },
}));

function render() {
    return mount(Footer, {
        global: {
            provide: {
                ziggyRoute: (name: string) => `/${name}`,
            },
            mocks: {
                $t: (key: string) => key,
            },
        },
    });
}

describe("Footer", () => {
    test("uses theme-aware shell colors", () => {
        const wrapper = render();

        expect(wrapper.get("footer").classes()).toEqual(
            expect.arrayContaining([
                "bg-app-surface",
                "text-tub",
                "dark:bg-app-surface",
                "dark:text-app-text",
                "border-app-border",
                "dark:border-app-border",
            ]),
        );
    });

    test("renders footer navigation links", () => {
        const wrapper = render();
        const links = wrapper.findAll("a");

        expect(links[0]!.attributes("href")).toBe("https://www.tu.berlin/datenschutz");
        expect(links[1]!.attributes("href")).toBe("/site_credits");
    });
});

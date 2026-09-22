import TerminalView from "@/Pages/TerminalView.vue";
import { useAppStore } from "@/Stores/AppStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@/Components/Calendar/ResourceGrid.vue", () => ({
    default: {
        name: "ResourceGridStub",
        props: ["interactive"],
        template: '<div data-test="resource-grid"></div>',
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

function render() {
    return mount(TerminalView, {
        props: {
            resourceGroup: {
                slug: "rooms",
                institution: {
                    slug: "tu-berlin",
                },
            },
            settings: {
                institution: {},
            },
            hiddenDays: [0, 6],
        },
    });
}

describe("TerminalView", () => {
    test("initializes the app store from typed props", () => {
        const appStore = useAppStore();
        const setCurrentSpy = vi.spyOn(appStore, "setCurrent");

        render();

        expect(setCurrentSpy).toHaveBeenCalledWith(
            {
                slug: "rooms",
                institution: {
                    slug: "tu-berlin",
                },
            },
            {
                institution: {},
            },
            [0, 6],
            false,
        );
    });

    test("renders the same calendar the public page does", () => {
        const wrapper = render();

        expect(wrapper.find('[data-test="resource-grid"]').exists()).toBe(true);
    });

    // The kiosk is unattended: nobody is there to log in, so a slot selection or
    // an event click could only ever open a modal that then sits on screen.
    test("switches the calendar into its unattended mode", () => {
        const wrapper = render();

        expect(wrapper.findComponent({ name: "ResourceGridStub" }).props("interactive")).toBe(false);
    });
});

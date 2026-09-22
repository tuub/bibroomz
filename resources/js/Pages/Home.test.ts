import Home from "@/Pages/Home.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useModal } from "@/Stores/Modal";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent } from "vue";

const ResourceGridStub = defineComponent({
    name: "ResourceGridStub",
    emits: ["open-modal-component"],
    template: `
        <button
            data-test="resource-grid"
            @click="$emit('open-modal-component', {
                view: { name: 'TestView' },
                content: { title: 'Inspect' },
                payload: { id: 42 },
                actions: [{ label: 'ok' }]
            })"
        >
            open
        </button>
    `,
});

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

function render() {
    return mount(Home, {
        props: {
            resourceGroup: {
                id: 1,
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
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                ResourceGrid: ResourceGridStub,
                Sidebar: true,
                SystemNotificationList: true,
                Teleport: true,
            },
        },
    });
}

describe("Home", () => {
    test("initializes the app store with the page context", () => {
        const appStore = useAppStore();
        const setCurrentSpy = vi.spyOn(appStore, "setCurrent");

        render();

        expect(setCurrentSpy).toHaveBeenCalledWith(
            {
                id: 1,
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

    test("renders the calendar", () => {
        const wrapper = render();

        expect(wrapper.find('[data-test="resource-grid"]').exists()).toBe(true);
    });

    test("forwards calendar modal events to the modal store", async () => {
        const modalStore = useModal();
        const openSpy = vi.spyOn(modalStore, "open");
        const wrapper = render();

        await wrapper.find('[data-test="resource-grid"]').trigger("click");

        expect(openSpy).toHaveBeenCalledWith({ name: "TestView" }, { title: "Inspect" }, { id: 42 }, [{ label: "ok" }]);
    });
});

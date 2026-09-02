import MainLayout from "@/Layouts/MainLayout.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { useToastStore } from "@/Stores/ToastStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";
import type { Mock } from "vitest";

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({ props: { systemNotification: "Maintenance tonight" } }),
}));

let appStore: ReturnType<typeof useAppStore>;
let authStore: ReturnType<typeof useAuthStore>;
let toastStore: ReturnType<typeof useToastStore>;

let setGlobalSystemNotificationSpy: Mock<typeof appStore.setGlobalSystemNotification>;
let checkSpy: Mock<typeof authStore.check>;
let unsubscribeSpy: Mock<typeof authStore.unsubscribe>;
let initToastSpy: Mock<typeof toastStore.initToast>;
let removeToastMessageSpy: Mock<typeof toastStore.removeToastMessage>;

beforeEach(() => {
    setActivePinia(createPinia());
    appStore = useAppStore();
    authStore = useAuthStore();
    toastStore = useToastStore();

    setGlobalSystemNotificationSpy = vi.spyOn(appStore, "setGlobalSystemNotification");
    checkSpy = vi.spyOn(authStore, "check").mockResolvedValue(undefined);
    unsubscribeSpy = vi.spyOn(authStore, "unsubscribe").mockImplementation(() => undefined);
    initToastSpy = vi.spyOn(toastStore, "initToast").mockImplementation(() => undefined);
    removeToastMessageSpy = vi.spyOn(toastStore, "removeToastMessage");
});

function render(slots: Record<string, string> = { default: '<div data-test="page-content">Page</div>' }) {
    return mount(MainLayout, {
        shallow: true,
        global: {
            mocks: { $t: (key: string) => key },
            stubs: { Head: true },
        },
        slots,
    });
}

afterEach(() => {
    vi.clearAllMocks();
});

describe("MainLayout", () => {
    test("checks auth and forwards the system notification to the app store on mount", () => {
        render();

        expect(checkSpy).toHaveBeenCalledOnce();
        expect(setGlobalSystemNotificationSpy).toHaveBeenCalledWith("Maintenance tonight");
    });

    test("initializes the toast store once mounted", () => {
        render();

        expect(initToastSpy).toHaveBeenCalledOnce();
    });

    test("unsubscribes from the auth store on unmount", () => {
        const wrapper = render();

        wrapper.unmount();

        expect(unsubscribeSpy).toHaveBeenCalledOnce();
    });

    test("renders the breadcrumbs slot above the page content", () => {
        const wrapper = render({
            breadcrumbs: '<div data-test="breadcrumbs">Crumbs</div>',
            default: '<div data-test="page-content">Page</div>',
        });
        const html = wrapper.html();

        expect(html.indexOf('data-test="breadcrumbs"')).toBeLessThan(html.indexOf('data-test="page-content"'));
    });

    test("renders the default slot content inside the content section", () => {
        const wrapper = render({ default: '<div data-test="page-content">Page</div>' });

        expect(wrapper.find('[data-test="page-content"]').exists()).toBe(true);
        expect(wrapper.find('[data-test="page-content"]').text()).toBe("Page");
    });

    test("renders the header and footer", () => {
        const wrapper = render();

        expect(wrapper.findComponent({ name: "Header" }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: "Footer" }).exists()).toBe(true);
    });

    test("forwards toast close and life-end events to the toast store", async () => {
        const wrapper = render();
        const toast = wrapper.findComponent({ name: "Toast" });

        const message = { id: "auth" };
        await toast.vm.$emit("close", { message });
        await toast.vm.$emit("life-end", { message });

        expect(removeToastMessageSpy).toHaveBeenCalledTimes(2);
        expect(removeToastMessageSpy).toHaveBeenCalledWith({ message });
    });
});

import AdminLayout from "@/Layouts/AdminLayout.vue";

import { mount } from "@vue/test-utils";
import { afterEach, describe, expect, test, vi } from "vitest";

const appStoreMock = {
    appName: "BibRoomz",
    setGlobalSystemNotification: vi.fn(),
};
const authStoreMock = {
    check: vi.fn(),
    unsubscribe: vi.fn(),
};
const toastStoreMock = {
    initToast: vi.fn(),
    removeToastMessage: vi.fn(),
};

vi.mock("@/Stores/AppStore", () => ({ useAppStore: () => appStoreMock }));
vi.mock("@/Stores/AuthStore", () => ({ useAuthStore: () => authStoreMock }));
vi.mock("@/Stores/ToastStore", () => ({ useToastStore: () => toastStoreMock }));

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({ props: { systemNotification: "Maintenance tonight" } }),
    Head: { name: "Head", template: "<slot />" },
}));

function render(slotContent = '<div data-test="page-content">Page</div>') {
    return mount(AdminLayout, {
        shallow: true,
        global: {
            mocks: { $t: (key: string) => key },
        },
        slots: { default: slotContent },
    });
}

afterEach(() => {
    vi.clearAllMocks();
});

describe("AdminLayout", () => {
    test("checks auth and forwards the system notification to the app store on mount", () => {
        render();

        expect(authStoreMock.check).toHaveBeenCalledOnce();
        expect(appStoreMock.setGlobalSystemNotification).toHaveBeenCalledWith("Maintenance tonight");
    });

    test("initializes the toast store once mounted", () => {
        render();

        expect(toastStoreMock.initToast).toHaveBeenCalledOnce();
    });

    test("unsubscribes from the auth store on unmount", () => {
        const wrapper = render();

        wrapper.unmount();

        expect(authStoreMock.unsubscribe).toHaveBeenCalledOnce();
    });

    test("renders the admin breadcrumbs above the page content", () => {
        const wrapper = render();
        const html = wrapper.html();

        expect(wrapper.findComponent({ name: "AdminBreadcrumbs" }).exists()).toBe(true);
        expect(html.indexOf("admin-breadcrumbs-stub")).toBeLessThan(html.indexOf('data-test="page-content"'));
    });

    test("renders the default slot content inside the content section", () => {
        const wrapper = render('<div data-test="page-content">Page</div>');

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

        expect(toastStoreMock.removeToastMessage).toHaveBeenCalledTimes(2);
        expect(toastStoreMock.removeToastMessage).toHaveBeenCalledWith({ message });
    });
});

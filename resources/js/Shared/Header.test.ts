import Header from "@/Shared/Header.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

const routerPostMock = vi.fn();

vi.mock("@inertiajs/vue3", () => ({
    router: {
        post: (...args: unknown[]) => routerPostMock(...args),
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    routerPostMock.mockClear();
});

function render({
    institution = {
        logo_uri: "/logo.svg",
    },
    isMultiTenancy = true,
    isPrivileged = true,
    isImpersonating = false,
}: {
    institution?: Record<string, unknown> | null;
    isMultiTenancy?: boolean;
    isPrivileged?: boolean;
    isImpersonating?: boolean;
} = {}) {
    const appStore = useAppStore();
    appStore.appName = "BibRoomz";
    appStore.resourceGroup = institution ? { institution } : null;
    appStore.isMultiTenancy = isMultiTenancy;

    const authStore = useAuthStore();
    authStore.isAdmin = isPrivileged;
    authStore.permissions = isPrivileged ? { "1": ["view_users"] } : {};
    authStore.isImpersonating = isImpersonating;
    authStore.user = { id: 1, name: "Alice" };

    return mount(Header, {
        global: {
            provide: {
                ziggyRoute: (name: string) => `/${name}`,
            },
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                Head: true,
                Button: {
                    props: ["ariaLabel"],
                    emits: ["click"],
                    template: '<button data-test="drawer-button" @click="$emit(\'click\')"><slot /></button>',
                },
                Drawer: {
                    name: "DrawerStub",
                    props: ["visible"],
                    template: '<div data-test="drawer"><slot name="container" :close-callback="() => {}" /></div>',
                },
                NavigationBar: {
                    name: "NavigationBarStub",
                    props: ["isResponsive", "isPrivileged", "isMultiTenancy"],
                    template:
                        '<div data-test="navigation-bar">{{ isResponsive }}|{{ isPrivileged }}|{{ isMultiTenancy }}</div>',
                },
            },
        },
    });
}

describe("Header", () => {
    test("renders the institution logo and the start link when an institution is present", () => {
        const wrapper = render();

        expect(wrapper.get("img").attributes("src")).toBe("/logo.svg");
        expect(wrapper.get(".brand-name").attributes("href")).toBe("/start");
        expect(wrapper.text()).toContain("BibRoomz");
    });

    test("hides the institution logo when there is no institution", () => {
        const wrapper = render({ institution: null });

        expect(wrapper.find("img").exists()).toBe(false);
    });

    test("passes privilege and multi-tenancy flags through to the navigation bars", () => {
        const wrapper = render({ isPrivileged: true, isMultiTenancy: false });
        const navigationBars = wrapper.findAllComponents({ name: "NavigationBarStub" });

        expect(navigationBars).toHaveLength(2);
        expect(navigationBars[0]!.props()).toMatchObject({
            isResponsive: false,
            isPrivileged: true,
            isMultiTenancy: false,
        });
        expect(navigationBars[1]!.props()).toMatchObject({
            isResponsive: false,
            isPrivileged: true,
            isMultiTenancy: false,
        });
    });

    test("opens the responsive drawer when the menu button is clicked", async () => {
        const wrapper = render();
        const drawer = wrapper.findComponent({ name: "DrawerStub" });

        expect(drawer.props("visible")).toBe(false);

        await wrapper.get('[data-test="drawer-button"]').trigger("click");

        expect(drawer.props("visible")).toBe(true);
    });

    test("hides the impersonation banner when not impersonating", () => {
        const wrapper = render({ isImpersonating: false });

        expect(wrapper.text()).not.toContain("impersonation.banner.message");
    });

    test("shows the impersonation banner and stops impersonating on click", async () => {
        const wrapper = render({ isImpersonating: true });

        expect(wrapper.text()).toContain("impersonation.banner.message");

        await wrapper.get("button.underline").trigger("click");

        expect(routerPostMock).toHaveBeenCalledWith(
            "/admin.impersonate.stop",
            {},
            expect.objectContaining({ onSuccess: expect.any(Function) }),
        );
    });
});

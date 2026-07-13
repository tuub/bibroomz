import NavigationBar from "@/Shared/Navigation/NavigationBar.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

const loginUserMock = vi.fn();
const logoutUserMock = vi.fn();

vi.mock("@/Composables/Login", () => ({
    useLogin: () => ({
        loginUser: loginUserMock,
        logoutUser: logoutUserMock,
    }),
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

function render({
    isPrivileged = false,
    isAuthenticated = false,
    userName = "Alice",
    isResponsive = false,
}: {
    isPrivileged?: boolean;
    isAuthenticated?: boolean;
    userName?: string;
    isResponsive?: boolean;
} = {}) {
    const authStore = useAuthStore();
    authStore.isAuthenticated = isAuthenticated;
    authStore.user = { name: userName };

    return mount(NavigationBar, {
        props: {
            isPrivileged,
            isResponsive,
            isMultiTenancy: true,
        },
        global: {
            provide: {
                ziggyRoute: (name: string) => `/${name}`,
            },
            mocks: {
                $t: (key: string) => {
                    if (key === "navigation.help.uri") {
                        return "/help";
                    }

                    return key;
                },
            },
            stubs: {
                ExternalLink: {
                    props: ["href", "icon", "title"],
                    template: '<a :href="href"><slot /></a>',
                },
                InternalLink: {
                    props: ["href", "icon", "title"],
                    template: '<a :href="href"><slot /></a>',
                },
                LanguageSwitch: {
                    template: '<div data-test="language-switch"></div>',
                },
            },
        },
    });
}

describe("NavigationBar", () => {
    test("renders home, help, and admin links from the injected route helper", () => {
        const wrapper = render({ isPrivileged: true });
        const links = wrapper.findAll("a");
        expect(links).toHaveLength(4);

        expect(links[0]!.attributes("href")).toBe("/start");
        expect(links[1]!.attributes("href")).toBe("/help");
        expect(links[2]!.attributes("href")).toBe("/admin.dashboard");
        expect(links[3]!.attributes("href")).toBe("#");
    });

    test("hides the admin link when the user is not privileged", () => {
        const wrapper = render({ isPrivileged: false });

        expect(wrapper.text()).not.toContain("navigation.admin");
    });

    test("delegates guest auth clicks to loginUser", async () => {
        const wrapper = render({ isAuthenticated: false });

        expect(wrapper.get("#auth").text()).toContain("navigation.login");

        await wrapper.get("#auth").trigger("click");

        expect(loginUserMock).toHaveBeenCalledOnce();
        expect(logoutUserMock).not.toHaveBeenCalled();
    });

    test("shows the authenticated user name and delegates clicks to logoutUser", async () => {
        const wrapper = render({ isAuthenticated: true, userName: "Bob" });

        expect(wrapper.get("#auth").text()).toContain("navigation.logout");
        expect(wrapper.get("#auth").text()).toContain("(Bob)");

        await wrapper.get("#auth").trigger("click");

        expect(logoutUserMock).toHaveBeenCalledOnce();
        expect(loginUserMock).not.toHaveBeenCalled();
    });

    test("switches its list item layout when rendered responsively", () => {
        const wrapper = render({ isResponsive: true });

        expect(wrapper.find("li").classes()).toContain("block");
    });
});

import Start from "@/Pages/Start.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

const loginUserMock = vi.fn();

vi.mock("@/Composables/Login", () => ({
    useLogin: () => ({
        loginUser: loginUserMock,
    }),
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

function render() {
    return mount(Start, {
        props: {
            appName: "BibRoomz",
            institutions: [{ id: 1 }, { id: 2 }],
        },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                Head: true,
                InstitutionCard: true,
                SystemNotificationList: true,
                ExternalLink: true,
            },
        },
    });
}

describe("Start", () => {
    test("sets the start page context from the page prop", () => {
        const appStore = useAppStore();
        const setStartPageContextSpy = vi.spyOn(appStore, "setStartPageContext");

        render();

        expect(setStartPageContextSpy).toHaveBeenCalledWith("BibRoomz");
    });

    test("shows the login link for guests and delegates clicks to the login composable", async () => {
        const authStore = useAuthStore();
        authStore.isAuthenticated = false;

        const wrapper = render();

        expect(wrapper.find('[data-testid="start-login-link"]').exists()).toBe(true);

        await wrapper.find('[data-testid="start-login-link"]').trigger("click");

        expect(loginUserMock).toHaveBeenCalledOnce();
    });

    test("hides the login link for authenticated users", () => {
        const authStore = useAuthStore();
        authStore.isAuthenticated = true;

        const wrapper = render();

        expect(wrapper.find('[data-testid="start-login-link"]').exists()).toBe(false);
    });
});

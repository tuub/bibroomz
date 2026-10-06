import CreateLink from "@/Components/Admin/Index/CreateLink.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

const routerVisitMock = vi.fn();
const routeMock = vi.fn((name: string) => `/${name}`);

vi.mock("@inertiajs/vue3", () => ({
    router: {
        visit: (...args: unknown[]) => routerVisitMock(...args),
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

function render(props: { model: string; institutionId?: string; params?: Record<string, unknown> }) {
    return mount(CreateLink, {
        props,
        global: {
            provide: {
                ziggyRoute: routeMock,
            },
            stubs: {
                Button: {
                    props: ["label"],
                    emits: ["click"],
                    template: "<button @click=\"$emit('click')\">{{ label }}</button>",
                },
            },
        },
    });
}

describe("CreateLink", () => {
    test("shows global create actions when the permission exists in any assignment", () => {
        const authStore = useAuthStore();
        authStore.permissions = { "institution-1": ["create_roles"] };

        expect(render({ model: "role" }).find("button").exists()).toBe(true);
    });

    test("scopes create actions to the supplied institution", () => {
        const authStore = useAuthStore();
        authStore.permissions = { "institution-1": ["create_resources"] };

        expect(render({ model: "resource", institutionId: "institution-1" }).find("button").exists()).toBe(true);
        expect(render({ model: "resource", institutionId: "institution-2" }).find("button").exists()).toBe(false);
    });

    test("visits the create route with its context parameters", async () => {
        const authStore = useAuthStore();
        authStore.permissions = { "institution-1": ["create_mails"] };
        const params = { institution_id: "institution-1" };
        const wrapper = render({ model: "mail", institutionId: "institution-1", params });

        await wrapper.get("button").trigger("click");

        expect(routeMock).toHaveBeenCalledWith("admin.mail.create", params);
        expect(routerVisitMock).toHaveBeenCalledWith("/admin.mail.create");
    });
});

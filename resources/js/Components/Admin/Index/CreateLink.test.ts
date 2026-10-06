import CreateLink from "@/Components/Admin/Index/CreateLink.vue";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";

const routerVisitMock = vi.fn();
const routeMock = vi.fn((name: string) => `/${name}`);

vi.mock("@inertiajs/vue3", () => ({
    router: {
        visit: (...args: unknown[]) => routerVisitMock(...args),
    },
}));

beforeEach(() => {
    vi.clearAllMocks();
});

function render(props: { model: string; params?: Record<string, unknown> }) {
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
    test("renders the create action selected by its parent", () => {
        expect(render({ model: "role" }).find("button").exists()).toBe(true);
    });

    test("visits the create route with its context parameters", async () => {
        const params = { institution_id: "institution-1" };
        const wrapper = render({ model: "mail", params });

        await wrapper.get("button").trigger("click");

        expect(routeMock).toHaveBeenCalledWith("admin.mail.create", params);
        expect(routerVisitMock).toHaveBeenCalledWith("/admin.mail.create");
    });
});

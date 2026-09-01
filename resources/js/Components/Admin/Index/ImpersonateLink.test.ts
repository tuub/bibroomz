import ImpersonateLink from "@/Components/Admin/Index/ImpersonateLink.vue";
import { useModal } from "@/Stores/Modal";

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
    routeMock.mockClear();
});

const routeMock = vi.fn((name: string) => `/${name}`);

function render(params: Record<string, unknown> = { id: "user-1" }) {
    return mount(ImpersonateLink, {
        props: { params },
        global: {
            provide: {
                ziggyRoute: routeMock,
            },
            mocks: {
                $t: (key: string) => key,
            },
        },
    });
}

describe("ImpersonateLink", () => {
    test("opens the confirm modal when clicked", async () => {
        const wrapper = render();
        const modal = useModal();

        await wrapper.get("button").trigger("click");

        expect(modal.isOpen).toBe(true);
        expect(modal.actions).toHaveLength(2);
    });

    test("posts to the impersonate route and reloads the page on confirm", async () => {
        const reloadMock = vi.fn();
        vi.stubGlobal("location", { ...window.location, reload: reloadMock });

        const wrapper = render({ id: "user-1" });
        const modal = useModal();

        await wrapper.get("button").trigger("click");
        const confirmAction = modal.actions?.[0];
        await confirmAction?.callback(undefined);

        expect(routeMock).toHaveBeenCalledWith("admin.user.impersonate", { id: "user-1" });
        expect(routerPostMock).toHaveBeenCalledWith(
            "/admin.user.impersonate",
            {},
            expect.objectContaining({ onStart: expect.any(Function), onSuccess: expect.any(Function) }),
        );

        const options = routerPostMock.mock.calls[0]?.[2] as { onStart: () => void; onSuccess: () => void };
        options.onSuccess();

        expect(reloadMock).toHaveBeenCalled();

        vi.unstubAllGlobals();
    });

    test("closes the modal via onStart before the response arrives", async () => {
        const wrapper = render();
        const modal = useModal();

        await wrapper.get("button").trigger("click");
        expect(modal.isOpen).toBe(true);

        const confirmAction = modal.actions?.[0];
        await confirmAction?.callback(undefined);

        const options = routerPostMock.mock.calls[0]?.[2] as { onStart: () => void };
        options.onStart();

        expect(modal.isOpen).toBe(false);
    });

    test("closes the modal without posting when cancelled", async () => {
        const wrapper = render();
        const modal = useModal();

        await wrapper.get("button").trigger("click");
        const cancelAction = modal.actions?.[1];
        await cancelAction?.callback(undefined);

        expect(modal.isOpen).toBe(false);
        expect(routerPostMock).not.toHaveBeenCalled();
    });
});

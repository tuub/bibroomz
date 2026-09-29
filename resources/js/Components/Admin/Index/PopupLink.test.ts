import PopupLink from "@/Components/Admin/Index/PopupLink.vue";
import { useModal } from "@/Stores/Modal";

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
    routerVisitMock.mockClear();
    routeMock.mockClear();
});

function render(props: Record<string, unknown> = {}) {
    return mount(PopupLink, {
        props: {
            action: "delete",
            model: "user",
            params: { id: "user-1" },
            ...props,
        },
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

describe("PopupLink", () => {
    test("labels the button from the model and action when no label is given", () => {
        const wrapper = render({ action: "clone", model: "happening" });

        expect(wrapper.get("button").text()).toBe("admin.happenings.index.table.actions.clone");
    });

    test("prefers an explicit label over the translation key", () => {
        const wrapper = render({ label: "Remove from group" });

        expect(wrapper.get("button").text()).toBe("Remove from group");
    });

    test.each([
        ["delete", "ri-delete-bin-line"],
        ["clone", "ri-file-copy-line"],
        ["ban", "ri-prohibited-line"],
        ["unban", "ri-arrow-go-back-fill"],
        ["remove", "ri-close-circle-line"],
    ])("shows the %s icon", (action, icon) => {
        const wrapper = render({ action });

        expect(wrapper.get("i").classes()).toContain(icon);
    });

    test("opens the confirm modal when clicked", async () => {
        const wrapper = render();
        const modal = useModal();

        await wrapper.get("button").trigger("click");

        expect(modal.isOpen).toBe(true);
        expect(modal.actions).toHaveLength(2);
    });

    test("visits the action route with the params on confirm", async () => {
        const wrapper = render({ action: "ban", model: "user", params: { id: "user-7" } });
        const modal = useModal();

        await wrapper.get("button").trigger("click");
        await modal.actions?.[0]?.callback(undefined);

        expect(routeMock).toHaveBeenCalledWith("admin.user.ban", { id: "user-7" });
        expect(routerVisitMock).toHaveBeenCalledWith(
            "/admin.user.ban",
            expect.objectContaining({
                method: "post",
                preserveScroll: true,
                preserveState: true,
            }),
        );
    });

    test("closes the modal via onStart before the response arrives", async () => {
        const wrapper = render();
        const modal = useModal();

        await wrapper.get("button").trigger("click");
        await modal.actions?.[0]?.callback(undefined);

        const options = routerVisitMock.mock.calls[0]?.[1] as { onStart: () => void };
        options.onStart();

        expect(modal.isOpen).toBe(false);
    });

    test("closes the modal without visiting when cancelled", async () => {
        const wrapper = render();
        const modal = useModal();

        await wrapper.get("button").trigger("click");
        await modal.actions?.[1]?.callback(undefined);

        expect(modal.isOpen).toBe(false);
        expect(routerVisitMock).not.toHaveBeenCalled();
    });
});

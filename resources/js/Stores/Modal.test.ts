import { useModal } from "@/Stores/Modal";

import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent } from "vue";

const TestView = defineComponent({
    name: "TestView",
    template: "<div>modal</div>",
});

beforeEach(() => {
    setActivePinia(createPinia());
    document.body.innerHTML = "<header></header><main></main><footer></footer>";
});

describe("open", () => {
    test("stores the modal state and blurs the background elements", () => {
        const store = useModal();
        const actions = [{ label: "Save", callback: vi.fn().mockResolvedValue(undefined) }];

        store.open(TestView, { title: "Inspect" }, { id: 42 }, actions);

        expect(store.view).toBe(TestView);
        expect(store.content).toEqual({ title: "Inspect" });
        expect(store.payload).toEqual({ id: 42 });
        expect(store.actions).toEqual(actions);
        expect(store.isOpen).toBe(true);
        expect(document.querySelector("header")?.classList.contains("blur-sm")).toBe(true);
        expect(document.querySelector("main")?.classList.contains("blur-sm")).toBe(true);
        expect(document.querySelector("footer")?.classList.contains("blur-sm")).toBe(true);
    });
});

describe("close", () => {
    test("closes the modal and removes the background blur", () => {
        const store = useModal();

        store.open(TestView, { title: "Inspect" }, { id: 42 }, null);
        store.close();

        expect(store.isOpen).toBe(false);
        expect(document.querySelector("header")?.classList.contains("blur-sm")).toBe(false);
        expect(document.querySelector("main")?.classList.contains("blur-sm")).toBe(false);
        expect(document.querySelector("footer")?.classList.contains("blur-sm")).toBe(false);
    });
});

describe("cleanup", () => {
    test("clears the modal state and removes the background blur", () => {
        const store = useModal();

        store.open(TestView, { title: "Inspect" }, { id: 42 }, [{ label: "Save", callback: vi.fn() }]);
        store.cleanup();

        expect(store.view).toBeNull();
        expect(store.content).toBeNull();
        expect(store.payload).toBeNull();
        expect(store.actions).toBeNull();
        expect(document.querySelector("header")?.classList.contains("blur-sm")).toBe(false);
        expect(document.querySelector("main")?.classList.contains("blur-sm")).toBe(false);
        expect(document.querySelector("footer")?.classList.contains("blur-sm")).toBe(false);
    });
});

describe("submit", () => {
    test("calls the only action with the current payload", () => {
        const store = useModal();
        const callback = vi.fn().mockResolvedValue(undefined);

        store.open(TestView, { title: "Inspect" }, { id: 42 }, [{ label: "Save", callback }]);
        store.submit();

        expect(callback).toHaveBeenCalledWith({ id: 42 });
    });

    test("does nothing when there is no action", () => {
        const store = useModal();

        store.open(TestView, { title: "Inspect" }, { id: 42 });

        expect(() => store.submit()).not.toThrow();
    });

    test("does nothing when there are multiple actions", () => {
        const store = useModal();
        const first = vi.fn().mockResolvedValue(undefined);
        const second = vi.fn().mockResolvedValue(undefined);

        store.open(TestView, { title: "Inspect" }, { id: 42 }, [
            { label: "Save", callback: first },
            { label: "Delete", callback: second },
        ]);
        store.submit();

        expect(first).not.toHaveBeenCalled();
        expect(second).not.toHaveBeenCalled();
    });
});

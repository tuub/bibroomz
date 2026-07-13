import { useHappeningStore } from "@/Stores/HappeningStore";

import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

const axiosMock = {
    post: vi.fn(),
    delete: vi.fn(),
};

beforeEach(() => {
    setActivePinia(createPinia());
    axiosMock.post.mockReset().mockResolvedValue({ data: {} });
    axiosMock.delete.mockReset().mockResolvedValue({ data: {} });
    globalThis.axios = axiosMock as unknown as typeof axios;
});

describe("useHappeningStore", () => {
    test("addHappening posts the happening to /happening/add", () => {
        const store = useHappeningStore();
        const happening = { resource: { id: 1 }, start: "2026-01-01T10:00:00" };

        void store.addHappening(happening);

        expect(axiosMock.post).toHaveBeenCalledWith("https://rooms.example.com/happening/add", happening);
    });

    test("editHappening posts to /happening/update/:id", () => {
        const store = useHappeningStore();
        const happening = { id: 42, start: "2026-01-01T10:00:00" };

        void store.editHappening(happening);

        expect(axiosMock.post).toHaveBeenCalledWith("https://rooms.example.com/happening/update/42", happening);
    });

    test("verifyHappening posts to /happening/verify/:id", () => {
        const store = useHappeningStore();
        const happening = { id: 7 };

        void store.verifyHappening(happening);

        expect(axiosMock.post).toHaveBeenCalledWith("https://rooms.example.com/happening/verify/7", happening);
    });

    test("deleteHappening issues a delete to /happening/delete/:id", () => {
        const store = useHappeningStore();

        void store.deleteHappening(9);

        expect(axiosMock.delete).toHaveBeenCalledWith("https://rooms.example.com/happening/delete/9");
    });

    test("getters expose the draft happening from initial state", () => {
        const store = useHappeningStore();

        expect(store.getHappeningResource).toEqual({});
        expect(store.getHappeningStart).toBe("");
        expect(store.getHappeningEnd).toBe("");
    });
});

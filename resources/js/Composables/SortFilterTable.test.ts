import { useSortFilterTable } from "@/Composables/SortFilterTable";

import { beforeEach, describe, expect, test, vi } from "vitest";
import { nextTick, ref } from "vue";

const appStoreMock = vi.hoisted(() => ({ locale: "en" }));
vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

async function flush() {
    await nextTick();
    await nextTick();
}

beforeEach(() => {
    appStoreMock.locale = "en";
});

describe("sorting", () => {
    test("sorts numeric fields ascending by default", async () => {
        const data = ref([{ id: 3 }, { id: 1 }, { id: 2 }]);
        const { paginator } = useSortFilterTable({ data, initialSortField: "id" });
        await flush();

        expect(paginator.data?.map((d) => d.id)).toEqual([1, 2, 3]);
    });

    test("reverses order when sortDirection is desc", async () => {
        const data = ref([{ id: 3 }, { id: 1 }, { id: 2 }]);
        const { paginator, sortDirection } = useSortFilterTable({ data, initialSortField: "id" });
        sortDirection.value = "desc";
        await flush();

        expect(paginator.data?.map((d) => d.id)).toEqual([3, 2, 1]);
    });

    test("sorts null values after every other value", async () => {
        const data = ref([{ id: 2 }, { id: null }, { id: 1 }]);
        const { paginator } = useSortFilterTable({ data, initialSortField: "id" });
        await flush();

        expect(paginator.data?.map((d) => d.id)).toEqual([1, 2, null]);
    });

    test("sorts non-numeric fields using locale-aware string comparison", async () => {
        const data = ref([{ name: "Charlie" }, { name: "Alice" }, { name: "Bob" }]);
        const { paginator } = useSortFilterTable({
            data,
            initialSortField: "name",
            nonNumericFields: ["name"],
        });
        await flush();

        expect(paginator.data?.map((d) => d.name)).toEqual(["Alice", "Bob", "Charlie"]);
    });
});

describe("filtering", () => {
    test("keeps only rows matching every active filter", async () => {
        const data = ref([{ name: "Alice" }, { name: "Bob" }, { name: "Alicia" }]);
        const { paginator, filters } = useSortFilterTable({
            data,
            initialSortField: "name",
            nonNumericFields: ["name"],
        });
        await flush();

        filters.name = "ali";
        await flush();

        expect(paginator.data?.map((d) => d.name).sort()).toEqual(["Alice", "Alicia"]);
    });

    test("toggleFilter removes an active filter", () => {
        const data = ref([{ name: "Alice" }]);
        const { filters, toggleFilter } = useSortFilterTable({ data, nonNumericFields: ["name"] });
        filters.name = "ali";

        toggleFilter("name");

        expect(filters.name).toBeUndefined();
    });

    test("toggleFilter is a no-op for an inactive filter", () => {
        const data = ref([{ name: "Alice" }]);
        const { filters, toggleFilter } = useSortFilterTable({ data, nonNumericFields: ["name"] });

        toggleFilter("missing");

        expect(filters).toEqual({});
    });
});

describe("pagination", () => {
    test("computes lastPage/nextPage/prevPage and slices data per page", async () => {
        const data = ref(Array.from({ length: 25 }, (_, i) => ({ id: i + 1 })));
        const { paginator } = useSortFilterTable({ data, initialSortField: "id" });
        await flush();

        expect(paginator.lastPage).toBe(3);
        expect(paginator.currentPage).toBe(1);
        expect(paginator.prevPage).toBe(1);
        expect(paginator.nextPage).toBe(2);
        expect(paginator.data).toHaveLength(10);
        expect(paginator.data?.[0]?.id).toBe(1);
    });

    test("jumpToPage moves to the requested page", async () => {
        const data = ref(Array.from({ length: 25 }, (_, i) => ({ id: i + 1 })));
        const { paginator } = useSortFilterTable({ data, initialSortField: "id" });
        await flush();

        paginator.jumpToPage?.(2);
        await flush();

        expect(paginator.currentPage).toBe(2);
        expect(paginator.data?.[0]?.id).toBe(11);
        expect(paginator.prevPage).toBe(1);
        expect(paginator.nextPage).toBe(3);
    });

    test("jumpToPage falls back to page 1 for a non-positive page", async () => {
        const data = ref(Array.from({ length: 25 }, (_, i) => ({ id: i + 1 })));
        const { paginator } = useSortFilterTable({ data, initialSortField: "id" });
        paginator.jumpToPage?.(2);
        await flush();

        paginator.jumpToPage?.(0);
        await flush();

        expect(paginator.currentPage).toBe(1);
    });

    test("resets to page 1 when filtering shrinks past the current page", async () => {
        const data = ref(Array.from({ length: 25 }, (_, i) => ({ id: i + 1, group: i < 15 ? "a" : "b" })));
        const { paginator, filters } = useSortFilterTable({ data, initialSortField: "id" });
        await flush();
        paginator.jumpToPage?.(2);
        await flush();
        expect(paginator.currentPage).toBe(2);

        filters.group = "b";
        await flush();

        expect(paginator.lastPage).toBe(1);
        expect(paginator.currentPage).toBe(1);
    });
});

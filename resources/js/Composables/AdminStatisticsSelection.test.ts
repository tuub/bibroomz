import {
    readStoredSelection,
    sameId,
    useDrilldownSelection,
    writeStoredSelection,
} from "@/Composables/AdminStatisticsSelection";
import type { InstitutionStatistic, ResourceGroupStatistic, ResourceStatistic } from "@/Types/Admin";

import { beforeEach, describe, expect, test, vi } from "vitest";
import { computed, nextTick } from "vue";

const translate = (title: Record<string, string>) => title.en ?? "";

beforeEach(() => {
    window.localStorage.clear();
});

describe("sameId", () => {
    test("compares ids by string value across numbers and strings", () => {
        expect(sameId(1, "1")).toBe(true);
        expect(sameId("10", 10)).toBe(true);
        expect(sameId(1, 2)).toBe(false);
    });

    test("returns false when either id is null or undefined", () => {
        expect(sameId(null, 1)).toBe(false);
        expect(sameId(1, null)).toBe(false);
        expect(sameId(undefined, undefined)).toBe(false);
    });
});

describe("readStoredSelection", () => {
    test("returns null when nothing is stored", () => {
        expect(readStoredSelection("key", [1, 2])).toBeNull();
    });

    test("returns the matching valid id", () => {
        window.localStorage.setItem("key", "2");

        expect(readStoredSelection("key", [1, 2])).toBe(2);
    });

    test("removes and returns null for a stored id that is no longer valid", () => {
        window.localStorage.setItem("key", "99");

        expect(readStoredSelection("key", [1, 2])).toBeNull();
        expect(window.localStorage.getItem("key")).toBeNull();
    });

    test("returns null when localStorage access throws", () => {
        const getItem = vi.spyOn(Storage.prototype, "getItem").mockImplementation(() => {
            throw new Error("blocked");
        });

        expect(readStoredSelection("key", [1, 2])).toBeNull();

        getItem.mockRestore();
    });
});

describe("writeStoredSelection", () => {
    test("stores the value as a string", () => {
        writeStoredSelection("key", 2);

        expect(window.localStorage.getItem("key")).toBe("2");
    });

    test("removes the key when the value is null", () => {
        window.localStorage.setItem("key", "2");

        writeStoredSelection("key", null);

        expect(window.localStorage.getItem("key")).toBeNull();
    });

    test("silently ignores a localStorage write failure", () => {
        const setItem = vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
            throw new Error("blocked");
        });

        expect(() => writeStoredSelection("key", 2)).not.toThrow();

        setItem.mockRestore();
    });
});

function buildInstitution(id: number | string, overrides: Partial<InstitutionStatistic> = {}): InstitutionStatistic {
    return {
        id,
        title: { en: `Institution ${id}` },
        count: 0,
        active: 0,
        cancelled: 0,
        cancellationRate: 0,
        ...overrides,
    };
}

function buildResourceGroup(
    id: number | string,
    institutionId: number | string,
    overrides: Partial<ResourceGroupStatistic> = {},
): ResourceGroupStatistic {
    return {
        id,
        institution_id: institutionId,
        title: { en: `Group ${id}` },
        count: 0,
        active: 0,
        cancelled: 0,
        cancellationRate: 0,
        ...overrides,
    };
}

function buildResource(
    id: number | string,
    resourceGroupId: number | string,
    overrides: Partial<ResourceStatistic> = {},
): ResourceStatistic {
    return {
        id,
        resource_group_id: resourceGroupId,
        title: { en: `Resource ${id}` },
        count: 0,
        active: 0,
        cancelled: 0,
        cancellationRate: 0,
        ...overrides,
    };
}

describe("useDrilldownSelection", () => {
    test("defaults to the first institution and its first resource group", () => {
        const selection = useDrilldownSelection(
            computed(() => [buildInstitution(1), buildInstitution(2)]),
            computed(() => [buildResourceGroup(10, 1), buildResourceGroup(20, 2)]),
            computed(() => []),
            translate,
        );

        expect(selection.selectedInstitutionId.value).toBe(1);
        expect(selection.selectedResourceGroupId.value).toBe(10);
        expect(selection.institutionOptions.value).toEqual([
            { id: 1, label: "Institution 1" },
            { id: 2, label: "Institution 2" },
        ]);
    });

    test("restores a previously persisted selection", () => {
        window.localStorage.setItem("roomz.admin.statistics.selectedInstitutionId", "2");
        window.localStorage.setItem("roomz.admin.statistics.selectedResourceGroupId", "20");

        const selection = useDrilldownSelection(
            computed(() => [buildInstitution(1), buildInstitution(2)]),
            computed(() => [buildResourceGroup(10, 1), buildResourceGroup(20, 2)]),
            computed(() => []),
            translate,
        );

        expect(selection.selectedInstitutionId.value).toBe(2);
        expect(selection.selectedResourceGroupId.value).toBe(20);
    });

    test("resets the resource group selection when it no longer belongs to the institution", async () => {
        const selection = useDrilldownSelection(
            computed(() => [buildInstitution(1), buildInstitution(2)]),
            computed(() => [buildResourceGroup(10, 1), buildResourceGroup(20, 2)]),
            computed(() => []),
            translate,
        );

        selection.selectedInstitutionId.value = 2;
        await nextTick();

        expect(selection.selectedResourceGroupId.value).toBe(20);
    });

    test("filters resources down to the selected resource group", () => {
        const selection = useDrilldownSelection(
            computed(() => [buildInstitution(1)]),
            computed(() => [buildResourceGroup(10, 1)]),
            computed(() => [buildResource(100, 10), buildResource(200, 99)]),
            translate,
        );

        expect(selection.resourcesForResourceGroup.value).toEqual([buildResource(100, 10)]);
    });

    test("persists selection changes to localStorage", async () => {
        const selection = useDrilldownSelection(
            computed(() => [buildInstitution(1), buildInstitution(2)]),
            computed(() => [buildResourceGroup(10, 1), buildResourceGroup(20, 2)]),
            computed(() => []),
            translate,
        );

        selection.selectedInstitutionId.value = 2;
        await nextTick();

        expect(window.localStorage.getItem("roomz.admin.statistics.selectedInstitutionId")).toBe("2");
        expect(window.localStorage.getItem("roomz.admin.statistics.selectedResourceGroupId")).toBe("20");
    });
});

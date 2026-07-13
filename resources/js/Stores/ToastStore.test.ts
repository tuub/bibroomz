import { useToastStore } from "@/Stores/ToastStore";

import type { ConfigType } from "dayjs";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

const appStoreMock = vi.hoisted(() => ({
    getDateTimeFromString: vi.fn((value?: string) => value),
    formatDate: vi.fn(() => "05.03.2026"),
    formatTime: vi.fn((value?: ConfigType) => (value === "2026-03-05T12:00:00" ? "13:00" : "12:00")),
}));
vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

const useToastMock = vi.hoisted(() => vi.fn(() => ({ add: vi.fn() })));
vi.mock("primevue/usetoast", () => ({
    useToast: useToastMock,
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
});

describe("initToast", () => {
    test("stores the primevue toast service instance", () => {
        const toastService = { add: vi.fn() };
        useToastMock.mockReturnValue(toastService);
        const store = useToastStore();

        store.initToast();

        expect(store.toast).toEqual(toastService);
    });
});

describe("addToast", () => {
    test("pushes a new message and forwards it to the toast service", () => {
        const toastService = { add: vi.fn() };
        useToastMock.mockReturnValue(toastService);
        const store = useToastStore();
        store.initToast();

        store.addToast({ id: "auth", summary: "Welcome" });

        expect(store.toastMessages).toEqual([{ id: "auth", summary: "Welcome" }]);
        expect(toastService.add).toHaveBeenCalledWith({ id: "auth", summary: "Welcome" });
    });

    test("ignores an exact duplicate of an already queued message", () => {
        const store = useToastStore();

        store.addToast({ id: "auth", summary: "Welcome" });
        store.addToast({ id: "auth", summary: "Welcome" });

        expect(store.toastMessages).toHaveLength(1);
    });

    test("does not treat messages with different content as duplicates", () => {
        const store = useToastStore();

        store.addToast({ id: "auth", summary: "Welcome" });
        store.addToast({ id: "auth", summary: "Goodbye" });

        expect(store.toastMessages).toHaveLength(2);
    });

    test("works without a toast service having been initialized", () => {
        const store = useToastStore();

        expect(() => store.addToast({ id: "auth", summary: "Welcome" })).not.toThrow();
    });
});

describe("addAuthToast", () => {
    test("defaults to a success severity", () => {
        const store = useToastStore();

        store.addAuthToast({ summary: "Logged in" });

        expect(store.toastMessages).toEqual([{ id: "auth", life: 3000, severity: "success", summary: "Logged in" }]);
    });

    test("accepts an explicit severity", () => {
        const store = useToastStore();

        store.addAuthToast({ summary: "Logged out", severity: "error" });

        expect(store.toastMessages[0]).toMatchObject({ severity: "error" });
    });
});

describe("addHappeningToast", () => {
    test("formats the happening's start/end into the toast detail", () => {
        const store = useToastStore();
        const happening = { start: "2026-03-05T12:00:00", end: "2026-03-05T14:00:00" };

        store.addHappeningToast({ happening, summary: "Booking created" });

        expect(appStoreMock.getDateTimeFromString).toHaveBeenCalledWith(happening.start);
        expect(appStoreMock.getDateTimeFromString).toHaveBeenCalledWith(happening.end);
        expect(store.toastMessages).toEqual([
            {
                detail: "05.03.2026, 13:00 - 12:00",
                id: "happening",
                life: 5000,
                severity: "success",
                summary: "Booking created",
            },
        ]);
    });
});

describe("addQuotaToast", () => {
    test("defaults to a warn severity", () => {
        const store = useToastStore();

        store.addQuotaToast({ summary: "Quota exceeded" });

        expect(store.toastMessages).toEqual([{ id: "quota", life: 5000, severity: "warn", summary: "Quota exceeded" }]);
    });
});

describe("addUserGroupToast", () => {
    test("defaults to a warn severity", () => {
        const store = useToastStore();

        store.addUserGroupToast({ summary: "Wrong group" });

        expect(store.toastMessages).toEqual([
            { id: "userGroup", life: 3000, severity: "warn", summary: "Wrong group" },
        ]);
    });
});

describe("removeToastMessage", () => {
    test("removes the matching message from the queue", () => {
        const store = useToastStore();
        store.addToast({ id: "auth", summary: "Welcome" });
        store.addToast({ id: "quota", summary: "Quota exceeded" });

        store.removeToastMessage({ message: { id: "auth", summary: "Welcome" } });

        expect(store.toastMessages).toEqual([{ id: "quota", summary: "Quota exceeded" }]);
    });

    test("is a no-op when the message is not queued", () => {
        const store = useToastStore();
        store.addToast({ id: "auth", summary: "Welcome" });

        store.removeToastMessage({ message: { id: "quota", summary: "Not queued" } });

        expect(store.toastMessages).toHaveLength(1);
    });
});

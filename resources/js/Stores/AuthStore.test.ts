import type { Institution, ResourceGroup, Settings } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string, params?: Record<string, string>) => (params ? `${key}:${JSON.stringify(params)}` : key),
}));

const routerMock = vi.hoisted(() => ({ visit: vi.fn() }));
vi.mock("@inertiajs/vue3", () => ({
    router: routerMock,
}));

type AppStoreMock = {
    resourceGroup: ResourceGroup | null;
    institution: Institution | null;
    settings: Settings | null;
    translate: (value?: Record<string, string>) => string | undefined;
    getDateTimeFromString: ReturnType<typeof vi.fn>;
    formatDate: ReturnType<typeof vi.fn>;
    formatTime: ReturnType<typeof vi.fn>;
};

const appStoreMock = vi.hoisted(
    (): AppStoreMock => ({
        resourceGroup: null,
        institution: null,
        settings: null,
        translate: vi.fn((value?: Record<string, string>) => value?.en),
        getDateTimeFromString: vi.fn((value?: string) => value),
        formatDate: vi.fn(() => "05.03.2026"),
        formatTime: vi.fn(() => "14:30"),
    }),
);
vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

const toastStoreMock = vi.hoisted(() => ({
    addAuthToast: vi.fn(),
    addHappeningToast: vi.fn(),
    addQuotaToast: vi.fn(),
    addUserGroupToast: vi.fn(),
}));
vi.mock("@/Stores/ToastStore", () => ({
    useToastStore: () => toastStoreMock,
}));

const axiosMock = { get: vi.fn(), post: vi.fn() };
const echoMock = { private: vi.fn(), leave: vi.fn() };

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    appStoreMock.resourceGroup = null;
    appStoreMock.institution = null;
    appStoreMock.settings = null;

    axiosMock.get.mockResolvedValue({ data: {} });
    axiosMock.post.mockResolvedValue({ data: {} });
    globalThis.axios = axiosMock as unknown as typeof axios;

    echoMock.private.mockReturnValue({ listen: vi.fn().mockReturnThis() });
    globalThis.Echo = echoMock as unknown as typeof Echo;
});

describe("csrf", () => {
    test("fetches the sanctum csrf cookie", async () => {
        const store = useAuthStore();

        await store.csrf();

        expect(axiosMock.get).toHaveBeenCalledWith("https://rooms.example.com/sanctum/csrf-cookie");
    });
});

describe("check", () => {
    test("populates auth state and subscribes on success", async () => {
        axiosMock.post.mockResolvedValue({
            data: {
                user: { id: 1, name: "Alice" },
                isAdmin: true,
                permissions: { 1: ["view"] },
                allowedResourceGroups: [1],
            },
        });
        const store = useAuthStore();

        await store.check();

        expect(store.user).toEqual({ id: 1, name: "Alice" });
        expect(store.isAuthenticated).toBe(true);
        expect(store.isAdmin).toBe(true);
        expect(store.permissions).toEqual({ 1: ["view"] });
        expect(store.allowedResourceGroups).toEqual([1]);
        expect(echoMock.private).toHaveBeenCalledWith("happenings.1");
    });

    test("resets state when the check request fails", async () => {
        axiosMock.post.mockRejectedValue(new Error("unauthorized"));
        const store = useAuthStore();
        store.isAuthenticated = true;

        await store.check();

        expect(store.isAuthenticated).toBe(false);
        expect(store.user).toBeNull();
    });
});

describe("login", () => {
    test("authenticates the user and shows a success toast", async () => {
        axiosMock.post.mockResolvedValue({
            data: { user: { id: 2 }, isAdmin: false, permissions: {}, allowedResourceGroups: [] },
        });
        const store = useAuthStore();

        await store.login("alice", "secret");

        expect(axiosMock.post).toHaveBeenCalledWith("https://rooms.example.com/login", {
            username: "alice",
            password: "secret",
        });
        expect(store.isAuthenticated).toBe(true);
        expect(toastStoreMock.addAuthToast).toHaveBeenCalledWith({ summary: "toast.login.success" });
    });
});

describe("logout", () => {
    test("logs out, unsubscribes and redirects on success", async () => {
        const store = useAuthStore();
        store.isAuthenticated = true;
        store.user = { id: 3 };

        await store.logout();

        expect(axiosMock.post).toHaveBeenCalledWith("https://rooms.example.com/logout");
        expect(routerMock.visit).toHaveBeenCalledWith("/");
        expect(store.isAuthenticated).toBe(false);
        expect(toastStoreMock.addAuthToast).toHaveBeenCalledWith({ summary: "toast.logout.success" });
    });

    test("shows an error toast when logout fails", async () => {
        axiosMock.post.mockRejectedValue(new Error("network error"));
        const store = useAuthStore();

        await store.logout();

        expect(toastStoreMock.addAuthToast).toHaveBeenCalledWith({
            severity: "error",
            summary: "toast.logout.error",
        });
    });
});

describe("fetchUserHappenings", () => {
    test("does nothing when not authenticated", async () => {
        const store = useAuthStore();

        await store.fetchUserHappenings();

        expect(axiosMock.get).not.toHaveBeenCalled();
    });

    test("does nothing without a current resource group", async () => {
        const store = useAuthStore();
        store.isAuthenticated = true;

        await store.fetchUserHappenings();

        expect(axiosMock.get).not.toHaveBeenCalled();
    });

    test("fetches and stores happenings for the current resource group", async () => {
        appStoreMock.resourceGroup = { id: 5 };
        axiosMock.get.mockResolvedValue({ data: [{ id: 1 }] });
        const store = useAuthStore();
        store.isAuthenticated = true;

        await store.fetchUserHappenings();

        expect(axiosMock.get).toHaveBeenCalledWith("https://rooms.example.com/my/happenings", {
            params: { resource_group_id: 5 },
        });
        expect(store.userHappenings).toEqual([{ id: 1 }]);
    });
});

describe("user happening list management", () => {
    test("addUserHappening appends and sorts by start", () => {
        const store = useAuthStore();
        store.userHappenings = [{ id: 1, start: "2026-03-05T12:00:00" }];

        store.addUserHappening({ id: 2, start: "2026-03-05T08:00:00" });

        expect(store.userHappenings.map((h) => h.id)).toEqual([2, 1]);
    });

    test("addUserHappening ignores duplicates by id", () => {
        const store = useAuthStore();
        store.userHappenings = [{ id: 1, start: "2026-03-05T12:00:00" }];

        store.addUserHappening({ id: 1, start: "2026-03-05T09:00:00" });

        expect(store.userHappenings).toHaveLength(1);
    });

    test("updateUserHappening replaces the matching entry", () => {
        const store = useAuthStore();
        store.userHappenings = [{ id: 1, start: "2026-03-05T12:00:00", summary: "old" }];

        store.updateUserHappening({ id: 1, start: "2026-03-05T12:00:00", summary: "new" });

        expect(store.userHappenings[0].summary).toBe("new");
    });

    test("removeUserHappening removes the matching entry", () => {
        const store = useAuthStore();
        store.userHappenings = [{ id: 1, start: "2026-03-05T12:00:00" }];

        store.removeUserHappening({ id: 1 });

        expect(store.userHappenings).toHaveLength(0);
    });

    test("removeUserHappening is a no-op when the happening is not found", () => {
        const store = useAuthStore();
        store.userHappenings = [{ id: 1, start: "2026-03-05T12:00:00" }];

        store.removeUserHappening({ id: 99 });

        expect(store.userHappenings).toHaveLength(1);
    });
});

describe("updateUserHappenings", () => {
    test("skips happenings from another resource group", () => {
        appStoreMock.resourceGroup = { id: 1 };
        const store = useAuthStore();
        const callback = vi.fn();

        store.updateUserHappenings({
            happening: { resource: { resourceGroupId: 2 } },
            callback,
        });

        expect(callback).not.toHaveBeenCalled();
        expect(toastStoreMock.addHappeningToast).not.toHaveBeenCalled();
    });

    test("invokes the callback and shows a toast for the current resource group", () => {
        appStoreMock.resourceGroup = { id: 1 };
        const store = useAuthStore();
        const callback = vi.fn();
        const happening = { resource: { resourceGroupId: 1 } };

        store.updateUserHappenings({ happening, callback, summary: "updated" });

        expect(callback).toHaveBeenCalledWith(happening);
        expect(toastStoreMock.addHappeningToast).toHaveBeenCalledWith({ happening, summary: "updated" });
    });
});

describe("subscribe/unsubscribe", () => {
    test("subscribe does nothing when not authenticated", () => {
        const store = useAuthStore();

        store.subscribe();

        expect(echoMock.private).not.toHaveBeenCalled();
    });

    test("subscribe listens on the user's private channel", () => {
        const store = useAuthStore();
        store.isAuthenticated = true;
        store.user = { id: 42 };

        store.subscribe();

        expect(echoMock.private).toHaveBeenCalledWith("happenings.42");
    });

    test("unsubscribe leaves the user's private channel", () => {
        const store = useAuthStore();
        store.isAuthenticated = true;
        store.user = { id: 42 };

        store.unsubscribe();

        expect(echoMock.leave).toHaveBeenCalledWith("happenings.42");
    });

    test("unsubscribe does nothing when not authenticated", () => {
        const store = useAuthStore();

        store.unsubscribe();

        expect(echoMock.leave).not.toHaveBeenCalled();
    });
});

describe("updateQuotas", () => {
    function happeningsForCurrentWeek(store: ReturnType<typeof useAuthStore>) {
        store.user = { id: 1, name: "Alice" };
        store.userHappenings = [
            { user_01: "Alice", start: "2026-03-05T10:00:00", end: "2026-03-05T12:00:00" },
            { user_01: "Alice", start: "2026-03-04T10:00:00", end: "2026-03-04T11:00:00" },
            { user_01: "Bob", start: "2026-03-05T10:00:00", end: "2026-03-05T11:00:00" },
        ];
    }

    test("computes daily/weekly hours and counts from a string date", () => {
        const store = useAuthStore();
        happeningsForCurrentWeek(store);

        store.updateQuotas("2026-03-05T09:00:00");

        expect(store.quotas.daily_hours).toBe(2);
        expect(store.quotas.weekly_hours).toBe(3);
        expect(store.quotas.weekly_happenings).toBe(2);
    });

    test("accepts a native Date instance", () => {
        const store = useAuthStore();
        happeningsForCurrentWeek(store);

        store.updateQuotas(new Date("2026-03-05T09:00:00"));

        expect(store.quotas.daily_hours).toBe(2);
    });

    test("accepts a Dayjs instance directly", async () => {
        const dayjs = (await import("dayjs")).default;
        const store = useAuthStore();
        happeningsForCurrentWeek(store);

        store.updateQuotas(dayjs("2026-03-05T09:00:00"));

        expect(store.quotas.daily_hours).toBe(2);
    });
});

describe("isOverlappingUserHappening", () => {
    test("detects an overlap with an existing happening", async () => {
        const dayjs = (await import("dayjs")).default;
        const store = useAuthStore();
        store.user = { id: 1, name: "Alice" };
        store.userHappenings = [{ user_01: "Alice", start: "2026-03-05T10:00:00", end: "2026-03-05T12:00:00" }];

        const overlaps = store.isOverlappingUserHappening(dayjs("2026-03-05T11:00:00"), dayjs("2026-03-05T13:00:00"));

        expect(overlaps).toBe(true);
    });

    test("returns false without any overlap", async () => {
        const dayjs = (await import("dayjs")).default;
        const store = useAuthStore();
        store.user = { id: 1, name: "Alice" };
        store.userHappenings = [{ user_01: "Alice", start: "2026-03-05T10:00:00", end: "2026-03-05T12:00:00" }];

        const overlaps = store.isOverlappingUserHappening(dayjs("2026-03-05T13:00:00"), dayjs("2026-03-05T14:00:00"));

        expect(overlaps).toBe(false);
    });
});

describe("isExceedingQuotas", () => {
    async function dayjsRange(startIso: string, endIso: string) {
        const dayjs = (await import("dayjs")).default;
        return { start: dayjs(startIso), end: dayjs(endIso) };
    }

    test("bypasses all checks when the user has unlimited quotas", async () => {
        const store = useAuthStore();
        store.isAdmin = true;
        const { start, end } = await dayjsRange("2026-03-05T10:00:00", "2026-03-05T12:00:00");

        expect(store.isExceedingQuotas(start, end)).toBe(false);
    });

    test("flags an overlapping happening", async () => {
        appStoreMock.settings = { resource_group: {} };
        const store = useAuthStore();
        store.user = { id: 1, name: "Alice" };
        store.userHappenings = [{ user_01: "Alice", start: "2026-03-05T10:00:00", end: "2026-03-05T12:00:00" }];
        const { start, end } = await dayjsRange("2026-03-05T11:00:00", "2026-03-05T13:00:00");

        expect(store.isExceedingQuotas(start, end)).toBe(true);
        expect(toastStoreMock.addQuotaToast).toHaveBeenCalledWith({ summary: "toast.concurrent_happening" });
    });

    test("flags a happening block exceeding the configured limit", async () => {
        appStoreMock.settings = { resource_group: { quota_happening_block_hours: 1 } };
        const store = useAuthStore();
        const { start, end } = await dayjsRange("2026-03-05T10:00:00", "2026-03-05T12:00:00");

        expect(store.isExceedingQuotas(start, end)).toBe(true);
        expect(toastStoreMock.addQuotaToast).toHaveBeenCalled();
    });

    test("returns false when under every quota", async () => {
        appStoreMock.settings = {
            resource_group: {
                quota_happening_block_hours: 5,
                quota_weekly_happenings: 5,
                quota_weekly_hours: 20,
                quota_daily_hours: 10,
            },
        };
        const store = useAuthStore();
        const { start, end } = await dayjsRange("2026-03-05T10:00:00", "2026-03-05T11:00:00");

        expect(store.isExceedingQuotas(start, end)).toBe(false);
        expect(toastStoreMock.addQuotaToast).not.toHaveBeenCalled();
    });
});

describe("permissions", () => {
    test("can() checks permission against the current institution", () => {
        appStoreMock.institution = { id: 7 };
        const store = useAuthStore();
        store.permissions = { 7: ["edit_resource"] };

        expect(store.can("edit_resource")).toBe(true);
        expect(store.can("delete_resource")).toBe(false);
    });

    test("hasPermission grants everything to admins", () => {
        const store = useAuthStore();
        store.isAdmin = true;

        expect(store.hasPermission("anything")).toBe(true);
    });

    test("hasPermission checks the global permission list without an institution", () => {
        const store = useAuthStore();
        store.permissions = { 3: ["view_institution"] };

        expect(store.hasPermission("view_institution")).toBe(true);
        expect(store.hasPermission("missing")).toBe(false);
    });

    test("canViewInstitutions is true for either the plural or singular permission", () => {
        const store = useAuthStore();
        store.permissions = { 1: ["view_institution"] };

        expect(store.canViewInstitutions()).toBe(true);
    });

    test("canViewInstitutions is false without either permission", () => {
        const store = useAuthStore();
        store.permissions = {};

        expect(store.canViewInstitutions()).toBe(false);
    });
});

describe("isAllowedForResource", () => {
    test("returns true and skips the toast for an allowed resource group", () => {
        const store = useAuthStore();
        store.allowedResourceGroups = [1];

        expect(store.isAllowedForResource({ resourceGroup: 1 })).toBe(true);
        expect(toastStoreMock.addUserGroupToast).not.toHaveBeenCalled();
    });

    test("returns false and shows a toast for a disallowed resource group", () => {
        const store = useAuthStore();
        store.allowedResourceGroups = [1];

        expect(store.isAllowedForResource({ resourceGroup: 2 })).toBe(false);
        expect(toastStoreMock.addUserGroupToast).toHaveBeenCalled();
    });
});

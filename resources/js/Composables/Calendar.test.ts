import { useCalendar } from "@/Composables/Calendar";
import type { CalendarPagination } from "@/Composables/Calendar";
import { useAuthStore } from "@/Stores/AuthStore";
import type { Happening } from "@/Stores/HappeningStore";

import dayjs from "dayjs";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@fullcalendar/interaction", () => ({ default: { id: "interactionPlugin" } }));
vi.mock("@fullcalendar/resource-timegrid", () => ({ default: { id: "resourceTimeGridPlugin" } }));

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
}));

vi.mock("@inertiajs/vue3", () => ({
    router: { visit: vi.fn() },
}));

vi.mock("@/Stores/ToastStore", () => ({
    useToastStore: () => ({
        addAuthToast: vi.fn(),
        addHappeningToast: vi.fn(),
        addQuotaToast: vi.fn(),
        addUserGroupToast: vi.fn(),
    }),
}));

const appStoreMock = vi.hoisted(() => {
    const settings: {
        resource_group?: {
            weeks_in_advance: string;
            time_slot_length: string;
            start_time_slot: string;
            end_time_slot: string;
        };
    } = {
        resource_group: {
            weeks_in_advance: "4",
            time_slot_length: "01:00",
            start_time_slot: "08:00:00",
            end_time_slot: "20:00:00",
        },
    };

    return {
        institution: { id: 1, slug: "tu-berlin" },
        resourceGroup: { id: 2, slug: "library" },
        settings,
        hiddenDays: [0, 6] as number[] | null,
        locale: "en",
        translate: vi.fn((value?: Record<string, string>) => value?.en ?? ""),
    };
});
vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

const modalActionsMock = vi.hoisted(() => ({
    useHappeningCreateModal: vi.fn((happening: Happening) => ({ kind: "create", happening })),
    useHappeningEditModal: vi.fn((happening: Happening) => ({ kind: "edit", happening })),
    useHappeningInfoModal: vi.fn((happening: Happening) => ({ kind: "info", happening })),
    useHappeningVerifyModal: vi.fn((happening: Happening) => ({ kind: "verify", happening })),
    useLoginModal: vi.fn((callback?: () => void) => ({ kind: "login", callback })),
    useResourceInfoModal: vi.fn((resource: { title?: string }) => ({ kind: "resource-info", resource })),
}));
vi.mock("@/Composables/ModalActions", () => modalActionsMock);

const axiosMock = vi.fn();

function makeCalendar(overrides: Partial<Parameters<typeof useCalendar>[0]> = {}) {
    const emit = vi.fn();
    const pagination: { currentPage?: string | null; nextPage?: string | null; previousPage?: string | null } = {
        currentPage: "https://rooms.example.com/resources?page=1",
    };
    const translate = (value?: Record<string, string>) => value?.en ?? "";

    const calendar = useCalendar({ emit, pagination, translate, ...overrides });

    return { ...calendar, emit, pagination };
}

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    appStoreMock.institution = { id: 1, slug: "tu-berlin" };
    appStoreMock.resourceGroup = { id: 2, slug: "library" };
    appStoreMock.settings = {
        resource_group: {
            weeks_in_advance: "4",
            time_slot_length: "01:00",
            start_time_slot: "08:00:00",
            end_time_slot: "20:00:00",
        },
    };
    appStoreMock.hiddenDays = [0, 6];

    axiosMock.mockResolvedValue({ data: {} });
    globalThis.axios = axiosMock as unknown as typeof axios;
});

describe("resources (fetchResources)", () => {
    test("does nothing without a current page", () => {
        const emit = vi.fn();
        const pagination: CalendarPagination = { currentPage: null };
        const { calendarOptions } = useCalendar({ emit, pagination, translate: (v) => v?.en ?? "" });
        const successCallback = vi.fn();
        const failureCallback = vi.fn();

        calendarOptions.resources({}, successCallback, failureCallback);

        expect(axiosMock).not.toHaveBeenCalled();
        expect(successCallback).not.toHaveBeenCalled();
        expect(failureCallback).not.toHaveBeenCalled();
    });

    test("fetches resources and updates pagination on success", async () => {
        axiosMock.mockResolvedValue({
            data: {
                pagination: { previousPage: null, nextPage: "https://rooms.example.com/resources?page=2" },
                resources: [{ id: "r1" }],
            },
        });
        const { calendarOptions, pagination } = makeCalendar();
        const successCallback = vi.fn();
        const failureCallback = vi.fn();

        calendarOptions.resources({}, successCallback, failureCallback);
        await vi.waitFor(() => expect(successCallback).toHaveBeenCalled());

        expect(axiosMock).toHaveBeenCalledWith({ method: "GET", url: pagination.currentPage });
        expect(pagination.nextPage).toBe("https://rooms.example.com/resources?page=2");
        expect(successCallback).toHaveBeenCalledWith([{ id: "r1" }]);
        expect(failureCallback).not.toHaveBeenCalled();
    });

    test("calls the failure callback when the request rejects", async () => {
        const error = new Error("network error");
        axiosMock.mockRejectedValue(error);
        const { calendarOptions } = makeCalendar();
        const successCallback = vi.fn();
        const failureCallback = vi.fn();

        calendarOptions.resources({}, successCallback, failureCallback);
        await vi.waitFor(() => expect(failureCallback).toHaveBeenCalledWith(error));

        expect(successCallback).not.toHaveBeenCalled();
    });
});

describe("events (fetchHappenings)", () => {
    test("fetches happenings scoped to the institution/resource group", async () => {
        axiosMock.mockResolvedValue({ data: [{ id: "h1" }] });
        const { calendarOptions } = makeCalendar();
        const successCallback = vi.fn();
        const failureCallback = vi.fn();
        const start = new Date("2026-03-01T00:00:00Z");
        const end = new Date("2026-03-02T00:00:00Z");

        calendarOptions.events({ start, end }, successCallback, failureCallback);
        await vi.waitFor(() => expect(successCallback).toHaveBeenCalled());

        expect(axiosMock).toHaveBeenCalledWith({
            method: "GET",
            url: "https://rooms.example.com/tu-berlin/library/happenings",
            params: { start, end },
        });
        expect(successCallback).toHaveBeenCalledWith([{ id: "h1" }]);
    });

    test("calls the failure callback when the request rejects", async () => {
        const error = new Error("network error");
        axiosMock.mockRejectedValue(error);
        const { calendarOptions } = makeCalendar();
        const failureCallback = vi.fn();

        calendarOptions.events({ start: new Date(), end: new Date() }, vi.fn(), failureCallback);
        await vi.waitFor(() => expect(failureCallback).toHaveBeenCalledWith(error));
    });
});

describe("validRange", () => {
    test("spans the configured number of weeks in advance", () => {
        const { calendarOptions } = makeCalendar();

        const diffDays = dayjs(calendarOptions.validRange.end).diff(calendarOptions.validRange.start, "day");

        expect(diffDays).toBe(28);
    });
});

describe("isSelectAllow", () => {
    function selectInfo(startIso: string, endIso: string, resourceGroup: string | number = 2) {
        return {
            resource: { extendedProps: { resourceGroup } },
            startStr: startIso,
            endStr: endIso,
        };
    }

    test("rejects a selection without a resource", () => {
        const { calendarOptions } = makeCalendar();
        const now = dayjs.utc();
        const start = now.add(1, "hour");
        const end = start.add(1, "hour");

        const allowed = calendarOptions.selectAllow({
            startStr: start.toISOString(),
            endStr: end.toISOString(),
        });

        expect(allowed).toBe(false);
    });

    test("allows a future slot within the configured time slot length", () => {
        const { calendarOptions } = makeCalendar();
        const now = dayjs.utc();
        const start = now.add(1, "hour");
        const end = start.add(1, "hour");

        const allowed = calendarOptions.selectAllow(selectInfo(start.toISOString(), end.toISOString()));

        expect(allowed).toBe(true);
    });

    test("allows a slot that is currently in progress", () => {
        const { calendarOptions } = makeCalendar();
        const now = dayjs.utc();
        const start = now.subtract(30, "minutes");
        const end = now.add(30, "minutes");

        const allowed = calendarOptions.selectAllow(selectInfo(start.toISOString(), end.toISOString()));

        expect(allowed).toBe(true);
    });

    test("rejects a slot fully in the past", () => {
        const { calendarOptions } = makeCalendar();
        const now = dayjs.utc();
        const start = now.subtract(3, "hours");
        const end = now.subtract(2, "hours");

        const allowed = calendarOptions.selectAllow(selectInfo(start.toISOString(), end.toISOString()));

        expect(allowed).toBe(false);
    });

    test("rejects when the authenticated user is not allowed for the resource group", () => {
        const authStore = useAuthStore();
        authStore.isAuthenticated = true;
        authStore.allowedResourceGroups = [999];
        const { calendarOptions } = makeCalendar();
        const now = dayjs.utc();
        const start = now.add(1, "hour");
        const end = start.add(1, "hour");

        const allowed = calendarOptions.selectAllow(selectInfo(start.toISOString(), end.toISOString(), 2));

        expect(allowed).toBe(false);
    });

    test("rejects when the select would exceed quotas due to an overlapping happening", () => {
        const authStore = useAuthStore();
        authStore.isAuthenticated = true;
        authStore.allowedResourceGroups = [2];
        authStore.user = { id: 1, name: "Alice" };
        const now = dayjs.utc();
        const start = now.add(1, "hour");
        const end = start.add(1, "hour");
        authStore.userHappenings = [{ user_01: "Alice", start: start.toISOString(), end: end.toISOString() }];

        const { calendarOptions } = makeCalendar();

        const allowed = calendarOptions.selectAllow(selectInfo(start.toISOString(), end.toISOString(), 2));

        expect(allowed).toBe(false);
    });
});

describe("select (onSelect)", () => {
    test("ignores selections without a resource", () => {
        const { calendarOptions, emit } = makeCalendar();

        calendarOptions.select({
            startStr: "2026-03-05T10:00:00Z",
            endStr: "2026-03-05T11:00:00Z",
        });

        expect(modalActionsMock.useLoginModal).not.toHaveBeenCalled();
        expect(modalActionsMock.useHappeningCreateModal).not.toHaveBeenCalled();
        expect(emit).not.toHaveBeenCalled();
    });

    test("opens the login modal when the user is not authenticated", () => {
        const { calendarOptions, emit } = makeCalendar();
        const eventInfo = {
            resource: { id: "r1", extendedProps: {} },
            startStr: "2026-03-05T10:00:00Z",
            endStr: "2026-03-05T11:00:00Z",
        };

        calendarOptions.select(eventInfo);

        expect(modalActionsMock.useLoginModal).toHaveBeenCalled();
        expect(emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "login" }));
        expect(modalActionsMock.useHappeningCreateModal).not.toHaveBeenCalled();
    });

    test("opens the happening create modal when authenticated", () => {
        const authStore = useAuthStore();
        authStore.isAuthenticated = true;
        const { calendarOptions, emit } = makeCalendar();
        const eventInfo = {
            resource: {
                id: "r1",
                extendedProps: {
                    translations: { title: { en: "Study Room" } },
                    capacity: 4,
                },
            },
            startStr: "2026-03-05T10:00:00Z",
            endStr: "2026-03-05T11:00:00Z",
        };

        calendarOptions.select(eventInfo);

        expect(modalActionsMock.useHappeningCreateModal).toHaveBeenCalledWith(
            expect.objectContaining({
                isSelected: true,
                start: "2026-03-05T10:00:00Z",
                end: "2026-03-05T11:00:00Z",
                resource: expect.objectContaining({ id: "r1", title: "Study Room", capacity: 4 }),
            }),
        );
        expect(emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "create" }));
    });
});

describe("eventClick (onEventClick)", () => {
    function clickInfo({
        isBgEvent = false,
        can,
    }: {
        isBgEvent?: boolean;
        can?: { verify?: boolean; edit?: boolean; delete?: boolean };
    }) {
        return {
            el: {
                classList: { contains: (cls: string) => isBgEvent && cls === "fc-bg-event" },
            } as unknown as HTMLElement,
            event: {
                id: "e1",
                extendedProps: { can, user_01: "Alice" },
                getResources: () => [{ _resource: { id: "r1", extendedProps: {} } }],
                _instance: {
                    range: { start: new Date("2026-03-05T10:00:00Z"), end: new Date("2026-03-05T11:00:00Z") },
                },
            },
        };
    }

    test("does nothing for background events", () => {
        const { calendarOptions, emit } = makeCalendar();

        calendarOptions.eventClick(clickInfo({ isBgEvent: true }));

        expect(emit).not.toHaveBeenCalled();
    });

    test("opens the verify modal when the user can verify", () => {
        const { calendarOptions, emit } = makeCalendar();

        calendarOptions.eventClick(clickInfo({ can: { verify: true } }));

        expect(modalActionsMock.useHappeningVerifyModal).toHaveBeenCalled();
        expect(emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "verify" }));
    });

    test("opens the edit modal when the user can edit but not verify", () => {
        const { calendarOptions, emit } = makeCalendar();

        calendarOptions.eventClick(clickInfo({ can: { edit: true } }));

        expect(modalActionsMock.useHappeningEditModal).toHaveBeenCalled();
        expect(emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "edit" }));
    });

    test("falls back to the info modal otherwise", () => {
        const { calendarOptions, emit } = makeCalendar();

        calendarOptions.eventClick(clickInfo({}));

        expect(modalActionsMock.useHappeningInfoModal).toHaveBeenCalled();
        expect(emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "info" }));
    });
});

describe("datesSet (onDatesSet)", () => {
    test("updates the auth store's quotas for the new visible range", () => {
        const authStore = useAuthStore();
        const updateQuotasSpy = vi.spyOn(authStore, "updateQuotas");
        const { calendarOptions } = makeCalendar();
        const start = new Date("2026-03-05T00:00:00Z");

        calendarOptions.datesSet({ start });

        expect(updateQuotasSpy).toHaveBeenCalledWith(start);
    });
});

describe("resourceLabelContent (getResourceInfoLabel)", () => {
    test("renders the translated resource title and an info link that opens the resource info modal", () => {
        const { calendarOptions, emit } = makeCalendar();
        const resourceInfo = {
            resource: {
                extendedProps: {
                    translations: { title: { en: "Study Room" }, resourceGroup: { en: "Library" } },
                    capacity: 4,
                },
            },
        };

        const { domNodes } = calendarOptions.resourceLabelContent(resourceInfo);

        expect(domNodes[0]!.textContent).toBe("Study Room");

        (domNodes[1]! as HTMLAnchorElement).onclick?.(new MouseEvent("click") as unknown as PointerEvent);

        expect(modalActionsMock.useResourceInfoModal).toHaveBeenCalledWith(
            expect.objectContaining({ title: "Study Room", resourceGroup: "Library", capacity: 4 }),
        );
        expect(emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "resource-info" }));
    });
});

describe("calendarOptions", () => {
    test("exposes the configured hidden days and lets callers override defaults", () => {
        const { calendarOptions } = makeCalendar({ calendarOptions: { locale: "de" } });

        expect(calendarOptions.hiddenDays).toEqual([0, 6]);
        expect(calendarOptions.locale).toBe("de");
    });

    test("leaves slot duration unset so FullCalendar's default applies when resource group settings are missing", () => {
        appStoreMock.settings = {};

        const { calendarOptions } = makeCalendar();

        expect(calendarOptions.slotDuration).toBeUndefined();
        expect(calendarOptions.slotLabelInterval).toBeUndefined();
    });
});

describe("refetchHappenings", () => {
    test("refetches events through the calendar api", () => {
        const { refetchHappenings } = makeCalendar();
        const refetchEvents = vi.fn();

        refetchHappenings({ getApi: () => ({ refetchEvents }) });

        expect(refetchEvents).toHaveBeenCalled();
    });

    test("does nothing when the calendar ref is null", () => {
        const { refetchHappenings } = makeCalendar();

        expect(() => refetchHappenings(null)).not.toThrow();
    });
});

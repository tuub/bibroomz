import { type Room, useResourceGridCalendar } from "@/Composables/ResourceGridCalendar";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { Happening } from "@/Stores/HappeningStore";

import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@fullcalendar/vue3/interaction", () => ({ default: { id: "interactionPlugin" } }));
vi.mock("@fullcalendar/vue3/themes/classic", () => ({ default: { id: "classicThemePlugin" } }));
vi.mock("@fullcalendar/vue3/timegrid", () => ({ default: { id: "timeGridPlugin" } }));

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
    getActiveLanguage: () => "en",
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

function makeRoom(id: string, overrides: Partial<Room> = {}): Room {
    return {
        id,
        title: `Room ${id}`,
        businessHours: [{ startTime: "08:00", endTime: "20:00", daysOfWeek: [1, 2, 3, 4, 5] }],
        translations: { title: { en: `Room ${id}` }, description: { en: `About ${id}` } },
        ...overrides,
    };
}

/**
 * `select`, `selectAllow` and `eventClick` are typed `false | handler`, because
 * the overrides can switch them off wholesale, so a test that exercises one has
 * to narrow it first.
 */
function asHandler(value: unknown) {
    expect(value).toBeTypeOf("function");

    return value as (...args: unknown[]) => void;
}

function makeGrid(overrides: Partial<Parameters<typeof useResourceGridCalendar>[0]> = {}) {
    const emit = vi.fn();
    const translate = (value?: Record<string, string>) => value?.en ?? "";

    const grid = useResourceGridCalendar({ emit, translate, ...overrides });

    return { ...grid, emit };
}

let appStore: ReturnType<typeof useAppStore>;
let authStore: ReturnType<typeof useAuthStore>;

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    appStore = useAppStore();
    appStore.resourceGroup = {
        id: 2,
        slug: "library",
        institution: { id: 1, slug: "tu-berlin" },
    };
    appStore.settings = {
        resource_group: {
            weeks_in_advance: "4",
            time_slot_length: "01:00",
            start_time_slot: "08:00:00",
            end_time_slot: "20:00:00",
        },
    };
    appStore.hiddenDays = [0, 6];
    appStore.locale = "en";

    authStore = useAuthStore();
    authStore.isAuthenticated = false;

    axiosMock.mockResolvedValue({ data: {} });
    globalThis.axios = axiosMock as unknown as typeof axios;
});

describe("rooms", () => {
    test("makes no request while there is no current page", async () => {
        const grid = makeGrid();

        await grid.loadRooms();

        expect(axiosMock).not.toHaveBeenCalled();
        expect(grid.rooms.value).toEqual([]);
    });

    test("orders the rooms itself, replacing the premium resourceOrder option", async () => {
        axiosMock.mockResolvedValue({
            data: {
                pagination: { previousPage: null, nextPage: null },
                resources: [makeRoom("c", { order: 3 }), makeRoom("a", { order: 1 }), makeRoom("b", { order: 2 })],
            },
        });

        const grid = makeGrid();
        grid.currentPage.value = "https://rooms.example.com/resources?page=1";

        await grid.loadRooms();

        expect(grid.rooms.value.map((room) => room.id)).toEqual(["a", "b", "c"]);
    });

    test("serves a page it has already fetched from the cache", async () => {
        axiosMock.mockResolvedValue({
            data: { pagination: { previousPage: null, nextPage: null }, resources: [makeRoom("a")] },
        });

        const grid = makeGrid();
        grid.currentPage.value = "https://rooms.example.com/resources?page=1";

        await grid.loadRooms();
        await grid.loadRooms();

        expect(axiosMock).toHaveBeenCalledTimes(1);
    });

    test("prefetches the neighbouring pages so paging does not wait on a request", async () => {
        axiosMock.mockResolvedValue({
            data: {
                pagination: { previousPage: "/resources?page=1", nextPage: "/resources?page=3" },
                resources: [makeRoom("a")],
            },
        });

        const grid = makeGrid();
        grid.currentPage.value = "https://rooms.example.com/resources?page=2";

        await grid.loadRooms();

        const urls = axiosMock.mock.calls.map((call) => (call[0] as { url: string }).url);

        expect(urls).toContain("/resources?page=1");
        expect(urls).toContain("/resources?page=3");
    });

    test("keeps the rooms already on screen when a page fails to load", async () => {
        axiosMock.mockResolvedValueOnce({
            data: { pagination: { previousPage: null, nextPage: null }, resources: [makeRoom("a")] },
        });

        const grid = makeGrid();
        grid.currentPage.value = "https://rooms.example.com/resources?page=1";
        await grid.loadRooms();

        axiosMock.mockRejectedValueOnce(new Error("offline"));
        grid.currentPage.value = "https://rooms.example.com/resources?page=2";
        await grid.loadRooms();

        expect(grid.rooms.value.map((room) => room.id)).toEqual(["a"]);
        expect(grid.isLoadingRooms.value).toBe(false);
    });

    test("exposes the pagination links the page returned", async () => {
        axiosMock.mockResolvedValue({
            data: {
                pagination: { previousPage: "/resources?page=1", nextPage: "/resources?page=3" },
                resources: [makeRoom("a")],
            },
        });

        const grid = makeGrid();
        grid.currentPage.value = "https://rooms.example.com/resources?page=2";

        await grid.loadRooms();

        expect(grid.previousPage.value).toBe("/resources?page=1");
        expect(grid.nextPage.value).toBe("/resources?page=3");
    });
});

describe("happenings", () => {
    test("fetches the whole grid's happenings in one request", async () => {
        axiosMock.mockResolvedValue({ data: [{ resourceId: "a", start: "s", end: "e" }] });

        const grid = makeGrid();
        await grid.loadHappenings();

        expect(axiosMock).toHaveBeenCalledTimes(1);
        expect(axiosMock).toHaveBeenCalledWith(
            expect.objectContaining({
                method: "GET",
                url: "https://rooms.example.com/tu-berlin/library/happenings",
            }),
        );
        expect(grid.happenings.value).toHaveLength(1);
    });

    test("empties the grid when the request fails", async () => {
        axiosMock.mockResolvedValueOnce({ data: [{ resourceId: "a", start: "s", end: "e" }] });

        const grid = makeGrid();
        await grid.loadHappenings();

        axiosMock.mockRejectedValueOnce(new Error("offline"));
        await grid.loadHappenings();

        expect(grid.happenings.value).toEqual([]);
    });
});

describe("columnOptions", () => {
    test("gives a column only its own room's happenings", async () => {
        axiosMock.mockResolvedValue({
            data: [
                { resourceId: "a", start: "s", end: "e" },
                { resourceId: "b", start: "s", end: "e" },
                { resourceId: "a", start: "s2", end: "e2" },
            ],
        });

        const grid = makeGrid();
        await grid.loadHappenings();

        const options = grid.columnOptions(makeRoom("a"), 0);

        expect(options.events).toHaveLength(2);
        expect(options.events.every((event) => event.resourceId === "a")).toBe(true);
    });

    test("matches a room to its happenings across number and string ids", async () => {
        axiosMock.mockResolvedValue({ data: [{ resourceId: 7, start: "s", end: "e" }] });

        const grid = makeGrid();
        await grid.loadHappenings();

        expect(grid.columnOptions(makeRoom("7"), 0).events).toHaveLength(1);
    });

    test("shows the time axis labels in the first column only", () => {
        const grid = makeGrid();

        expect(grid.columnOptions(makeRoom("a"), 0).slotHeaderContent).toBeUndefined();

        // Columns 2..N still render a label, because an empty one leaves no line
        // box and collapses the slot row heights - it just has no width.
        const later = grid.columnOptions(makeRoom("b"), 1).slotHeaderContent;

        expect(later?.()).toEqual({ html: '<span class="roomz-grid-axis-spacer"></span>' });
    });

    test("marks the axis of every column but the first as collapsed", () => {
        const grid = makeGrid();
        const headerInfo = { time: { milliseconds: 0 } };

        expect(grid.columnOptions(makeRoom("a"), 0).slotHeaderClass(headerInfo)).not.toContain(
            "roomz-grid-axis-collapsed",
        );
        expect(grid.columnOptions(makeRoom("b"), 1).slotHeaderClass(headerInfo)).toContain("roomz-grid-axis-collapsed");
    });

    test("makes every column's header sticky, not just the first", () => {
        const grid = makeGrid();

        expect(grid.columnOptions(makeRoom("a"), 0).tableHeaderSticky).toBe(true);
        expect(grid.columnOptions(makeRoom("b"), 2).tableHeaderSticky).toBe(true);
    });

    test("passes each room its own business hours", () => {
        const grid = makeGrid();
        const room = makeRoom("a", {
            businessHours: [{ startTime: "09:00", endTime: "17:00", daysOfWeek: [1] }],
        });

        expect(grid.columnOptions(room, 0).businessHours).toEqual(room.businessHours);
    });

    test("references no premium plugin", () => {
        const grid = makeGrid();

        expect(grid.columnOptions(makeRoom("a"), 0).plugins).toEqual([
            { id: "classicThemePlugin" },
            { id: "interactionPlugin" },
            { id: "timeGridPlugin" },
        ]);
    });
});

describe("selection", () => {
    const selection = { startStr: "2026-09-16T10:00:00Z", endStr: "2026-09-16T11:00:00Z" };

    test("asks a guest to log in before opening the create modal", () => {
        const grid = makeGrid();
        const room = makeRoom("a");

        asHandler(grid.columnOptions(room, 0).select)(selection);

        expect(grid.emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "login" }));

        // Logging in continues into the booking the guest started.
        modalActionsMock.useLoginModal.mock.calls[0]?.[0]?.();

        expect(modalActionsMock.useHappeningCreateModal).toHaveBeenCalledWith(
            expect.objectContaining({ isSelected: true, resource: expect.objectContaining({ id: "a" }) }),
        );
    });

    test("opens the create modal directly for a signed-in user", () => {
        authStore.isAuthenticated = true;

        const grid = makeGrid();
        asHandler(grid.columnOptions(makeRoom("a"), 0).select)(selection);

        expect(grid.emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "create" }));
    });

    test("takes the room from the column rather than from the selection", () => {
        authStore.isAuthenticated = true;

        const grid = makeGrid();
        asHandler(grid.columnOptions(makeRoom("b"), 3).select)(selection);

        expect(modalActionsMock.useHappeningCreateModal).toHaveBeenCalledWith(
            expect.objectContaining({ resource: expect.objectContaining({ id: "b" }) }),
        );
    });

    test("disables selection entirely when the caller overrides it", () => {
        const grid = makeGrid({ overrides: { selectable: false } });
        const options = grid.columnOptions(makeRoom("a"), 0);

        expect(options.selectable).toBe(false);
        expect(options.select).toBe(false);
        expect(options.selectAllow).toBe(false);
    });
});

describe("event clicks", () => {
    function clickInfo(overrides: Record<string, unknown> = {}) {
        return {
            el: document.createElement("div"),
            event: {
                id: "42",
                start: new Date("2026-09-16T10:00:00Z"),
                end: new Date("2026-09-16T11:00:00Z"),
                extendedProps: {},
                ...overrides,
            },
        };
    }

    test("opens the verify modal when the user may verify", () => {
        const grid = makeGrid();

        asHandler(grid.columnOptions(makeRoom("a"), 0).eventClick)(
            clickInfo({ extendedProps: { can: { verify: true, edit: true } } }),
        );

        expect(grid.emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "verify" }));
    });

    test("opens the edit modal when the user may edit but not verify", () => {
        const grid = makeGrid();

        asHandler(grid.columnOptions(makeRoom("a"), 0).eventClick)(
            clickInfo({ extendedProps: { can: { edit: true } } }),
        );

        expect(grid.emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "edit" }));
    });

    test("falls back to the info modal", () => {
        const grid = makeGrid();

        asHandler(grid.columnOptions(makeRoom("a"), 0).eventClick)(clickInfo());

        expect(grid.emit).toHaveBeenCalledWith("open-modal-component", expect.objectContaining({ kind: "info" }));
    });

    test("ignores clicks on a closing, which is a background event", () => {
        const grid = makeGrid();

        asHandler(grid.columnOptions(makeRoom("a"), 0).eventClick)(clickInfo({ display: "background" }));

        expect(grid.emit).not.toHaveBeenCalled();
    });

    test("disables event clicks entirely when the caller overrides it", () => {
        const grid = makeGrid({ overrides: { interactive: false } });

        expect(grid.columnOptions(makeRoom("a"), 0).eventClick).toBe(false);
    });
});

describe("room info", () => {
    test("opens the info modal for the room whose header was clicked", () => {
        const grid = makeGrid();

        grid.openRoomInfo(makeRoom("a", { capacity: 4, location_uri: "https://maps.example.com/a" }));

        expect(modalActionsMock.useResourceInfoModal).toHaveBeenCalledWith(
            expect.objectContaining({
                title: "Room a",
                description: "About a",
                capacity: 4,
                location_uri: "https://maps.example.com/a",
            }),
        );
        expect(grid.emit).toHaveBeenCalledWith(
            "open-modal-component",
            expect.objectContaining({ kind: "resource-info" }),
        );
    });
});

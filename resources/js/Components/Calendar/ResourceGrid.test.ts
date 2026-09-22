import ResourceGrid from "@/Components/Calendar/ResourceGrid.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import dayjs from "dayjs";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { ref } from "vue";

const gridState = vi.hoisted(() => ({
    rooms: [] as { id: string; title: string; businessHours: never[] }[],
    columnOptions: vi.fn(),
    gotoDate: vi.fn(),
}));

const useResourceGridCalendarMock = vi.fn((args: unknown) => {
    gridState.columnOptions.mockImplementation((room: { id: string }, index: number) => ({ room, index }));

    return {
        args,
        rooms: ref(gridState.rooms),
        date: ref(dayjs("2026-09-16")),
        validRange: { start: new Date("2026-09-01"), end: new Date("2026-10-31") },
        isLoadingRooms: ref(false),
        currentPage: ref(null),
        nextPage: ref(null),
        previousPage: ref(null),
        loadRooms: vi.fn(() => Promise.resolve()),
        loadHappenings: vi.fn(() => Promise.resolve()),
        columnOptions: gridState.columnOptions,
    };
});

vi.mock("@/Composables/ResourceGridCalendar", () => ({
    useResourceGridCalendar: (args: unknown) => useResourceGridCalendarMock(args),
}));

vi.mock("@/baseUrl", () => ({
    withBaseUrl: (path: string) => `https://rooms.example.com${path}`,
}));

// Renders the header element the real day header produces, so the component's
// header measuring has something to find, and exposes `getApi()` the way the
// real component does, so date navigation can be observed per column.
vi.mock("@fullcalendar/vue3", () => ({
    default: {
        name: "FullCalendarStub",
        props: ["options"],
        methods: {
            getApi(this: { options?: { room?: { id: string } } }) {
                const room = this.options?.room;

                return { gotoDate: (date: Date) => gridState.gotoDate(room?.id, date) };
            },
        },
        template: '<div data-test="full-calendar"><div class="roomz-calendar-resource-header-inner"></div></div>',
    },
}));

/**
 * happy-dom has no layout, so the headers' heights have to be supplied.
 *
 * The stub reproduces what the stylesheet does - `height: var(
 * --roomz-grid-header-height, auto)` - by reporting the pinned height when one
 * is set and the natural height otherwise. Without that, a header would report
 * its natural height even while pinned, and the measuring code could never be
 * caught reading back its own previous answer.
 */
function stubHeaderHeights(wrapper: ReturnType<typeof mount>, naturalHeights: number[]) {
    const root = wrapper.element.querySelector(".roomz-grid-root") as HTMLElement;
    const headers = [...root.querySelectorAll(".roomz-calendar-resource-header-inner")];

    headers.forEach((header, index) => {
        header.getBoundingClientRect = () => {
            const pinned = Number.parseFloat(root.style.getPropertyValue("--roomz-grid-header-height"));

            return { height: Number.isNaN(pinned) ? (naturalHeights[index] ?? 0) : pinned } as DOMRect;
        };
    });
}

function gridRootStyle(wrapper: ReturnType<typeof mount>) {
    return (wrapper.element.querySelector(".roomz-grid-root") as HTMLElement).style;
}

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    gridState.rooms = [
        { id: "a", title: "Room a", businessHours: [] },
        { id: "b", title: "Room b", businessHours: [] },
    ];

    const appStore = useAppStore();
    appStore.resourceGroup = {
        slug: "rooms",
        title: { en: "Rooms" },
        institution: { slug: "tu-berlin", title: { en: "TU Berlin" } },
    };
    appStore.settings = { resource_group: { time_slot_length: "01:00" } };
    appStore.dateFormat = "DD.MM.YYYY";
    appStore.locale = "en";
    appStore.translate = vi.fn((value?: string | Record<string, string>) =>
        typeof value === "string" ? value : (value?.en ?? ""),
    );

    const authStore = useAuthStore();
    authStore.isAuthenticated = false;
    authStore.isAdmin = false;
    authStore.userHappenings = [];

    globalThis.Echo = {
        channel: vi.fn(() => ({ listen: vi.fn() })),
        leave: vi.fn(),
    } as unknown as typeof Echo;
});

function render() {
    return mount(ResourceGrid, {
        attachTo: document.body,
        global: {
            mocks: { $t: (key: string) => key },
            stubs: { Legend: true },
        },
    });
}

describe("ResourceGrid component", () => {
    test("renders one calendar per room instead of one calendar with resource columns", () => {
        const wrapper = render();

        expect(wrapper.findAll('[data-test="full-calendar"]')).toHaveLength(2);
        expect(wrapper.findAll(".roomz-grid-col")).toHaveLength(2);
    });

    test("marks only the first column, which is the one that shows the time axis", () => {
        const wrapper = render();
        const columns = wrapper.findAll(".roomz-grid-col");

        expect(columns[0]?.classes()).toContain("roomz-grid-col-first");
        expect(columns[1]?.classes()).not.toContain("roomz-grid-col-first");
    });

    test("builds each column's options from its room and position", () => {
        render();

        expect(gridState.columnOptions).toHaveBeenCalledWith(expect.objectContaining({ id: "a" }), 0);
        expect(gridState.columnOptions).toHaveBeenCalledWith(expect.objectContaining({ id: "b" }), 1);
    });

    test("tags each column with its room, so a column can be addressed in tests and styles", () => {
        const wrapper = render();

        expect(wrapper.findAll(".roomz-grid-col").map((column) => column.attributes("data-room-id"))).toEqual([
            "a",
            "b",
        ]);
    });

    test("passes its emit function into the composable", () => {
        render();

        expect(useResourceGridCalendarMock).toHaveBeenCalledWith(
            expect.objectContaining({ emit: expect.any(Function), translate: expect.any(Function) }),
        );
    });

    test("forwards open-modal-component emitted through the composable", () => {
        const wrapper = render();
        const { emit } = useResourceGridCalendarMock.mock.calls[0]?.[0] as {
            emit: (event: string, payload?: unknown) => void;
        };
        const payload = { view: { name: "ModalView" }, content: { title: "Inspect" } };

        emit("open-modal-component", payload);

        expect(wrapper.emitted("open-modal-component")).toEqual([[payload]]);
    });

    test("forwards show-status emitted through the composable", () => {
        const wrapper = render();
        const { emit } = useResourceGridCalendarMock.mock.calls[0]?.[0] as {
            emit: (event: string, payload?: unknown) => void;
        };

        emit("show-status");

        expect(wrapper.emitted("show-status")).toEqual([[]]);
    });
});

describe("header alignment", () => {
    // Separate calendars size their headers independently, so a room name that
    // wraps would push only its own column's grid down unless the headers are
    // made to agree on a height.
    test("pins every header to the tallest one", async () => {
        const wrapper = render();

        stubHeaderHeights(wrapper, [24, 61]);
        window.dispatchEvent(new Event("resize"));
        await wrapper.vm.$nextTick();

        expect(gridRootStyle(wrapper).getPropertyValue("--roomz-grid-header-height")).toBe("61px");
    });

    test("rounds up, so no column is left a fraction of a pixel short", async () => {
        const wrapper = render();

        stubHeaderHeights(wrapper, [24, 60.5]);
        window.dispatchEvent(new Event("resize"));
        await wrapper.vm.$nextTick();

        expect(gridRootStyle(wrapper).getPropertyValue("--roomz-grid-header-height")).toBe("61px");
    });

    test("lets the headers shrink again when the long name is paged away", async () => {
        const wrapper = render();

        stubHeaderHeights(wrapper, [24, 61]);
        window.dispatchEvent(new Event("resize"));
        await wrapper.vm.$nextTick();

        // Measuring without releasing the pinned height first would only ever
        // ratchet upwards, keeping the room a name that is no longer shown needed.
        stubHeaderHeights(wrapper, [24, 24]);
        window.dispatchEvent(new Event("resize"));
        await wrapper.vm.$nextTick();

        expect(gridRootStyle(wrapper).getPropertyValue("--roomz-grid-header-height")).toBe("24px");
    });
});

describe("date navigation", () => {
    // Each column is its own calendar, and FullCalendar reads `initialDate` only
    // at construction: without an explicit gotoDate the toolbar would move to the
    // next day while every column kept rendering the day it was mounted on.
    test("walks every column to the new day", async () => {
        const wrapper = render();

        await wrapper.find("#calendar-date-next").trigger("click");

        expect(gridState.gotoDate.mock.calls).toEqual([
            ["a", dayjs("2026-09-17").toDate()],
            ["b", dayjs("2026-09-17").toDate()],
        ]);
    });

    test("walks every column back again", async () => {
        const wrapper = render();

        await wrapper.find("#calendar-date-previous").trigger("click");

        expect(gridState.gotoDate.mock.calls).toEqual([
            ["a", dayjs("2026-09-15").toDate()],
            ["b", dayjs("2026-09-15").toDate()],
        ]);
    });
});

import Calendar from "@/Components/Calendar/Calendar.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";

const refetchHappeningsMock = vi.fn();
const useCalendarMock = vi.fn((args: unknown) => ({
    calendarOptions: { mocked: true, args },
    refetchHappenings: refetchHappeningsMock,
}));

vi.mock("@/Composables/Calendar", () => ({
    useCalendar: (args: unknown) => useCalendarMock(args),
}));

vi.mock("@fullcalendar/vue3", () => ({
    default: {
        name: "FullCalendarStub",
        props: ["options"],
        template: '<div data-test="full-calendar"><div class="fc-timegrid-axis-frame"></div><slot /></div>',
        methods: {
            getApi() {
                return {
                    getOption: () => ({ start: new Date(), end: new Date() }),
                    getDate: () => new Date("2026-03-05T00:00:00Z"),
                    next: vi.fn(),
                    prev: vi.fn(),
                    refetchResources: vi.fn(),
                    setOption: vi.fn(),
                };
            },
        },
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    const appStore = useAppStore();
    appStore.resourceGroup = {
        slug: "rooms",
        title: { en: "Rooms" },
        institution: {
            slug: "tu-berlin",
            title: { en: "TU Berlin" },
        },
    };
    appStore.settings = {
        resource_group: {
            time_slot_length: "01:00",
        },
    };
    appStore.dateFormat = "DD.MM.YYYY";
    appStore.locale = "en";
    appStore.translate = vi.fn((value?: string | Record<string, string>) => {
        if (typeof value === "string") {
            return value;
        }

        return value?.en ?? "";
    });

    const authStore = useAuthStore();
    authStore.isAuthenticated = false;
    authStore.isAdmin = false;
    authStore.userHappenings = [];

    globalThis.Echo = {
        channel: vi.fn(() => ({
            listen: vi.fn(),
        })),
        leave: vi.fn(),
    } as unknown as typeof Echo;
});

function render() {
    return mount(Calendar, {
        attachTo: document.body,
        global: {
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                Legend: true,
            },
        },
    });
}

describe("Calendar component", () => {
    test("passes its emit function into useCalendar", () => {
        render();

        expect(useCalendarMock).toHaveBeenCalledWith(
            expect.objectContaining({
                emit: expect.any(Function),
                pagination: expect.objectContaining({
                    currentPage: expect.stringContaining("/tu-berlin/rooms/resources"),
                }),
            }),
        );
    });

    test("forwards open-modal-component emitted through useCalendar", () => {
        const wrapper = render();
        const emit = (useCalendarMock.mock.calls[0]?.[0] as { emit: (event: string, payload?: unknown) => void }).emit;
        const payload = {
            view: { name: "ModalView" },
            content: { title: "Inspect" },
            payload: { id: 7 },
            actions: [{ label: "ok" }],
        };

        emit("open-modal-component", payload);

        expect(wrapper.emitted("open-modal-component")).toEqual([[payload]]);
    });

    test("forwards show-status emitted through useCalendar", () => {
        const wrapper = render();
        const emit = (useCalendarMock.mock.calls[0]?.[0] as { emit: (event: string, payload?: unknown) => void }).emit;

        emit("show-status");

        expect(wrapper.emitted("show-status")).toEqual([[]]);
    });
});

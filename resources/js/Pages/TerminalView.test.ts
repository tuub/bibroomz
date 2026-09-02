import TerminalView from "@/Pages/TerminalView.vue";
import { useAppStore } from "@/Stores/AppStore";

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
        template: '<div data-test="full-calendar"><slot /></div>',
    },
}));

beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    globalThis.Echo = {
        channel: vi.fn(() => ({
            listen: vi.fn(),
        })),
        leave: vi.fn(),
    } as unknown as typeof Echo;
});

function render() {
    return mount(TerminalView, {
        props: {
            resourceGroup: {
                slug: "rooms",
                institution: {
                    slug: "tu-berlin",
                },
            },
            settings: {
                institution: {},
            },
            hiddenDays: [0, 6],
        },
    });
}

describe("TerminalView", () => {
    test("initializes the app store and calendar composable from typed props", () => {
        const appStore = useAppStore();
        const setCurrentSpy = vi.spyOn(appStore, "setCurrent");

        const wrapper = render();

        expect(setCurrentSpy).toHaveBeenCalledWith(
            {
                slug: "rooms",
                institution: {
                    slug: "tu-berlin",
                },
            },
            {
                institution: {},
            },
            [0, 6],
            false,
        );

        expect(useCalendarMock).toHaveBeenCalledWith({
            calendarOptions: {
                headerToolbar: {
                    left: "title",
                    center: "",
                    right: "",
                },
                selectable: false,
                select: false,
                selectAllow: false,
                eventClick: false,
            },
            pagination: {
                currentPage: "/tu-berlin/rooms/resources",
                nextPage: null,
                previousPage: null,
            },
            translate: appStore.translate,
        });

        expect(wrapper.find('[data-test="full-calendar"]').exists()).toBe(true);
    });

    test("disables select, selectAllow, and eventClick interactions on the unattended kiosk view", () => {
        render();

        const options = useCalendarMock.mock.calls[0]?.[0] as {
            calendarOptions: Record<string, unknown>;
        };

        expect(options.calendarOptions).toMatchObject({
            selectable: false,
            select: false,
            selectAllow: false,
            eventClick: false,
        });
    });

    test("subscribes to happening updates on mount and leaves the channel on unmount", () => {
        const wrapper = render();

        expect(Echo.channel).toHaveBeenCalledWith("happenings");

        const channelResult = (Echo.channel as unknown as ReturnType<typeof vi.fn>).mock.results[0];
        expect(channelResult?.value).toBeDefined();

        const listenMock = (channelResult?.value as { listen: ReturnType<typeof vi.fn> }).listen;
        expect(listenMock).toHaveBeenCalledWith("HappeningsChangedEvent", expect.any(Function));

        const listenCall = listenMock.mock.calls[0];
        expect(listenCall).toBeDefined();

        const listener = listenCall?.[1] as () => void;
        listener();

        expect(refetchHappeningsMock).toHaveBeenCalledOnce();

        wrapper.unmount();

        expect(Echo.leave).toHaveBeenCalledWith("happenings");
    });
});

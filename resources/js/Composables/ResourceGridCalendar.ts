/**
 * The resource calendar, built on the MIT-only FullCalendar packages.
 *
 * The app used to render this view with the premium
 * `@fullcalendar/vue3-scheduler` package, which supplied exactly one thing it
 * needed: resource columns (`resourceTimeGridDay`, plus the `resources`,
 * `refetchResources`, `getResources`, `resourceOrder` and `resourceDayHeaderContent`
 * APIs that hang off it). Everything else the calendar uses - selection, business
 * hours, background events, the class hooks, sticky headers - is in the MIT
 * `@fullcalendar/vue3` package. The premium package's copyleft terms were what
 * forced the app to AGPLv3, which is the reason this rewrite exists at all.
 *
 * So instead of one calendar with N resource columns, this renders N single-day
 * `timeGridDay` calendars side by side, one per room. The room list, paging and
 * event fetching therefore live in ordinary Vue state here rather than in
 * FullCalendar's own resource callbacks.
 */
import interactionPlugin from "@fullcalendar/vue3/interaction";
import themePlugin from "@fullcalendar/vue3/themes/classic";
import timeGridPlugin from "@fullcalendar/vue3/timegrid";

import {
    useHappeningCreateModal,
    useHappeningEditModal,
    useHappeningInfoModal,
    useHappeningVerifyModal,
    useLoginModal,
    useResourceInfoModal,
} from "@/Composables/ModalActions";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { Happening } from "@/Stores/HappeningStore";
import type { ModalOpenPayload } from "@/Stores/Modal";
import type { Translatable } from "@/Types/Admin";
import { appWallClock } from "@/appTime";
import { withBaseUrl } from "@/baseUrl";

import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import utc from "dayjs/plugin/utc";
import { trans } from "laravel-vue-i18n";
import { storeToRefs } from "pinia";
import { computed, ref } from "vue";

dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);
dayjs.extend(isBetween);
dayjs.extend(utc);

export type RoomBusinessHours = {
    startTime: string;
    endTime: string;
    daysOfWeek: number[];
};

/**
 * Mirrors PublicResourcePresenter::present(). This is the same payload the
 * premium build hands to FullCalendar as a `ResourceInput`; the difference is
 * that here it stays a plain object we own rather than becoming an opaque
 * FullCalendar Resource with an `extendedProps` bag.
 */
export type Room = {
    id: number | string;
    title: string;
    order?: number;
    capacity?: number;
    location_uri?: string;
    isVerificationRequired?: boolean;
    businessHours: RoomBusinessHours[];
    translations?: {
        title?: Translatable;
        location?: Translatable;
        description?: Translatable;
        resourceGroup?: Translatable;
    };
};

/** Mirrors CalendarEntryPresenter - every entry carries `resourceId`. */
export type CalendarEntry = {
    id?: number | string;
    resourceId: number | string;
    start: string;
    end: string;
    display?: string;
    className?: string;
    label?: Translatable;
    description?: Translatable;
    user_01?: string | null;
    isVerificationRequired?: boolean;
    status?: { user?: { verification?: string }; type?: string } | null;
    can?: { verify?: boolean; edit?: boolean; delete?: boolean };
    [key: string]: unknown;
};

type RoomsResponse = {
    data: {
        pagination: { previousPage: string | null; nextPage: string | null };
        resources: Room[];
    };
};

type HappeningsResponse = { data: CalendarEntry[] };

type SelectInfo = { startStr: string; endStr: string };

type EventClickInfo = {
    el: HTMLElement;
    event: {
        id?: number | string;
        display?: string;
        start: Date | null;
        end: Date | null;
        extendedProps: Record<string, unknown>;
    };
};

type CalendarEmit = {
    (event: "show-status"): void;
    <Props>(event: "open-modal-component", payload: ModalOpenPayload<Props>): void;
};

function noopCalendarEmit(event: "show-status"): void;
function noopCalendarEmit<Props>(event: "open-modal-component", payload: ModalOpenPayload<Props>): void;
function noopCalendarEmit(): void {}

export type ResourceGridOverrides = {
    selectable?: boolean;
    interactive?: boolean;
};

export function useResourceGridCalendar({
    emit = noopCalendarEmit,
    translate,
    overrides = {},
}: {
    emit?: CalendarEmit;
    translate: (value?: Translatable) => string;
    overrides?: ResourceGridOverrides;
}) {
    const appStore = useAppStore();
    const institution = appStore.institution;
    const resourceGroup = appStore.resourceGroup;
    const resourceGroupSettings = appStore.settings?.resource_group;
    const timeSlotLength = resourceGroupSettings?.["time_slot_length"];
    const hiddenDays = appStore.hiddenDays;

    const authStore = useAuthStore();
    const { isAuthenticated } = storeToRefs(authStore);

    const isInteractive = overrides.interactive !== false;
    const isSelectable = overrides.selectable !== false;

    // ------------------------------------------------
    // State that FullCalendar used to own
    // ------------------------------------------------
    const rooms = ref<Room[]>([]);
    const happenings = ref<CalendarEntry[]>([]);
    const date = ref(appStore.now());
    const isLoadingRooms = ref(false);

    const currentPage = ref<string | null>(null);
    const nextPage = ref<string | null>(null);
    const previousPage = ref<string | null>(null);

    // The cache is keyed by URL, which already encodes page, count and date. It
    // is plain axios, never coupled to FullCalendar - which is why the room list
    // could move out of the resource callbacks unchanged.
    const roomPageCache = new Map<string, RoomsResponse["data"]>();

    function prefetchRoomPage(url: string | null) {
        if (!url || roomPageCache.has(url)) {
            return;
        }

        axios({ method: "GET", url })
            .then((response: RoomsResponse) => {
                roomPageCache.set(url, response.data);
            })
            .catch(() => {
                // Best-effort, exactly as in the premium build.
            });
    }

    function applyRoomPage(data: RoomsResponse["data"]) {
        previousPage.value = data.pagination.previousPage;
        nextPage.value = data.pagination.nextPage;

        // `resourceOrder: "order"` was a premium option; ordering is ours now.
        rooms.value = [...data.resources].sort((a, b) => Number(a.order ?? 0) - Number(b.order ?? 0));

        prefetchRoomPage(data.pagination.previousPage);
        prefetchRoomPage(data.pagination.nextPage);
    }

    function loadRooms() {
        const url = currentPage.value;
        if (!url) {
            return Promise.resolve();
        }

        const cached = roomPageCache.get(url);
        if (cached) {
            applyRoomPage(cached);
            return Promise.resolve();
        }

        isLoadingRooms.value = true;

        return axios({ method: "GET", url })
            .then((response: RoomsResponse) => {
                roomPageCache.set(url, response.data);

                if (currentPage.value === url) {
                    applyRoomPage(response.data);
                }
            })
            .catch(() => {
                // Matches the premium build's failureCallback: a failed page
                // leaves the previous rooms on screen rather than blanking the
                // grid, and the next navigation retries.
            })
            .finally(() => {
                if (currentPage.value === url) {
                    isLoadingRooms.value = false;
                }
            });
    }

    /**
     * One request for the whole grid, as today - the premium build also issued a
     * single `events` fetch covering every resource. Each column filters this
     * list by `resourceId` rather than making a request of its own.
     */
    function loadHappenings() {
        const start = date.value.startOf("day");
        const end = date.value.endOf("day");

        return axios({
            method: "GET",
            url: withBaseUrl(`/${institution?.slug}/${resourceGroup?.slug}/happenings`),
            params: { start: appWallClock(start), end: appWallClock(end) },
        })
            .then((response: HappeningsResponse) => {
                happenings.value = response.data;
            })
            .catch(() => {
                happenings.value = [];
            });
    }

    function getValidRange() {
        const weeksInAdvance = resourceGroupSettings?.["weeks_in_advance"];
        const startDate = appStore.now();

        return {
            start: startDate.toDate(),
            end: startDate.add(Number(weeksInAdvance), "week").toDate(),
        };
    }

    const validRange = getValidRange();

    // ------------------------------------------------
    // Interaction - resource now comes from the column, not from the event
    // ------------------------------------------------
    function canSelect(room: Room, selection: SelectInfo) {
        if (authStore.isAuthenticated && !authStore.isAllowedForResource(room)) {
            return false;
        }

        const tsStart = dayjs.utc(selection.startStr);
        const tsEnd = dayjs.utc(selection.endStr);

        if (authStore.isAuthenticated && authStore.isExceedingQuotas(tsStart, tsEnd)) {
            return false;
        }

        const tsLenConfig = (resourceGroupSettings?.["time_slot_length"] ?? "00:00").split(":");
        const tsLen = {
            hours: parseInt(tsLenConfig[0] ?? "0"),
            minutes: parseInt(tsLenConfig[1] ?? "0"),
        };

        const now = appStore.now();
        const isNotPast = tsStart.isSameOrAfter(now);
        const isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);
        const isValid = tsStart.add(tsLen.hours, "hours").add(tsLen.minutes, "minutes").isAfter(now);

        return isValid && (isNotPast || isCurrentTimeSlot);
    }

    function describeRoom(room: Room) {
        return {
            id: room.id,
            title: translate(room.translations?.title),
            location: translate(room.translations?.location),
            location_uri: room.location_uri,
            capacity: room.capacity,
            description: translate(room.translations?.description),
            resourceGroup: translate(room.translations?.resourceGroup),
        };
    }

    function onSelect(room: Room, selection: SelectInfo) {
        const openCreateModal = () => {
            emit(
                "open-modal-component",
                useHappeningCreateModal({
                    isSelected: true,
                    resource: describeRoom(room),
                    start: appWallClock(selection.startStr),
                    end: appWallClock(selection.endStr),
                    isVerificationRequired: room.isVerificationRequired,
                }),
            );
        };

        if (!isAuthenticated.value) {
            emit("open-modal-component", useLoginModal(openCreateModal));
        } else {
            openCreateModal();
        }
    }

    function onEventClick(room: Room, info: EventClickInfo) {
        const isBgEvent = info.event.display === "background" || info.el.classList.contains("roomz-calendar-bg-event");

        if (isBgEvent || !info.event.start || !info.event.end) {
            return;
        }

        const props = info.event.extendedProps as CalendarEntry;

        const happening: Happening = {
            resource: describeRoom(room),
            id: info.event.id,
            user_01: props.user_01 ?? undefined,
            user_02: props.status?.user?.verification,
            start: appWallClock(info.event.start),
            end: appWallClock(info.event.end),
            isVerificationRequired: props.isVerificationRequired,
            can: props.can,
            label: props.label,
        };

        if (happening.can?.verify) {
            emit("open-modal-component", useHappeningVerifyModal(happening));
        } else if (happening.can?.edit) {
            emit("open-modal-component", useHappeningEditModal(happening));
        } else {
            emit("open-modal-component", useHappeningInfoModal(happening));
        }
    }

    function openRoomInfo(room: Room) {
        emit(
            "open-modal-component",
            useResourceInfoModal({
                resourceGroup: translate(room.translations?.resourceGroup),
                title: translate(room.translations?.title),
                description: translate(room.translations?.description),
                location: translate(room.translations?.location),
                location_uri: room.location_uri,
                capacity: room.capacity,
            }),
        );
    }

    const roomInfoTitle = computed(() =>
        appStore.locale === "de" ? trans("calendar.resource_info.de") : trans("calendar.resource_info.en"),
    );

    // ------------------------------------------------
    // Class hooks
    // ------------------------------------------------
    function getSlotIndex(milliseconds: number) {
        const [tsHours, tsMinutes] = (timeSlotLength ?? "").split(":");
        const parsedSlotMinutes = (parseInt(tsHours ?? "0") || 0) * 60 + (parseInt(tsMinutes ?? "0") || 0);
        const slotMinutes = parsedSlotMinutes || 30;

        return Math.round(milliseconds / 60000 / slotMinutes);
    }

    function getSlotLaneClass(laneInfo: { time?: { milliseconds: number } }) {
        return getSlotIndex(laneInfo.time?.milliseconds ?? 0) % 2 === 0
            ? "roomz-calendar-slot-lane"
            : "roomz-calendar-slot-lane roomz-calendar-slot-lane-alt";
    }

    function getDayLaneClass(laneInfo: { isPast?: boolean }) {
        return laneInfo.isPast ? "roomz-calendar-day-lane-past" : "";
    }

    function getColumnEventClass(eventInfo: { isInteractive?: boolean }) {
        return eventInfo.isInteractive ? "roomz-calendar-event roomz-calendar-event-clickable" : "roomz-calendar-event";
    }

    function getTimeFormat() {
        return {
            hour: "numeric" as const,
            minute: "2-digit" as const,
            meridiem: false as const,
            hour12: false as const,
        };
    }

    /**
     * The axis is rendered by the first column only; the rest collapse it to
     * zero width in CSS, so every column keeps FullCalendar's own row geometry
     * (identical slotDuration/slotMinTime/slotMaxTime everywhere) without any
     * cross-instance measuring.
     */
    function getSlotHeaderClass(isFirst: boolean, headerInfo: { time?: { milliseconds: number } }) {
        const parity =
            getSlotIndex(headerInfo.time?.milliseconds ?? 0) % 2 !== 0 ? "roomz-calendar-slot-header-alt" : "";

        return isFirst ? parity : `${parity} roomz-grid-axis-collapsed`;
    }

    /**
     * The room name + info link, rendered into FullCalendar's own day-header
     * slot. Putting it there rather than in a separate header row above the
     * grid is what keeps it aligned with the time axis: FullCalendar lays the
     * header out against its own axis inside each column, so no cross-instance
     * measuring is needed.
     *
     * This is the premium `resourceDayHeaderContent` callback nearly verbatim -
     * the only change is that the room comes from the column closure instead of
     * from `resourceInfo.resource`.
     */
    function getRoomHeader(room: Room) {
        const title = document.createElement("span");
        title.textContent = translate(room.translations?.title) ?? "";

        const link = document.createElement("a");
        link.href = "#";
        link.classList.add("ml-1");
        link.title = roomInfoTitle.value;
        link.innerHTML = '<i class="ri-information-line"></i>';
        link.onclick = (mouseEvent) => {
            mouseEvent.preventDefault();
            openRoomInfo(room);
        };

        return { domNodes: [title, link] };
    }

    /** Per-column FullCalendar options. Only MIT plugins are referenced here. */
    function columnOptions(room: Room, index: number) {
        const isFirst = index === 0;

        return {
            plugins: [themePlugin, interactionPlugin, timeGridPlugin],
            initialView: "timeGridDay",
            initialDate: date.value.toDate(),
            headerToolbar: false as const,
            dayHeaderContent: () => getRoomHeader(room),
            dayHeaderInnerClass: "roomz-calendar-resource-header-inner",
            dayHeaderDividerClass: "roomz-calendar-day-header-divider",
            locale: appStore.locale,
            timeZone: "utc",
            validRange,
            events: happenings.value
                .filter((entry) => String(entry.resourceId) === String(room.id))
                .map((entry) => ({ ...entry, extendedProps: entry })),
            businessHours: room.businessHours,
            slotMinTime: resourceGroupSettings?.["start_time_slot"],
            slotMaxTime: resourceGroupSettings?.["end_time_slot"],
            height: "auto",
            contentHeight: "auto",
            tableHeaderSticky: true,
            weekends: true,
            hiddenDays,
            editable: false,
            nowIndicator: true,
            // Without this, every "now" FullCalendar derives itself - the
            // indicator line and the past/today lane classes - is the browser's
            // clock read as UTC, which trails the wall-clock values the columns
            // render by the app timezone's offset. Re-read on every tick, so
            // the indicator keeps moving.
            now: () => appStore.now().toDate(),
            allDaySlot: false,
            longPressDelay: import.meta.env.VITE_LONG_PRESS_DELAY ?? 500,
            unselectAuto: true,
            selectMirror: true,
            slotDuration: timeSlotLength && `${timeSlotLength}:00`,
            slotHeaderInterval: timeSlotLength && `${timeSlotLength}:00`,
            selectOverlap: false,
            selectConstraint: "businessHours",
            selectable: isSelectable,
            selectAllow: isSelectable ? (selection: SelectInfo) => canSelect(room, selection) : false,
            select: isSelectable ? (selection: SelectInfo) => onSelect(room, selection) : false,
            eventClick: isInteractive ? (info: EventClickInfo) => onEventClick(room, info) : false,
            // What actually collapses the axis in columns 2..N. v7 sizes the axis
            // column - and the matching spacer element it renders into every body
            // row - from this content, and that spacer carries only minified
            // `fc-*` class names, so no public class hook can reach it. Feeding it
            // a zero-width element sizes it away from the inside instead.
            //
            // The element has to be real inline content, not "" and not a blank
            // character: an empty label leaves no line box at all and v7 then
            // collapses every slot row's height, while a space or a zero-width
            // character is measured by the font (a font missing the glyph draws a
            // tofu box, which is how this last gap survived). An empty inline-block
            // still establishes a line box - so the row keeps its height - while
            // contributing no width of its own.
            slotHeaderContent: isFirst ? undefined : () => ({ html: '<span class="roomz-grid-axis-spacer"></span>' }),
            slotHeaderFormat: getTimeFormat(),
            eventTimeFormat: getTimeFormat(),
            columnEventClass: getColumnEventClass,
            columnEventInnerClass: "roomz-calendar-event-inner",
            backgroundEventClass: "roomz-calendar-bg-event",
            slotLaneClass: getSlotLaneClass,
            slotHeaderClass: (headerInfo: { time?: { milliseconds: number } }) =>
                getSlotHeaderClass(isFirst, headerInfo),
            dayLaneClass: getDayLaneClass,
            nonBusinessHoursClass: "roomz-calendar-non-business",
        };
    }

    return {
        rooms,
        happenings,
        date,
        validRange,
        isLoadingRooms,
        currentPage,
        nextPage,
        previousPage,
        roomInfoTitle,
        loadRooms,
        loadHappenings,
        openRoomInfo,
        columnOptions,
    };
}

import interactionPlugin from "@fullcalendar/interaction";
import type { ResourceFuncArg, ResourceInput } from "@fullcalendar/resource";
import resourceTimeGridPlugin from "@fullcalendar/resource-timegrid";

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
import { withBaseUrl } from "@/baseUrl";

import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import utc from "dayjs/plugin/utc";
import { trans } from "laravel-vue-i18n";
import { storeToRefs } from "pinia";
import type { Ref } from "vue";
import { reactive, unref } from "vue";

dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);
dayjs.extend(isBetween);
dayjs.extend(utc);

type ResourceResponse = {
    data: {
        pagination: {
            previousPage: string | null;
            nextPage: string | null;
        };
        resources: ResourceInput[];
    };
};

type HappeningResponse = {
    data: Happening[];
};

type Translatable = Record<string, string>;

type ResourceExtendedProps = {
    translations?: {
        title?: Translatable;
        location?: Translatable;
        description?: Translatable;
        resourceGroup?: Translatable;
    };
    location_uri?: string;
    capacity?: number;
    isVerificationRequired?: boolean;
    resourceGroup?: number | string;
    [key: string]: unknown;
};

type CalendarResource = {
    id?: number | string;
    extendedProps: ResourceExtendedProps;
};

type EventExtendedProps = {
    user_01?: string;
    status?: { user?: { verification?: string }; type?: string };
    isVerificationRequired?: boolean;
    can?: { verify?: boolean; edit?: boolean; delete?: boolean };
    label?: Translatable;
    [key: string]: unknown;
};

type CalendarEvent = {
    id?: number | string;
    extendedProps: EventExtendedProps;
    getResources: () => Array<CalendarResource | { _resource: CalendarResource }>;
    _instance: { range: { start: Date; end: Date } };
};

type SelectInfo = {
    resource?: CalendarResource;
    startStr: string;
    endStr: string;
};

type EventClickInfo = {
    el: HTMLElement;
    event: CalendarEvent;
};

type ResourceLabelInfo = {
    resource: CalendarResource;
};

type CalendarApi = {
    refetchEvents: () => void;
};
type CalendarRef = { getApi: () => CalendarApi } | null;

export type CalendarPagination = {
    currentPage?: string | null;
    nextPage?: string | null;
    previousPage?: string | null;
};

type CalendarEmit = {
    (event: "show-status"): void;
    <Props>(event: "open-modal-component", payload: ModalOpenPayload<Props>): void;
};
type CalendarTimeFormat = {
    hour: "numeric";
    minute: "2-digit";
    meridiem: false;
    hour12: false;
};

function noopCalendarEmit(event: "show-status"): void;
function noopCalendarEmit<Props>(event: "open-modal-component", payload: ModalOpenPayload<Props>): void;
function noopCalendarEmit(): void {}

type UseCalendarOptions = Record<string, unknown> & {
    resources: (
        fetchInfo: ResourceFuncArg,
        successCallback: (resources: ResourceInput[]) => void,
        failureCallback: (error: unknown) => void,
    ) => void;
    events: (
        fetchInfo: { start: Date; end: Date },
        successCallback: (events: Happening[]) => void,
        failureCallback: (error: unknown) => void,
    ) => void;
    validRange: {
        start: Date;
        end: Date;
    };
    selectAllow: (selection: SelectInfo) => boolean;
    select: (eventInfo: SelectInfo) => void;
    eventClick: (eventInfo: EventClickInfo) => void;
    datesSet: (dateInfo: { start: Date }) => void;
    resourceLabelContent: (resourceInfo: ResourceLabelInfo) => { domNodes: HTMLElement[] };
    slotLabelFormat: CalendarTimeFormat;
    eventTimeFormat: CalendarTimeFormat;
};

export function useCalendar({
    emit = noopCalendarEmit,
    pagination,
    translate,
    calendarOptions = {},
}: {
    emit?: CalendarEmit;
    pagination: CalendarPagination;
    translate: (value?: Translatable) => string;
    calendarOptions?: Partial<UseCalendarOptions>;
}) {
    const appStore = useAppStore();
    const institution = appStore.institution;
    const resourceGroup = appStore.resourceGroup;
    const resourceGroupSettings = appStore.settings?.resource_group;
    const hiddenDays = appStore.hiddenDays;

    const authStore = useAuthStore();
    const { isAuthenticated } = storeToRefs(authStore);

    function fetchResources(
        _fetchInfo: ResourceFuncArg,
        successCallback: (resources: ResourceInput[]) => void,
        failureCallback: (error: unknown) => void,
    ) {
        if (!pagination.currentPage) {
            return;
        }

        axios({
            method: "GET",
            url: pagination.currentPage,
        })
            .then((response: ResourceResponse) => {
                pagination.previousPage = response.data.pagination.previousPage;
                pagination.nextPage = response.data.pagination.nextPage;

                successCallback(response.data.resources);
            })
            .catch((error: unknown) => {
                failureCallback(error);
            });
    }

    function fetchHappenings(
        fetchInfo: { start: Date; end: Date },
        successCallback: (events: Happening[]) => void,
        failureCallback: (error: unknown) => void,
    ) {
        const payload = {
            start: fetchInfo.start,
            end: fetchInfo.end,
        };

        axios({
            method: "GET",
            url: withBaseUrl(`/${institution?.slug}/${resourceGroup?.slug}/happenings`),
            params: payload,
        })
            .then((response: HappeningResponse) => {
                successCallback(response.data);
            })
            .catch((error: unknown) => {
                failureCallback(error);
            });
    }

    function refetchHappenings(calendar: CalendarRef | Ref<CalendarRef>) {
        const api = unref(calendar)?.getApi();
        api?.refetchEvents();
    }

    function getValidRange() {
        const weeksInAdvance = resourceGroupSettings?.["weeks_in_advance"];

        const startDate = dayjs();
        const endDate = startDate.add(Number(weeksInAdvance), "week");

        return {
            start: startDate.toDate(),
            end: endDate.toDate(),
        };
    }

    const canSelect = (event: SelectInfo) => {
        if (!event.resource) {
            return false;
        }

        if (authStore.isAuthenticated && !authStore.isAllowedForResource(event.resource.extendedProps)) {
            return false;
        }

        const tsStart = dayjs(event.startStr);
        const tsEnd = dayjs(event.endStr);

        if (authStore.isAuthenticated && authStore.isExceedingQuotas(tsStart, tsEnd)) {
            return false;
        }

        const tsLenConfig = (resourceGroupSettings?.["time_slot_length"] ?? "00:00").split(":");
        const tsLen = {
            hours: parseInt(tsLenConfig[0] ?? "0"),
            minutes: parseInt(tsLenConfig[1] ?? "0"),
        };

        const now = dayjs.utc();
        const isNotPast = tsStart.isSameOrAfter(now);
        const isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);

        const isValid = tsStart.add(tsLen.hours, "hours").add(tsLen.minutes, "minutes").isAfter(now);

        return isValid && (isNotPast || isCurrentTimeSlot);
    };

    function onSelect(eventInfo: SelectInfo) {
        const resource = eventInfo.resource;
        if (!resource) {
            return;
        }

        const happeningModalCallback = () => {
            const happening: Happening = reactive({
                isSelected: true,
                resource: {
                    id: resource.id,
                    title: translate(resource.extendedProps.translations?.title),
                    location: translate(resource.extendedProps.translations?.location),
                    location_uri: resource.extendedProps.location_uri,
                    capacity: resource.extendedProps.capacity,
                    description: translate(resource.extendedProps.translations?.description),
                    resourceGroup: translate(resource.extendedProps.translations?.resourceGroup),
                },
                start: eventInfo.startStr,
                end: eventInfo.endStr,
                isVerificationRequired: resource.extendedProps.isVerificationRequired,
            });

            emit("open-modal-component", useHappeningCreateModal(happening));
        };

        if (!isAuthenticated.value) {
            emit("open-modal-component", useLoginModal(happeningModalCallback));
        } else {
            happeningModalCallback();
        }
    }

    function onEventClick(eventInfo: EventClickInfo) {
        const isBgEvent = eventInfo.el.classList.contains("fc-bg-event");

        const firstResource = eventInfo.event.getResources()[0];
        const resource = firstResource && "_resource" in firstResource ? firstResource._resource : firstResource;

        if (!resource) {
            return;
        }

        const happening: Happening = {
            resource: {
                id: resource.id,
                title: translate(resource.extendedProps.translations?.title),
                location: translate(resource.extendedProps.translations?.location),
                location_uri: resource.extendedProps.location_uri,
                capacity: resource.extendedProps.capacity,
                description: translate(resource.extendedProps.translations?.description),
                resourceGroup: translate(resource.extendedProps.translations?.resourceGroup),
            },
            id: eventInfo.event.id,
            user_01: eventInfo.event.extendedProps.user_01,
            user_02: eventInfo.event.extendedProps.status?.user?.verification,
            start: dayjs.utc(eventInfo.event._instance.range.start),
            end: dayjs.utc(eventInfo.event._instance.range.end),
            isVerificationRequired: eventInfo.event.extendedProps.isVerificationRequired,
            can: eventInfo.event.extendedProps.can,
            label: eventInfo.event.extendedProps.label,
        };

        if (!isBgEvent) {
            if (happening.can?.verify) {
                emit("open-modal-component", useHappeningVerifyModal(happening));
            } else if (happening.can?.edit) {
                emit("open-modal-component", useHappeningEditModal(happening));
            } else {
                emit("open-modal-component", useHappeningInfoModal(happening));
            }
        }
    }

    function onDatesSet(dateInfo: { start: Date }) {
        authStore.updateQuotas(dateInfo.start);
    }

    function getResourceInfoLabel(resourceInfo: ResourceLabelInfo) {
        const link = document.createElement("a");
        link.href = "#";
        link.classList.add("ml-1");

        if (appStore.locale === "de") {
            link.title = trans("calendar.resource_info.de");
        } else {
            link.title = trans("calendar.resource_info.en");
        }

        link.innerHTML = '<i class="ri-information-line"></i>';
        link.onclick = function () {
            emit(
                "open-modal-component",
                useResourceInfoModal({
                    resourceGroup: translate(resourceInfo.resource.extendedProps.translations?.resourceGroup),
                    title: translate(resourceInfo.resource.extendedProps.translations?.title),
                    description: translate(resourceInfo.resource.extendedProps.translations?.description),
                    location: translate(resourceInfo.resource.extendedProps.translations?.location),
                    location_uri: resourceInfo.resource.extendedProps.location_uri,
                    capacity: resourceInfo.resource.extendedProps.capacity,
                }),
            );
        };

        const title = document.createElement("span");
        title.textContent = translate(resourceInfo.resource.extendedProps.translations?.title) ?? "";

        return { domNodes: [title, link] };
    }

    function getHiddenDays() {
        return hiddenDays;
    }

    function getTimeFormat() {
        return {
            hour: "numeric" as const,
            minute: "2-digit" as const,
            meridiem: false as const,
            hour12: false as const,
        };
    }

    const defaultCalendarOptions: UseCalendarOptions = {
        schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
        plugins: [interactionPlugin, resourceTimeGridPlugin],
        initialView: "resourceTimeGridDay",
        headerToolbar: false,
        locale: appStore.locale,
        timeZone: "utc",
        validRange: getValidRange(),
        resources: fetchResources,
        events: fetchHappenings,
        slotMinTime: resourceGroupSettings?.["start_time_slot"],
        slotMaxTime: resourceGroupSettings?.["end_time_slot"],
        resourceOrder: "order",
        height: "auto",
        contentHeight: "auto",
        stickyHeaderDates: true,
        weekends: true,
        hiddenDays: getHiddenDays(),
        editable: false,
        nowIndicator: true,
        allDaySlot: false,
        longPressDelay: import.meta.env.VITE_LONG_PRESS_DELAY ?? 500,
        unselectAuto: true,
        selectMirror: true,
        slotDuration: resourceGroupSettings?.["time_slot_length"] + ":00",
        slotLabelInterval: resourceGroupSettings?.["time_slot_length"] + ":00",
        selectOverlap: false,
        selectConstraint: "businessHours",
        selectable: true,
        selectAllow: (selection: SelectInfo) => canSelect(selection),
        select: onSelect,
        eventClick: onEventClick,
        datesSet: onDatesSet,
        resourceLabelContent: getResourceInfoLabel,
        slotLabelFormat: getTimeFormat(),
        eventTimeFormat: getTimeFormat(),
    };

    return {
        calendarOptions: {
            ...defaultCalendarOptions,
            ...calendarOptions,
        } as UseCalendarOptions,
        refetchHappenings,
    };
}

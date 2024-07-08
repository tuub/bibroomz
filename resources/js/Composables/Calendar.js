import interactionPlugin from "@fullcalendar/interaction";
import resourceTimeGridPlugin from "@fullcalendar/resource-timegrid";

import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import {
    useHappeningCreateModal,
    useHappeningEditModal,
    useHappeningInfoModal,
    useHappeningVerifyModal,
    useLoginModal,
    useResourceInfoModal,
} from "./ModalActions";

import dayjs from "dayjs";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import utc from "dayjs/plugin/utc";
import { trans } from "laravel-vue-i18n";
import { storeToRefs } from "pinia";
import { reactive, unref } from "vue";

dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);
dayjs.extend(isBetween);
dayjs.extend(utc);

export function useCalendar({ emit, pagination, translate, calendarOptions = {} }) {
    const appStore = useAppStore();
    const institution = appStore.institution;
    const resourceGroup = appStore.resourceGroup;
    const resourceGroupSettings = appStore.settings["resource_group"];
    const hiddenDays = appStore.hiddenDays;

    const authStore = useAuthStore();
    const { isAuthenticated } = storeToRefs(authStore);

    const baseUrl = import.meta.env.VITE_API_URL;

    function fetchResources(fetchInfo, successCallback, failureCallback) {
        if (!pagination.currentPage) {
            return;
        }

        axios({
            method: "GET",
            url: pagination.currentPage,
        })
            .then((response) => {
                pagination.previousPage = response.data.pagination.previousPage;
                pagination.nextPage = response.data.pagination.nextPage;

                successCallback(response.data.resources);
            })
            .catch((error) => {
                failureCallback(error);
            });
    }

    function fetchHappenings(fetchInfo, successCallback, failureCallback) {
        const payload = {
            start: fetchInfo.start,
            end: fetchInfo.end,
        };

        axios({
            method: "GET",
            url: baseUrl + "/" + institution.slug + "/" + resourceGroup.slug + "/happenings",
            params: payload,
        })
            .then((response) => {
                successCallback(response.data);
            })
            .catch((error) => {
                failureCallback(error);
            });
    }

    function refetchHappenings(calendar) {
        const api = unref(calendar)?.getApi();
        api.refetchEvents();
    }

    function getValidRange() {
        const weeksInAdvance = resourceGroupSettings["weeks_in_advance"];

        const startDate = dayjs();
        const endDate = startDate.add(weeksInAdvance, "week");

        return {
            start: startDate.toDate(),
            end: endDate.toDate(),
        };
    }

    function isSelectable() {
        return true;
    }

    function isSelectAllow(event) {
        const now = dayjs.utc();
        const tsStart = dayjs(event.startStr);
        const tsEnd = dayjs(event.endStr);

        const tsLenConfig = resourceGroupSettings["time_slot_length"].split(":");
        const tsLen = {
            hours: parseInt(tsLenConfig[0]),
            minutes: parseInt(tsLenConfig[1]),
        };

        const isNotPast = tsStart.isSameOrAfter(now);
        const isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);

        const isValid = tsStart.add(tsLen.hours, "hours").add(tsLen.minutes, "minutes").isAfter(now);

        if (authStore.isAuthenticated && authStore.isExceedingQuotas(tsStart, tsEnd)) {
            return false;
        }

        return isValid && (isNotPast || isCurrentTimeSlot);
    }

    function onSelect(eventInfo) {
        const happeningModalCallback = () => {
            const happening = reactive({
                isSelected: true,
                resource: {
                    id: eventInfo.resource.id,
                    title: translate(eventInfo.resource.extendedProps.translations.title),
                    location: translate(eventInfo.resource.extendedProps.translations.location),
                    location_uri: eventInfo.resource.extendedProps.location_uri,
                    capacity: eventInfo.resource.extendedProps.capacity,
                    description: translate(eventInfo.resource.extendedProps.translations.description),
                    resourceGroup: translate(eventInfo.resource.extendedProps.translations.resourceGroup),
                },
                start: eventInfo.startStr,
                end: eventInfo.endStr,
                isVerificationRequired: eventInfo.resource.extendedProps.isVerificationRequired,
            });

            emit("open-modal-component", useHappeningCreateModal(happening));
        };

        if (!isAuthenticated.value) {
            emit("open-modal-component", useLoginModal(happeningModalCallback));
        } else {
            happeningModalCallback();
        }
    }

    function onEventClick(eventInfo) {
        const happening = {};
        const isBgEvent = eventInfo.el.classList.contains("fc-bg-event");

        const resource = eventInfo.event.getResources()[0]._resource;

        happening.resource = {
            id: resource.id,
            title: translate(resource.extendedProps.translations.title),
            location: translate(resource.extendedProps.translations.location),
            location_uri: resource.extendedProps.location_uri,
            capacity: resource.extendedProps.capacity,
            description: translate(resource.extendedProps.translations.description),
            resourceGroup: translate(resource.extendedProps.translations.resourceGroup),
        };

        happening.id = eventInfo.event.id;
        happening.user_02 = eventInfo.event.extendedProps.status?.user?.verification;
        happening.start = dayjs.utc(eventInfo.event._instance.range.start);
        happening.end = dayjs.utc(eventInfo.event._instance.range.end);
        happening.isVerificationRequired = eventInfo.event.extendedProps.isVerificationRequired;
        happening.can = eventInfo.event.extendedProps.can;
        happening.label = eventInfo.event.extendedProps.label;

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

    function onDatesSet(dateInfo) {
        authStore.updateQuotas(dateInfo.start);
    }

    function getResourceInfoLabel(resourceInfo) {
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
                    resourceGroup: translate(resourceInfo.resource.extendedProps.translations.resourceGroup),
                    title: translate(resourceInfo.resource.extendedProps.translations.title),
                    description: translate(resourceInfo.resource.extendedProps.translations.description),
                    location: translate(resourceInfo.resource.extendedProps.translations.location),
                    location_uri: resourceInfo.resource.extendedProps.location_uri,
                    capacity: resourceInfo.resource.extendedProps.capacity,
                }),
            );
        };

        const title = document.createElement("span");
        title.innerHTML = translate(resourceInfo.resource.extendedProps.translations.title);

        return { domNodes: [title, link] };
    }

    function getHiddenDays() {
        return hiddenDays;
    }

    function getTimeFormat() {
        return {
            hour: "numeric",
            minute: "2-digit",
            meridiem: false,
            hour12: false,
        };
    }

    const defaultCalendarOptions = {
        schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
        plugins: [interactionPlugin, resourceTimeGridPlugin],
        initialView: "resourceTimeGridDay",
        headerToolbar: {
            left: "title",
            center: "",
            right: "",
        },
        titleFormat: {
            month: "2-digit",
            year: "numeric",
            day: "2-digit",
            weekday: "long",
        },
        locale: appStore.locale,
        timeZone: "utc",
        validRange: getValidRange(),
        resources: fetchResources,
        events: fetchHappenings,
        slotMinTime: resourceGroupSettings["start_time_slot"],
        slotMaxTime: resourceGroupSettings["end_time_slot"],
        resourceOrder: "title",
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
        slotDuration: resourceGroupSettings["time_slot_length"] + ":00",
        slotLabelInterval: resourceGroupSettings["time_slot_length"] + ":00",
        selectOverlap: false,
        selectConstraint: "businessHours",
        selectable: isSelectable,
        selectAllow: isSelectAllow,
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
        },
        refetchHappenings,
    };
}

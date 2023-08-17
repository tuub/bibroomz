import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import isBetween from "dayjs/plugin/isBetween";
import isSameOrAfter from "dayjs/plugin/isSameOrAfter";
import isSameOrBefore from "dayjs/plugin/isSameOrBefore";
import interactionPlugin from "@fullcalendar/interaction";
import resourceTimeGridPlugin from "@fullcalendar/resource-timegrid";

import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { storeToRefs } from "pinia";
import { useToast } from "vue-toastification";
import { reactive, unref } from "vue";
import {useCreateModal, useInfoModal, useResourceInfoModal, useVerifyModal} from "./ModalActions";
import { useEditDeleteModal } from "./ModalActions";

dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);
dayjs.extend(isBetween);
dayjs.extend(utc);

export function useCalendar({ emit, calendarOptions = {} }) {
    const appStore = useAppStore();
    const institution = appStore.institution;

    const authStore = useAuthStore();
    const { isAuthenticated } = storeToRefs(authStore);

    function fetchResources() {
        return "/" + institution.slug + "/resources";
    }

    function fetchHappenings(fetchInfo, successCallback, failureCallback) {
        const appStore = useAppStore();
        const institution = appStore.institution;

        const payload = {
            start: fetchInfo.start,
            end: fetchInfo.end,
        };

        // isLoading = true;

        axios({
            method: "GET",
            url: "/" + institution.slug + "/happenings",
            params: payload,
        })
            .then((response) => {
                successCallback(response.data);
                // isLoading.value = false;
            })
            .catch((error) => {
                failureCallback(error);
                // isLoading.value = false;
            });
    }

    function refetchHappenings(calendar) {
        const api = unref(calendar).getApi();
        api.refetchEvents();
    }

    function getValidRange() {
        const weeksInAdvance = institution.settings["weeks_in_advance"];

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

        const tsLenConfig = institution.settings["time_slot_length"].split(":");
        const tsLen = {
            hours: parseInt(tsLenConfig[0]),
            minutes: parseInt(tsLenConfig[1]),
        };

        const isNotPast = tsStart.isSameOrAfter(now);
        const isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);

        const isValid = tsStart
            .add(tsLen.hours, "hours")
            .add(tsLen.minutes, "minutes")
            .isAfter(now);

        if (
            authStore.isAuthenticated &&
            authStore.isExceedingQuotas(tsStart, tsEnd)
        ) {
            return false;
        }

        return isValid && (isNotPast || isCurrentTimeSlot);
    }

    function onSelect(eventInfo) {
        if (!isAuthenticated.value) {
            useToast().error("Must be authenticated!");
        } else {
            const happeningData = reactive({
                isSelected: true,
                resource: {
                    id: eventInfo.resource.id,
                    title: eventInfo.resource.title,
                },
                start: eventInfo.startStr,
                end: eventInfo.endStr,
                isVerificationRequired:
                    eventInfo.resource.extendedProps.isVerificationRequired,
            });

            emit("open-modal-component", useCreateModal(happeningData));
        }
    }

    function onEventClick(eventInfo) {
        const happeningData = {};
        const isBgEvent = eventInfo.el.classList.contains("fc-bg-event");

        if (eventInfo.resource) {
            /* This is a new selection */
            const dataPath = eventInfo;

            happeningData.resource = {
                id: dataPath.resource._resource.id,
                title: dataPath.resource._resource.title,
            };
        } else {
            /* This is an event */
            const dataPath = eventInfo.event;

            happeningData.resource = {
                id: dataPath.getResources()[0]._resource.id,
                title: dataPath.getResources()[0]._resource.title,
            };
            happeningData.id = dataPath.id;
            happeningData.user_02 =
                dataPath.extendedProps.status?.user?.verification;
            happeningData.start = dayjs.utc(dataPath._instance.range.start);
            happeningData.end = dayjs.utc(dataPath._instance.range.end);
            happeningData.isVerificationRequired =
                dataPath.extendedProps.isVerificationRequired;
        }

        if (!isBgEvent) {
            const can = eventInfo.event.extendedProps.can;

            if (can.edit && can.delete) {
                emit("open-modal-component", useEditDeleteModal(happeningData));
            } else if (can.verify) {
                emit("open-modal-component", useVerifyModal(happeningData));
            } else {
                emit("open-modal-component", useInfoModal(happeningData));
            }
        }
    }

    function onDatesSet(dateInfo) {
        authStore.updateQuotas(dateInfo.start);
    }

    function getResourceInfo(resourceInfo) {
        let link = document.createElement('a');
        link.href = '#';
        link.innerHTML = '<i class="ri-information-line"></i>';
        link.onclick = function () {
            emit("open-modal-component", useResourceInfoModal(resourceInfo));
        };

        let title = document.createElement('div');
        title.innerHTML = resourceInfo.resource.title;



        let arrayOfDomNodes = [ title, link ]
        return { domNodes: arrayOfDomNodes }
        //info.el.querySelector('.fc-cell-text').appendChild(z);
    }

    const defaultCalendarOptions = {
        schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
        plugins: [interactionPlugin, resourceTimeGridPlugin],
        initialView: "resourceTimeGridDay",
        headerToolbar: {
            left: "title",
            center: "",
            right: "today,prev,next",
        },
        buttonText: {
            today: dayjs().format("DD.MM.YYYY"),
        },
        titleFormat: {
            month: "2-digit",
            year: "numeric",
            day: "2-digit",
            weekday: "long",
        },
        locale: "de",
        timeZone: "utc",
        validRange: getValidRange(),
        resources: fetchResources(),
        events: fetchHappenings,
        slotMinTime: institution.settings["start_time_slot"],
        slotMaxTime: institution.settings["end_time_slot"],
        resourceOrder: "title",
        height: "auto",
        contentHeight: "auto",
        stickyHeaderDates: true,
        weekends: true,
        editable: false,
        nowIndicator: true,
        allDaySlot: false,
        longPressDelay: 0,
        unselectAuto: true,
        selectMirror: true,
        slotDuration: institution.settings["time_slot_length"] + ":00",
        slotLabelInterval: institution.settings["time_slot_length"] + ":00",
        selectOverlap: false,
        selectConstraint: "businessHours",
        selectable: isSelectable,
        selectAllow: isSelectAllow,
        select: onSelect,
        eventClick: onEventClick,
        datesSet: onDatesSet,
        resourceLabelContent: getResourceInfo,
    };

    return {
        calendarOptions: {
            ...defaultCalendarOptions,
            ...calendarOptions,
        },
        refetchHappenings,
    };
}

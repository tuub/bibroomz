<template>
    <div class='calendar'>
        <FullCalendar ref="refCalendar" class='calendar' :options="calendarOptions">
            <template v-slot:eventContent='arg'>
                <div class="text-center">
                    <b>{{ arg.timeText }}</b>
                    <i>{{ arg.event.title }}</i>
                </div>
            </template>
        </FullCalendar>
    </div>
</template>

<script setup>
import '@fullcalendar/core/vdom'
import FullCalendar from '@fullcalendar/vue3';
import interactionPlugin from '@fullcalendar/interaction';
import resourceTimeGridPlugin from '@fullcalendar/resource-timegrid';
import dayjs from 'dayjs';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isBetween from 'dayjs/plugin/isBetween';
import { inject, onMounted, reactive, ref, watch, watchEffect } from "vue";
import {useHappeningStore} from "../Stores/HappeningStore";
import {useAuthStore} from "../Stores/AuthStore";

import HappeningModal from "@/Components/Modals/HappeningModal.vue";
import useModal from "../Stores/Modal.ts";
import {storeToRefs} from "pinia";
import utc from "dayjs/plugin/utc";

// ------------------------------------------------
// Debug information
// ------------------------------------------------

//console.log(props)
//console.log(context)

// ------------------------------------------------
// Options (should come from external file or database)
// ------------------------------------------------
let options = {
    timeSlotLength: '00:30:00',
    blockHourQuota: 4,
    futureDays: 14,
}

// ------------------------------------------------
// Loading indicator
// ------------------------------------------------
let isLoading = false;
watchEffect(() => {
    console.log('isLoading: %s', isLoading)
})

// ------------------------------------------------
// Event Bus
// ------------------------------------------------
const emitter = inject('emitter');

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits([
    'show-status',
    'open-modal-component'
    ])

// ------------------------------------------------
// Stores
// ------------------------------------------------
let happeningStore = useHappeningStore()
let authStore = useAuthStore()

let { isAuthenticated } = storeToRefs(authStore)

// ------------------------------------------------
// Modal
// ------------------------------------------------
const modal = useModal();

// ------------------------------------------------
// CALENDAR METHODS
// ------------------------------------------------

// ------------------------------------------------
// refCalendar
// ------------------------------------------------
const refCalendar = ref(null)

// ------------------------------------------------
// Init calendarApi
// ------------------------------------------------
let calendarApi = null

// ------------------------------------------------
// Get calendar API instance and event bus
// ------------------------------------------------
onMounted(() => {
    // Init Calendar API
    calendarApi = refCalendar.value.getApi()

    Echo.channel("happenings").listen("HappeningsChanged", () => {
        refetchHappenings();
    });
})

// ------------------------------------------------
// DayJS plugins
// ------------------------------------------------
dayjs.extend(isSameOrAfter)
dayjs.extend(isSameOrBefore)
dayjs.extend(isBetween)
dayjs.extend(utc)

// ------------------------------------------------
// Fetch resources from backend
// ------------------------------------------------
const getResources = () => {
    return '/resources';
}

// ------------------------------------------------
// Calculate valid start and end date
// ------------------------------------------------
const getValidRange = () => {
    const startDate = new Date()
    const endDate = new Date().setDate(
        startDate.getDate() + options.futureDays
    );

    return {
        start: startDate,
        end: endDate
    };
};

// ------------------------------------------------
// Fetch business hours from backend
// ------------------------------------------------
const getBusinessHours = () => {
    let result = [];
    axios.get('/business_hours').then((response) => {
        let data = response.data;
        for (let weekDay in data) {
            if (data.hasOwnProperty(weekDay)) {
                result.push({
                    daysOfWeek: [weekDay],
                    startTime: data[weekDay].start,
                    endTime: data[weekDay].end,
                    className: 'closed',
                    rendering: 'inverse-background',
                });
            }
        }
    }).catch((error) => {
        console.log(error);
    });

    return result;
}

// ------------------------------------------------
// Fetch happenings from backend
// ------------------------------------------------
const getHappenings = (fetchInfo, successCallback, failureCallback) => {
    let payload = {
        start: fetchInfo.start,
        end: fetchInfo.end
    };

    isLoading = true;

    axios({ method: 'GET', url: '/happenings', params: payload})
        .then((response) => {
            successCallback(response.data);
            isLoading = false
        }).catch((error) => {
        failureCallback(error);
    });
}

const refetchHappenings = () => {
    calendarApi.refetchEvents()
}

// Refetch happenings if store state of isAuthenticated changes => after login / logout
watch(isAuthenticated, (value) => {
    refetchHappenings()
})

// ------------------------------------------------
// Selection constraints
// ------------------------------------------------
const isSelectable = () => {
    return true;
}

const isSelectAllow = (event) => {
    let now = dayjs.utc()
    let tsStart = dayjs(event.startStr)
    let tsEnd = dayjs(event.endStr)

    let tsLenConfig = options.timeSlotLength.split(':')
    let tsLen = {
        hours: parseInt(tsLenConfig[0]),
        minutes: parseInt((tsLenConfig[1]))
    }

    let isNotPast = tsStart.isSameOrAfter(now);
    let isValid = tsStart.add(tsLen.minutes, 'minutes').isAfter(now) &&
        tsEnd.isSameOrBefore(
            tsStart.add(parseInt(options.blockHourQuota), 'hours')
        );
    let isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);

    return isValid && (isNotPast || isCurrentTimeSlot);
}

// ------------------------------------------------
// Select action
// ------------------------------------------------
const onSelect = (eventInfo) => {
    if (!authStore.isAuthenticated) {
        emit('show-status', 'WHAT DO YOU WANT, ALIEN!?')
    } else {
        let happeningData = reactive({
            isSelected: true,
            resource: eventInfo.resource,
            start: eventInfo.startStr,
            end: eventInfo.endStr,
            // confirmer: '',
        });

        emit('open-modal-component', {
            view: HappeningModal,
            content: {
                title: 'Create Reservation',
                description: "Create your reservation here, you won't regret it."
            },
            payload: {...happeningData, editable: true},
            actions: [
                {
                    label: 'Save reservation',
                    callback: (payloadFromView) => {
                        return happeningStore.addHappening(payloadFromView);
                    },
                }
            ],
        })
    }
}

const onEventClick = (eventInfo) => {
    let happeningData = {
        resource: '',
        happening_id: '',
        extraData: '',
    }

    if (eventInfo.resource) {
        /* This is a new selection */
        let dataPath = eventInfo;
        happeningData.resource = {
            id: dataPath.resource._resource.id,
            title: dataPath.resource._resource.title,
        }
    } else {
        /* This is an event */
        let dataPath = eventInfo.event;
        happeningData.resource = {
            id: dataPath.getResources()[0]._resource.id,
            title: dataPath.getResources()[0]._resource.title,
        };
        happeningData.happening_id = dataPath.id;
        happeningData.extraData = dataPath.extendedProps;
        happeningData.start = dayjs.utc(dataPath._instance.range.start);
        happeningData.end = dayjs.utc(dataPath._instance.range.end);
    }

    emit('open-modal-component', {
        view: HappeningModal,
        content: {
            title: 'Show Reservation',
            description: 'Info about reservation here.'
        },
        payload: {...happeningData, editable: false},
        actions: [
            {
                label: 'OK',
                callback: () => {
                    modal.close();
                },
            },
        ],
    });
};

// ------------------------------------------------
// Calendar setup
// ------------------------------------------------
const calendarOptions = {
    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
    plugins: [interactionPlugin, resourceTimeGridPlugin],
    initialView: 'resourceTimeGridDay',
    headerToolbar: {
        left: 'title',
        center: '',
        right: 'today,prev,next'
    },
    buttonText: {
        today:    'Heute',
        month:    'Monat',
        week:     'Woche',
        day:      'Tag',
    },
    titleFormat: {
        month: '2-digit',
        year: 'numeric',
        day: '2-digit',
        weekday: 'long',
    },
    locale: 'de',
    timeZone: 'utc',
    validRange: getValidRange(),
    resources: getResources(),
    businessHours: getBusinessHours(),
    events: getHappenings,
    slotMinTime: '09:00',
    slotMaxTime: '24:00',
    resourceOrder: 'title',
    height: 'auto',
    contentHeight: 'auto',
    stickyHeaderDates: true,
    weekends: true,
    editable: false,
    nowIndicator: true,
    allDaySlot: false,
    longPressDelay: 0,
    unselectAuto: true,
    selectMirror: true,
    slotDuration: '00:30:00',
    slotLabelInterval: '00:30:00',
    selectOverlap: false,
    selectable: isSelectable,
    selectAllow: isSelectAllow,
    select: onSelect,
    eventClick: onEventClick,
}
</script>

<style lang='css'>
/* FIXME: Alias ~ in Vite! */
@import '/node_modules/@fullcalendar/daygrid/main.css';
@import '/node_modules/@fullcalendar/timegrid/main.css';

/* Firefox fix for now-indicator */
.fc .fc-timegrid-now-indicator-container {
    overflow: visible;
}

a.fc-event, a.fc-event:hover {
    cursor: pointer;
}
</style>

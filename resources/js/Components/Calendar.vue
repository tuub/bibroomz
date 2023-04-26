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

        <!--
        <AddReservationModal
            :showModal="showAddModal"
            @hideModal="showAddModal = false"
            @refetch-events="refetchEvents"
        >
        </AddReservationModal>
        -->

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
import {inject, onMounted, reactive, ref, watch} from "vue";
//import AddReservationModal from "./Modals/AddReservationModal.vue";
import {useReservationStore} from "../Stores/ReservationStore";
import {useAuthStore} from "../Stores/AuthStore";

import AddReservation from "./Modals/AddReservation.vue";
import ShowReservation from "./Modals/ShowReservation.vue";
import useModal from "../Stores/Modal.ts";
import {storeToRefs} from "pinia";

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
let reservationStore = useReservationStore()
let authStore = useAuthStore()

let { isAuthenticated } = storeToRefs(authStore)
// ------------------------------------------------
// FIXME: Modal
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
    // Get Calendar API
    calendarApi = refCalendar.value.getApi()
    // Listen to emitter events
    emitter.on('reservation-added', (event) => {
        calendarApi.refetchEvents()
        console.log('Refetched events.');
    })
})

// ------------------------------------------------
// DayJS plugins
// ------------------------------------------------
dayjs.extend(isSameOrAfter)
dayjs.extend(isSameOrBefore)
dayjs.extend(isBetween)

// ------------------------------------------------
// Fetch resources from backend
// ------------------------------------------------
const getResources = () => {
    return '/api/resources';
}

// ------------------------------------------------
// Calculate valid start and end date
// ------------------------------------------------
const getValidRange = () => {
    const startDate = new Date()
    const endDate = new Date().setDate(startDate.getDate() + options.futureDays)

    return {
        start: startDate,
        end: endDate
    }
}

// ------------------------------------------------
// Fetch business hours from backend
// ------------------------------------------------
const getBusinessHours = () => {
    let result = [];
    axios.get('/api/business_hours').then((response) => {
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
    });

    return result;
}

// ------------------------------------------------
// Fetch events from backend
// ------------------------------------------------
const getEvents = (fetchInfo, successCallback, failureCallback) => {
    let payload = {
        start: fetchInfo.start,
        end: fetchInfo.end
    };
    // show loading indicator
    isLoading = true;

    axios({ method: 'GET', url: '/api/reservations', params: payload})
        .then((response) => {
            successCallback(response.data);
            // hide loading indicator
            isLoading = false
        });
}

const refetchEvents = () => {
    calendarApi.refetchEvents()
}

// Refetch events if store state of isAuthenticated changes => after login / logout
watch(isAuthenticated, (value) => {
    console.log('Auth change: Refetching calendar events ' + value)
    refetchEvents()
})

// Refetch events if store state of doCalendarRefetch changes => after modal action
watch(
    () => reservationStore.doRefreshCalendar,
    () => {
        console.log('Refetching events after modal action')
        modal.close()
        refetchEvents()
        reservationStore.doRefreshCalendar = false
    },
)

// ------------------------------------------------
// Selection constraints
// ------------------------------------------------
const isSelectable = () => {
    return true;
}

const isSelectAllow = (event) => {
    let now = dayjs()
    let tsStart = dayjs(event.start.getTime())
    let tsEnd = dayjs(event.end.getTime())
    let tsLenConfig = options.timeSlotLength.split(':')
    let tsLen = {
        'hours': parseInt(tsLenConfig[0]),
        'minutes': parseInt((tsLenConfig[1]))
    }

    let isNotPast = tsStart.isSameOrAfter(now)
    let isValid = tsStart.add(tsLen.minutes, 'minutes').isAfter(now) &&
        tsEnd.isSameOrBefore(tsStart.add(parseInt(options.blockHourQuota), 'hours'))
    let isCurrentTimeSlot = now.isBetween(tsStart, tsEnd)

    return isValid && (isNotPast || isCurrentTimeSlot);
}

// ------------------------------------------------
// Select action
// ------------------------------------------------
const onSelect = (eventInfo) => {
    console.log(eventInfo.resource)
    if (!authStore.isAuthenticated) {
        emit('show-status', 'WHAT DO YOU WANT, ALIEN!?')
    } else {
        let reservationData = reactive({
            isSelected: true,
            resource: eventInfo.resource,
            start: eventInfo.startStr,
            end: eventInfo.endStr,
            confirmer: '',
        });

        emit('open-modal-component', {
            view: AddReservation,
            content: {
                title: 'Add Reservation',
                description: "Add your reservation here, you won't regret it."
            },
            payload: reservationData,
            actions: [
                {
                    label: 'Save reservation',
                    callback: (payloadFromView) => {
                        if (reservationStore.addReservation(payloadFromView)) {
                            authStore.fetchUserEvents()
                            modal.close();
                        }
                    },
                }
            ],
        })
    }
}

const onEventClick = (eventInfo) => {
    let reservationData = {
        resource: '',
        reservation_id: '',
        extraData: '',
    }

    if (eventInfo.resource) {
        /* This is a new selection */
        let dataPath = eventInfo;
        reservationData.resource = {
            id: dataPath.resource._resource.id,
            title: dataPath.resource._resource.title,
        }
    } else {
        /* This is an event */
        let dataPath = eventInfo.event;
        reservationData.resource = {
            id: dataPath.getResources()[0]._resource.id,
            title: dataPath.getResources()[0]._resource.title,
        };
        reservationData.reservation_id = dataPath.id;
        reservationData.extraData = dataPath.extendedProps;
    }

    emit('open-modal-component', {
        view: ShowReservation,
        content: {
            title: 'Show Reservation',
            description: "Info about reservation here."
        },
        payload: reservationData,
        actions: [
            {
                label: 'OK',
                callback: () => {
                    modal.close();
                },
            }
        ],
    })
}

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
    validRange: getValidRange(),
    resources: getResources(),
    businessHours: getBusinessHours(),
    events: getEvents,
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

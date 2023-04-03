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

        {{ showModal }}

        <AddReservationModal
            :showModal="showModal"
            @hideModal="showModal = false"
            @refetch-events="refetchEvents"
        >
        </AddReservationModal>

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
import {inject, onMounted, reactive, ref} from "vue";
import AddReservationModal from "./Modals/AddReservationModal.vue";
import {useReservationStore} from "../Stores/ReservationStore";

// ------------------------------------------------
// Debug information
// ------------------------------------------------

//console.log(props)
//console.log(context)

let options = {
    timeSlotLength: '00:30:00',
    blockHourQuota: 4,
    futureDays: 14,
}

let isLoading = false;

// ------------------------------------------------
// Event Bus
// ------------------------------------------------
const emitter = inject('emitter');

// ------------------------------------------------
// Reservation
// ------------------------------------------------
let reservationStore = useReservationStore()

/*
const reservationInitState = reactive({
    isSelected: false,
    resource: '',
    start: '',
    end: '',
})

let reservation = reactive({ ...reservationInitState })

const resetReservation = () => {
    console.log('Resetting reservation')
    Object.assign(reservation, reservationInitState);
    console.log(reservation)
}
*/

// ------------------------------------------------
// Modal
// ------------------------------------------------
let showModal = ref(false)

// ------------------------------------------------
// refCalendar
// ------------------------------------------------
const refCalendar = ref(null)

// ------------------------------------------------
// Init calendarApi
// ------------------------------------------------
let calendarApi = null

const refetchEvents = () => {
    console.log('Refetching reservations from API');
    calendarApi.refetchEvents()
}

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

const getEvents = (fetchInfo, successCallback, failureCallback) => {
    let params = {
        start: fetchInfo.start,
        end: fetchInfo.end
    };
    isLoading = true;

    axios({ method: 'GET', url: '/api/reservations', params: params})
        .then((response) => {
            successCallback(response.data);
            isLoading = false
        });
}

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
const onSelect = (selectionInfo) => {
    let resource = selectionInfo.resource
    let eventStart = selectionInfo.startStr
    let eventEnd = selectionInfo.endStr

    let reservationData = {
        isSelected: true,
        resource: resource.id,
        start: eventStart,
        end: eventEnd,
    };
    reservationStore.addCurrentReservation(reservationData)

    console.log('Selected ' + resource.title + ' (' + resource.id + ')');
    console.log('Slot: ' + eventStart + ' - ' + eventEnd);
    showModal.value = true
    console.log(showModal.value)
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
    editable: true,
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
</style>

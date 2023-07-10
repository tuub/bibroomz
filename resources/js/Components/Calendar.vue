<template>
    <div class='calendar'>
        <Legend class="mb-5"></Legend>
        <FullCalendar ref="refCalendar" class='calendar' :options="calendarOptions">
            <template v-slot:eventContent='arg'>
                <div class="text-center">
                    <div v-if="arg.event.display == 'background'"
                         class="border-b-2 pt-5 text-xl">
                        {{ arg.event.extendedProps.description }}
                    </div>
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
import { inject, onMounted, onUnmounted, reactive, ref, watch, watchEffect } from "vue";
import {useAuthStore} from "../Stores/AuthStore";

import {storeToRefs} from "pinia";
import utc from "dayjs/plugin/utc";
import Legend from "./Legend.vue";
import Spinner from "../Shared/Spinner.vue";

import { useToast } from "vue-toastification";

import {
    useConfirmModal,
    useCreateModal,
    useEditDeleteModal,
    useInfoModal,
} from "@/Composables/modalActions";

// ------------------------------------------------
// Debug information
// ------------------------------------------------

//console.log(props)
//console.log(context)

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    settings: Object
})

// ------------------------------------------------
// Config (from backend)
// ------------------------------------------------
let settings = props.settings
let getConfig = (key) => { return settings.find(o => o.key === key).value }

// ------------------------------------------------
// Loading indicator
// ------------------------------------------------
let isLoading = ref(false);

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
let authStore = useAuthStore()
let { isAuthenticated, isAdmin } = storeToRefs(authStore)

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
onUnmounted(() => {
    Echo.leave("happenings");
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
    const startDate = dayjs()
    const endDate = startDate.add(getConfig('weeks_in_advance') * 7, 'day')

    return {
        start: startDate.toDate(),
        end: endDate.toDate()
    };
};

// ------------------------------------------------
// Fetch happenings from backend
// ------------------------------------------------
const getHappenings = (fetchInfo, successCallback, failureCallback) => {
    isLoading.value = true
    let payload = {
        start: fetchInfo.start,
        end: fetchInfo.end
    };

    axios({ method: 'GET', url: '/happenings', params: payload})
        .then((response) => {
            successCallback(response.data);
            isLoading.value = false
        }).catch((error) => {
            failureCallback(error);
            isLoading.value = false
    });
}

const refetchHappenings = () => {
    isLoading.value = true
    calendarApi.refetchEvents()
    isLoading.value = false

}

// Refetch happenings if store state of isAuthenticated changes => after login / logout
watch(isAuthenticated, () => {
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

    let tsLenConfig = getConfig('time_slot_length').split(':')
    let tsLen = {
        hours: parseInt(tsLenConfig[0]),
        minutes: parseInt((tsLenConfig[1]))
    }

    let isNotPast = tsStart.isSameOrAfter(now);
    let isValid = tsStart.add(tsLen.minutes, 'minutes').isAfter(now) &&
        (isAdmin || tsEnd.isSameOrBefore(
            tsStart.add(parseInt(getConfig('quota_happening_block_hours')), 'hours')
        ));
    let isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);

    return isValid && (isNotPast || isCurrentTimeSlot);
}

// ------------------------------------------------
// Select action
// ------------------------------------------------
const onSelect = (eventInfo) => {
    if (!isAuthenticated) {
        useToast().error('Must be authenticated!');
    } else {
        let happeningData = reactive({
            isSelected: true,
            resource: eventInfo.resource,
            start: eventInfo.startStr,
            end: eventInfo.endStr,
            // confirmer: '',
        });

        emit('open-modal-component', useCreateModal(happeningData));
    }
}

const onEventClick = (eventInfo) => {
    let happeningData = {
        id: '',
        resource: '',
        extraData: '',
    }
    let isBgEvent = eventInfo.el.classList.contains('fc-bg-event')

    if (eventInfo.resource) {
        console.log(eventInfo.resource)
        /* This is a new selection */
        let dataPath = eventInfo;
        happeningData.resource = {
            id: dataPath.resource._resource.id,
            title: dataPath.resource._resource.title,
        }
    } else {
        /* This is an event */
        let dataPath = eventInfo.event;
        console.log(dataPath)
        happeningData.resource = {
            id: dataPath.getResources()[0]._resource.id,
            title: dataPath.getResources()[0]._resource.title,
        };
        happeningData.id = dataPath.id;
        happeningData.user_02 = dataPath.extendedProps.status.user.confirmation;
        happeningData.start = dayjs.utc(dataPath._instance.range.start);
        happeningData.end = dayjs.utc(dataPath._instance.range.end);
    }

    if (!isBgEvent) {
        let can = eventInfo.event.extendedProps.can;

        if (can.edit && can.delete) {
            emit('open-modal-component', useEditDeleteModal(happeningData));
        } else if (can.confirm) {
            emit('open-modal-component', useConfirmModal(happeningData));
        } else {
            emit('open-modal-component', useInfoModal(happeningData));
        }
    }
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
    timeZone: 'utc',
    validRange: getValidRange(),
    resources: getResources(),
    events: getHappenings,
    slotMinTime: getConfig('start_time_slot'),
    slotMaxTime: getConfig('end_time_slot'),
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
    slotDuration: getConfig('time_slot_length') + ':00',
    slotLabelInterval: getConfig('time_slot_length') + ':00',
    selectOverlap: false,
    selectConstraint: 'businessHours',
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

/*
.fc-non-business {
    background-color: #BEBEBE !important;
}
*/

div.fc-timegrid-slots tr {
    background-color: #fff;
}
</style>

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
import { inject, onMounted, onUnmounted, reactive, ref, watch } from "vue";

import {useAppStore} from "../Stores/AppStore";
import {useAuthStore} from "../Stores/AuthStore";

import {storeToRefs} from "pinia";
import utc from "dayjs/plugin/utc";
import Legend from "./Legend.vue";
import Spinner from "../Shared/Spinner.vue";

import { useToast } from "vue-toastification";

import {
    useVerifyModal,
    useCreateModal,
    useEditDeleteModal,
    useInfoModal,
} from "@/Composables/ModalActions";

// ------------------------------------------------
// Debug information
// ------------------------------------------------

//console.log(context)

// ------------------------------------------------
// Stores
// ------------------------------------------------
let appStore = useAppStore()
let authStore = useAuthStore()

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(isSameOrAfter)
dayjs.extend(isSameOrBefore)
dayjs.extend(isBetween)
dayjs.extend(utc)

// ------------------------------------------------
// Variables
// ------------------------------------------------
const refCalendar = ref(null)
let calendarApi = null
let isLoading = ref(false);
let { isAuthenticated } = storeToRefs(authStore)

let institution = appStore.institution
let institutionSettings = institution.settings
let institutionSlug = institution.slug

// ------------------------------------------------
// Watchers
// ------------------------------------------------
// Refetch happenings if store state of isAuthenticated changes => after login / logout
watch(isAuthenticated, () => {
    refetchHappenings()
})

watch(() => authStore.userHappenings, () => {
    authStore.updateQuotas(calendarApi.getDate());
}, { deep: true} );

watch(() => appStore.locale, () => {
    calendarApi.setOption('locale', appStore.locale);
});

/*
watchEffect(() => {
    console.log('isLoading: %s', isLoading)
})
*/

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
// Mount
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
// CALENDAR METHODS
// ------------------------------------------------

// Fetch resources from backend
const getResources = () => {
    return '/' + institutionSlug + '/resources';
}

// Calculate valid start and end date
const getValidRange = () => {
    const startDate = dayjs()
    const endDate = startDate.add(institutionSettings['weeks_in_advance'] * 7, 'day')

    return {
        start: startDate.toDate(),
        end: endDate.toDate()
    };
};

// Fetch happenings from backend
const getHappenings = (fetchInfo, successCallback, failureCallback) => {
    isLoading.value = true
    let payload = {
        start: fetchInfo.start,
        end: fetchInfo.end
    };

    axios({ method: 'GET', url: '/' + institutionSlug + '/happenings', params: payload})
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

// Selection constraints
const isSelectable = () => {
    return true;
}

const isSelectAllow = (event) => {
    let now = dayjs.utc()
    let tsStart = dayjs(event.startStr)
    let tsEnd = dayjs(event.endStr)

    let tsLenConfig = institutionSettings['time_slot_length'].split(':')
    let tsLen = {
        hours: parseInt(tsLenConfig[0]),
        minutes: parseInt((tsLenConfig[1]))
    }

    let isNotPast = tsStart.isSameOrAfter(now);
    let isCurrentTimeSlot = now.isBetween(tsStart, tsEnd);

    let isValid = tsStart.add(tsLen.hours, 'hours').add(tsLen.minutes, 'minutes').isAfter(now)

    if (authStore.isAuthenticated && authStore.isExceedingQuotas(tsStart, tsEnd)) {
        return false;
    }

    return isValid && (isNotPast || isCurrentTimeSlot);
}

// Select action
const onSelect = (eventInfo) => {
    if (!isAuthenticated.value) {
        useToast().error('Must be authenticated!');
    } else {
        let happeningData = reactive({
            isSelected: true,
            resource: {
                id: eventInfo.resource.id,
                title: eventInfo.resource.title,
            },
            start: eventInfo.startStr,
            end: eventInfo.endStr,
            isVerificationRequired: eventInfo.resource.extendedProps.isVerificationRequired,
        });

        emit('open-modal-component', useCreateModal(happeningData));
    }
}

const onEventClick = (eventInfo) => {
    let happeningData = {}
    let isBgEvent = eventInfo.el.classList.contains('fc-bg-event')

    if (eventInfo.resource) {
        /* This is a new selection */
        console.log('NEW SELECTION');

        let dataPath = eventInfo;

        happeningData.resource = {
            id: dataPath.resource._resource.id,
            title: dataPath.resource._resource.title,
        }
    } else {
        /* This is an event */
        console.log('EVENT CLICKED');

        let dataPath = eventInfo.event;

        happeningData.resource = {
            id: dataPath.getResources()[0]._resource.id,
            title: dataPath.getResources()[0]._resource.title,
        };
        happeningData.id = dataPath.id;
        happeningData.user_02 = dataPath.extendedProps.status?.user?.verification;
        happeningData.start = dayjs.utc(dataPath._instance.range.start);
        happeningData.end = dayjs.utc(dataPath._instance.range.end);
        happeningData.isVerificationRequired = dataPath.extendedProps.isVerificationRequired;
    }

    if (!isBgEvent) {
        let can = eventInfo.event.extendedProps.can;

        if (can.edit && can.delete) {
            emit('open-modal-component', useEditDeleteModal(happeningData));
        } else if (can.verify) {
            emit('open-modal-component', useVerifyModal(happeningData));
        } else {
            emit('open-modal-component', useInfoModal(happeningData));
        }
    }
}

const onDatesSet = (dateInfo) => {
    authStore.updateQuotas(dateInfo.start)
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
        today: dayjs().format('DD.MM.YYYY'),
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
    slotMinTime: institutionSettings['start_time_slot'],
    slotMaxTime: institutionSettings['end_time_slot'],
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
    slotDuration: institutionSettings['time_slot_length'] + ':00',
    slotLabelInterval: institutionSettings['time_slot_length'] + ':00',
    selectOverlap: false,
    selectConstraint: 'businessHours',
    selectable: isSelectable,
    selectAllow: isSelectAllow,
    select: onSelect,
    eventClick: onEventClick,
    datesSet: onDatesSet,
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

<template>
    <FullCalendar id="terminal-view-calendar" ref="refCalendar" class="calendar" :options="calendarOptions">
        <template #eventContent="arg">
            <div class="text-center">
                <div v-if="arg.event.display === 'background'" class="border-b-2 pt-5 text-xl">
                    {{ translate(arg.event.extendedProps.description) }}
                </div>
                <b>{{ arg.timeText }}</b>
                <i>{{ arg.event.title }}</i>
            </div>
        </template>
    </FullCalendar>
</template>

<script setup>
import FullCalendar from "@fullcalendar/vue3";

import { useCalendar } from "@/Composables/Calendar";
import TerminalLayout from "@/Layouts/TerminalLayout.vue";
import { useAppStore } from "@/Stores/AppStore";

import { onBeforeMount, onMounted, onUnmounted, ref } from "vue";

defineOptions({ layout: TerminalLayout });

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    resourceGroup: {
        type: Object,
        required: true,
    },
    settings: {
        type: Object,
        required: true,
    },
    hiddenDays: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
let calendarOptions, refetchHappenings;
const translate = appStore.translate;
const refCalendar = ref(null);

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setCurrent(props.resourceGroup, props.settings, props.hiddenDays, false);

    const pagination = {
        currentPage: `/${props.resourceGroup.institution.slug}/${props.resourceGroup.slug}/resources`,
        nextPage: null,
        previousPage: null,
    };

    ({ calendarOptions, refetchHappenings } = useCalendar({
        calendarOptions: {
            headerToolbar: {
                left: "title",
                center: "",
                right: "",
            },
            select: false,
            selectable: false,
            selectAllow: false,
            eventClick: false,
        },
        pagination,
        translate,
    }));
});

onMounted(() => {
    Echo.channel("happenings").listen("HappeningsChangedEvent", () => {
        refetchHappenings(refCalendar);
    });
});

onUnmounted(() => {
    Echo.leave("happenings");
});
</script>

<style lang="css">
/* Firefox fix for now-indicator */
.fc .fc-timegrid-now-indicator-container {
    overflow: visible;
}

a.fc-event,
a.fc-event:hover {
    cursor: pointer;
}

/*
.fc-non-business {
    background-color: #BEBEBE !important;
}
*/

div.fc-timegrid-slots tr {
    background-color: #FFFFFF;
}
</style>

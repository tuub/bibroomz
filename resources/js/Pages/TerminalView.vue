<template>
    <FullCalendar id="terminal-view-calendar" ref="refCalendar" class="calendar" :options="calendarOptions">
        <template #eventContent="arg">
            <div class="text-center">
                <div v-if="arg.event.display == 'background'" class="border-b-2 pt-5 text-xl">
                    {{ arg.event.extendedProps.description }}
                </div>
                <b>{{ arg.timeText }}</b>
                <i>{{ arg.event.title }}</i>
            </div>
        </template>
    </FullCalendar>
</template>

<script setup>
import "@fullcalendar/core/vdom";
import FullCalendar from "@fullcalendar/vue3";

import { useCalendar } from "@/Composables/Calendar";
import { useAppStore } from "@/Stores/AppStore";
import TerminalLayout from '@/Layouts/TerminalLayout.vue';

import { onBeforeMount, onMounted, onUnmounted, ref } from "vue";


defineOptions({ layout: TerminalLayout })

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    institution: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
let calendarOptions, refetchHappenings;

const refCalendar = ref(null);

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    const appStore = useAppStore();
    appStore.setCurrentInstitution(props.institution, false);

    const pagination = {
        currentPage: `/${props.institution.slug}/resources`,
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
@import url("@fullcalendar/daygrid/main.css");
@import url("@fullcalendar/timegrid/main.css");

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
    background-color: #ffffff;
}

</style>

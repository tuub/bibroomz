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

<script setup lang="ts">
import type { CalendarOptions as FullCalendarOptions } from "@fullcalendar/core";
import FullCalendar from "@fullcalendar/vue3";

import { type CalendarPagination, useCalendar } from "@/Composables/Calendar";
import TerminalLayout from "@/Layouts/TerminalLayout.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { ResourceGroup, Settings } from "@/Stores/AppStore";

import { onMounted, onUnmounted, ref } from "vue";

defineOptions({ layout: TerminalLayout });

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps<{
    resourceGroup: ResourceGroup;
    settings: Settings;
    hiddenDays: number[];
}>();

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
appStore.setCurrent(props.resourceGroup, props.settings, props.hiddenDays, false);

const pagination: CalendarPagination = {
    currentPage: `/${props.resourceGroup.institution?.slug ?? ""}/${props.resourceGroup.slug ?? ""}/resources`,
    nextPage: null,
    previousPage: null,
};

const { calendarOptions: rawCalendarOptions, refetchHappenings } = useCalendar({
    calendarOptions: {
        headerToolbar: {
            left: "title",
            center: "",
            right: "",
        },
        selectable: false,
    },
    pagination,
    translate: appStore.translate,
});
const calendarOptions = rawCalendarOptions as FullCalendarOptions;

const translate = appStore.translate;
const refCalendar = ref(null);

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
    background-color: #ffffff;
}
</style>

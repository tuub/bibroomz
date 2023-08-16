<template>
    <FullCalendar ref="refCalendar" class="calendar" :options="calendarOptions">
        <template #eventContent="arg">
            <div class="text-center">
                <div
                    v-if="arg.event.display == 'background'"
                    class="border-b-2 pt-5 text-xl"
                >
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
import { onBeforeMount, onMounted, onUnmounted, ref } from "vue";
import { useAppStore } from "@/Stores/AppStore";
import { useCalendar } from "@/Composables/Calendar";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    institution: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { calendarOptions, refetchHappenings } = useCalendar({
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
});

const refCalendar = ref(null);

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    appStore.setCurrentInstitution(props.institution, false);
});

onMounted(() => {
    Echo.channel("happenings").listen("HappeningsChanged", () => {
        refetchHappenings(refCalendar.value);
    });
});
onUnmounted(() => {
    Echo.leave("happenings");
});
</script>

<style lang="css">
/* FIXME: Alias ~ in Vite! */
@import "/node_modules/@fullcalendar/daygrid/main.css";
@import "/node_modules/@fullcalendar/timegrid/main.css";

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
    background-color: #fff;
}
</style>

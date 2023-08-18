<template>
    <div class="calendar">
        <Legend class="mb-5"></Legend>
        <FullCalendar
            ref="refCalendar"
            class="calendar"
            :options="calendarOptions"
        >
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
    </div>
</template>

<script setup>
import "@fullcalendar/core/vdom";
import FullCalendar from "@fullcalendar/vue3";
import { onMounted, onUnmounted, ref, unref, watch } from "vue";

import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import Legend from "./Legend.vue";
import { useCalendar } from "@/Composables/Calendar";
// import Spinner from "../Shared/Spinner.vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
let appStore = useAppStore();
let authStore = useAuthStore();

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits(["show-status", "open-modal-component"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { calendarOptions, refetchHappenings } = useCalendar({ emit });

const refCalendar = ref(null);
let { isAuthenticated } = storeToRefs(authStore);

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(isAuthenticated, () => {
    refetchHappenings(refCalendar);
});

watch(
    () => authStore.userHappenings,
    () => {
        const api = unref(refCalendar).getApi();
        authStore.updateQuotas(api.getDate());
    },
    { deep: true }
);

watch(
    () => appStore.locale,
    () => {
        const api = unref(refCalendar).getApi();
        api.setOption("locale", appStore.locale);
    }
);

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    Echo.channel("happenings").listen("HappeningsChanged", () => {
        refetchHappenings(refCalendar);
    });

    let slotAxis = document.querySelector('.fc-timegrid-axis-frame')
    slotAxis.innerHTML = '<i class="ri-time-line"></i>'
});

onUnmounted(() => {
    Echo.leave("happenings");
});
</script>

<style lang="css">
@import "fullcalendar/daygrid/main.css";
@import "fullcalendar/timegrid/main.css";

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

.fc .fc-timegrid-axis-frame {
    justify-content: center;
    margin-top: 2px;
}
</style>

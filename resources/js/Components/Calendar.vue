<template>
    <div id="legend-full-calendar-wrapper" class="calendar">
        <Legend class="mb-5"></Legend>

        <div v-if="pagination.previousPage || pagination.nextPage" class="page-change-wrapper">
            <label>{{ $t("calendar.browse_resources") }}</label>
            <button
                :disabled="!pagination.previousPage"
                :class="{ 'opacity-25': !pagination.previousPage }"
                @click="previousStuff"
            >
                <i class="ri-arrow-left-s-line ri-xl"></i>
            </button>
            <button :disabled="!pagination.nextPage" :class="{ 'opacity-25': !pagination.nextPage }" @click="nextStuff">
                <i class="ri-arrow-right-s-line ri-xl"></i>
            </button>
        </div>

        <FullCalendar ref="refCalendar" class="calendar" :options="calendarOptions">
            <template #eventContent="arg">
                <div class="text-center">
                    <div v-if="arg.event.display === 'background'" class="border-b-2 pt-5 text-xl">
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

import Legend from "@/Components/Legend.vue";
import { useCalendar } from "@/Composables/Calendar";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { computed, onBeforeMount, onMounted, onUnmounted, reactive, ref, unref, watch } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits(["show-status", "open-modal-component"]);

const windowWidth = ref(window.innerWidth);
const resourceCount = ref(0);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const institution = appStore.institution;

const initialPage = computed(() => `/${institution.slug}/resources?count=${resourceCount.value}&page=1`);

const pagination = reactive({
    currentPage: unref(initialPage),
    nextPage: null,
    previousPage: null,
});

const { calendarOptions, refetchHappenings } = useCalendar({ emit, pagination });

const { isAuthenticated } = storeToRefs(authStore);
const refCalendar = ref(null);

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(isAuthenticated, () => {
    refetchHappenings(refCalendar);
});

watch(
    () => authStore.userHappenings,
    () => {
        const api = unref(refCalendar)?.getApi();
        authStore.updateQuotas(api?.getDate());
    },
    { deep: true },
);

watch(
    () => appStore.locale,
    () => {
        const api = unref(refCalendar)?.getApi();
        api?.setOption("locale", appStore.locale);
    },
);

watch(resourceCount, () => {
    pagination.currentPage = unref(initialPage);

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
});

// ------------------------------------------------
// Methods
// ------------------------------------------------

const previousStuff = () => {
    pagination.currentPage = pagination.previousPage;

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
};

const nextStuff = () => {
    pagination.currentPage = pagination.nextPage;

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
};

const setResourceCountFromScreen = () => {
    if (windowWidth.value < 600) {
        resourceCount.value = 2;
    } else if (windowWidth.value < 800) {
        resourceCount.value = 3;
    } else if (windowWidth.value < 1000) {
        resourceCount.value = 4;
    } else {
        resourceCount.value = 8;
    }
};

const handleScreenResize = () => {
    windowWidth.value = window.innerWidth;

    setResourceCountFromScreen();
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onBeforeMount(() => {
    setResourceCountFromScreen();
});

onMounted(() => {
    Echo.channel("happenings").listen("HappeningsChanged", () => {
        refetchHappenings(refCalendar);
    });

    const slotAxis = document.querySelector(".fc-timegrid-axis-frame");
    slotAxis.innerHTML = '<i class="ri-time-line"></i>';

    window.addEventListener("resize", handleScreenResize);
});

onUnmounted(() => {
    Echo.leave("happenings");

    window.removeEventListener("resize", handleScreenResize);
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

.fc-non-business {
    background-color: #BEBEBE !important;
}

div.fc-timegrid-slots tr {
    background-color: #ffffff;
}

.fc .fc-timegrid-axis-frame {
    justify-content: center;
    margin-top: 2px;
}

.fc .fc-toolbar-title {
    font-size: 1rem;
    margin-right: 1em;
}

.fc .fc-toolbar.fc-header-toolbar {
    float: left;
    width: 20%;
    margin-bottom: 0.5em;
    margin-top: 1em;
    margin-right: 1em;
    padding-right: 1em;
}

@media only screen and (max-width: 1150px) {
    .fc .fc-toolbar.fc-header-toolbar {
        width: 30%;
    }
}

@media only screen and (max-width: 600px) {
    .fc .fc-toolbar.fc-header-toolbar {
        width: 40%;
    }
}

@media only screen and (max-width: 500px) {
    .fc .fc-toolbar.fc-header-toolbar {
        width: 60%;
    }
}

.page-change-wrapper {
    position: fixed;
    top: 155px;
    right: 30px;
}
</style>

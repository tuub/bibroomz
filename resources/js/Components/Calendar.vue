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

        <FullCalendar ref="refCalendar" class="full-calendar" :options="calendarOptions">
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
const resourceGroup = appStore.resourceGroup;

const initialPage = computed(() => `/${resourceGroup.institution.slug}/${resourceGroup.slug}/resources?count=${resourceCount.value}&page=1`);

const pagination = reactive({
    currentPage: unref(initialPage),
    nextPage: null,
    previousPage: null,
});

const translate = appStore.translate;
const { calendarOptions, refetchHappenings } = useCalendar({ emit, pagination, translate });

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
    Echo.channel("happenings").listen("HappeningsChangedEvent", () => {
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
    background-color: #FFFFFF;
}

.fc .fc-timegrid-axis-frame {
    justify-content: center;
    margin-top: 2px;
}

.fc .fc-toolbar-title {
    font-size: 0.9em;
    margin-right: 0.5em;
}

@-moz-document url-prefix() {
    .fc .fc-toolbar-title {
        font-size: 0.8em;
        margin-right: 0.5em;
    }
}


.fc .fc-toolbar.fc-header-toolbar {
    float: left;
    width: 27%;
    margin-bottom: 0.5em;
    margin-top: 1em;
    margin-right: 1em;
    padding-right: 1em;
}

.fc-direction-ltr .fc-button-group > .fc-button:not(:last-child){
    width: 21px;
    height: 25px;
    font-size: 0.85em;
}

.fc-direction-ltr .fc-button-group > .fc-button:not(:first-child){
    width: 21px;
    height: 25px;
    font-size: 0.85em;
}

.page-change-wrapper {
    position: absolute;
    top: -13px;
    right: 0;
    font-size: 0.9em;
    font-weight: bold;
}

.page-change-wrapper > button > i{
    font-size: 1.4em;
    line-height: 0.9666em;
    vertical-align: -0.16em;
}

.page-change-wrapper > label{
    width: 200px;
    margin-right: 20px;
    top: 0;
    right: 0;
}

@-moz-document url-prefix() {
    .page-change-wrapper {
        position: absolute;
        top: -15px;
        right: 0;
        font-size: small;
    }

    .page-change-wrapper > label{
        right: 20px;
    }
}

.page-change-wrapper > button:nth-child(2){
    background-color: #2C3E50;
    color: white;
}

.page-change-wrapper > button:nth-child(3){
    background-color: #2C3E50;
    color: white;
}

@-moz-document url-prefix() {
    .page-change-wrapper {
        position: absolute;
        top: -12px;
        right: -5px;
    }
}

.full-calendar > div:nth-child(1) > div:nth-child(1){
    position: absolute;
    left: 65px;
    top: -13px;
}

.full-calendar > div:nth-child(1) > div:nth-child(3){
    position: absolute;
    left: 0;
    top: -16px;
}


.calendar-if-not-logged-in{
    width: 100% !important;
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

@media only screen and (max-width: 530px) {
    .fc .fc-toolbar.fc-header-toolbar {
        width: 50%;
    }

    .content-wrapper{
        margin: 12em 2em 0 2em;
        min-height: 80vh;
        position: relative;
    }

    .page-change-wrapper > label{
        position: absolute;
        top: 4px;
        left: 65px;
    }

    .page-change-wrapper > button:nth-child(2){
        position: absolute;
        top: 3px;
        left: 0px;
        background-color: #2C3E50;
        color: white;
    }

    .page-change-wrapper > button:nth-child(3){
        position: absolute;
        top: 3px;
        left: 20px;
        background-color: #2C3E50;
        color: white;
    }

    .page-change-wrapper{
        position: absolute;
        top: -13px;
        left: 0;
        font-size: 0.9em;
        font-weight: bold;
    }

    .full-calendar > div:nth-child(1) > div:nth-child(1){
        position: absolute;
        left: 65px;
        top: -50px;
    }

    .full-calendar > div:nth-child(1) > div:nth-child(3){
        position: absolute;
        left: 0;
        top: -53px;
    }
}

</style>

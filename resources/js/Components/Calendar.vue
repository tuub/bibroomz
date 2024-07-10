<template>
    <div id="legend-full-calendar-wrapper" class="calendar">
        <Legend class="mb-5"></Legend>
        <h1 class="resource-group-name text-lg font-bold">
            {{ translate(resourceGroup?.title) }}
            <a href="#"><i class="ri-information-line" @click="getResourceGroupInfo(resourceGroup)"></i></a>
        </h1>
        <div
            :class="{
                'page-change-wrapper': true,
                'page-change-wrapper-page-change': isPaginated,
                'page-change-wrapper-no-page-change': !isPaginated,
            }"
        >
            <label>{{ $t("calendar.browse_resources") }}</label>
            <button
                :disabled="!pagination.previousPage"
                :class="{ 'opacity-25': !pagination.previousPage }"
                @click="previousResources"
            >
                <i class="ri-arrow-left-s-line ri-xl"></i>
            </button>
            <button
                :disabled="!pagination.nextPage"
                :class="{ 'opacity-25': !pagination.nextPage }"
                @click="nextResources"
            >
                <i class="ri-arrow-right-s-line ri-xl"></i>
            </button>
        </div>

        <FullCalendar ref="refCalendar" class="full-calendar" :options="calendarOptions">
            <template #eventContent="arg">
                <div class="truncate-lines text-center" :style="{ '--truncate-lines': countLines(arg.event) }">
                    <div v-if="arg.event.display === 'background'" class="border-b-2 pt-5 text-xl">
                        {{ translate(arg.event.extendedProps.description) }}
                    </div>
                    <b>{{ arg.timeText }}</b>
                    <div class="px-1">{{ translate(arg.event.extendedProps.label) }}</div>
                </div>
            </template>
        </FullCalendar>
    </div>
</template>

<script setup>
import FullCalendar from "@fullcalendar/vue3";

import Legend from "@/Components/Legend.vue";
import { useCalendar } from "@/Composables/Calendar";
import { useResourceGroupInfoModal } from "@/Composables/ModalActions";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import dayjs from "dayjs";
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
const baseUrl = import.meta.env.VITE_API_URL;
const resourceGroup = appStore.resourceGroup;

const initialPage = computed(
    () =>
        `${baseUrl}/${resourceGroup.institution.slug}/${resourceGroup.slug}/resources?count=${resourceCount.value}&page=1`,
);

const pagination = reactive({
    currentPage: unref(initialPage),
    nextPage: null,
    previousPage: null,
});

const refCalendar = ref(null);
let calendarApi;

const resetCalendarDate = () => {
    const date = dayjs(calendarApi.getDate()).utcOffset(0, true).format("YY-MM-DD");
    const url = new URL(pagination.currentPage, window.location.origin);
    url.searchParams.set("date", date);
    pagination.currentPage = url.pathname + "?" + url.searchParams;
};

const changeCustomButtons = () => {
    const date = dayjs(calendarApi.getDate());
    const validRange = calendarApi.getOption("validRange");

    const prevButton = document.querySelector(".fc-prevCustom-button");
    prevButton.disabled = date.isSame(validRange.start, "day");

    const nextButton = document.querySelector(".fc-nextCustom-button");
    nextButton.disabled = date.isSame(validRange.end, "day");
};

const customCalendarOptions = {
    customButtons: {
        prevCustom: {
            icon: "chevron-left",
            click: () => {
                calendarApi.prev();
                resetCalendarDate();
                calendarApi.refetchResources();
                changeCustomButtons();
            },
        },
        nextCustom: {
            icon: "chevron-right",
            click: () => {
                calendarApi.next();
                resetCalendarDate();
                calendarApi.refetchResources();
                changeCustomButtons();
            },
        },
    },

    headerToolbar: {
        left: "title",
        center: "",
        right: "prevCustom,nextCustom",
    },
};

const translate = appStore.translate;
const { calendarOptions, refetchHappenings } = useCalendar({
    emit,
    pagination,
    translate,
    calendarOptions: customCalendarOptions,
});

const { isAuthenticated } = storeToRefs(authStore);

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPaginated = computed(() => pagination.previousPage || pagination.nextPage);

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

const previousResources = () => {
    pagination.currentPage = pagination.previousPage;

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
};

const nextResources = () => {
    pagination.currentPage = pagination.nextPage;

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
};

const getResourceGroupInfo = (resourceGroup) => {
    emit(
        "open-modal-component",
        useResourceGroupInfoModal({
            title: resourceGroup.title,
            description: resourceGroup.description,
        }),
    );
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

const convertTimeToMinutes = (time) => {
    const timeParts = time.split(":");
    return Number(timeParts[0]) * 60 + Number(timeParts[1]);
};

const countLines = (event) => {
    const milliseconds = event.end - event.start;
    const seconds = milliseconds / 1000;
    const minutes = seconds / 60;

    const timeSlotLength = convertTimeToMinutes(appStore.settings.resource_group.time_slot_length);

    return minutes / timeSlotLength;
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

    calendarApi = unref(refCalendar)?.getApi();
    changeCustomButtons();
});

onUnmounted(() => {
    Echo.leave("happenings");

    window.removeEventListener("resize", handleScreenResize);
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

.fc-non-business {
    background-color: #bebebe !important;
}

div.fc-timegrid-slots tr {
    background-color: #ffffff;
}

.fc .fc-timegrid-axis-frame {
    justify-content: center;
    margin-top: 2px;
}

.fc .fc-toolbar-title {
    margin-right: 0.5em;
    font-size: 0.9em;
}

@-moz-document url-prefix() {
    .fc .fc-toolbar-title {
        margin-right: 0.5em;
        font-size: 0.8em;
    }
}

.fc .fc-toolbar.fc-header-toolbar {
    float: left;
    margin-top: 1em;
    margin-right: 1em;
    margin-bottom: 0.5em;
    padding-right: 1em;
    width: 27%;
}

.fc-direction-ltr .fc-button-group > .fc-button:not(:last-child) {
    width: 21px;
    height: 25px;
    font-size: 0.85em;
}

.fc-direction-ltr .fc-button-group > .fc-button:not(:first-child) {
    width: 21px;
    height: 25px;
    font-size: 0.85em;
}

.page-change-wrapper {
    position: absolute;
    top: -13px;
    left: 0;
    font-weight: bold;
    font-size: 0.9em;
}

.page-change-wrapper > label {
    position: absolute;
    top: 4px;
    left: 65px;
    width: 200px;
}

.page-change-wrapper > button:nth-child(3) {
    position: absolute;
    top: 3px;
    left: 20px;
    background-color: #2c3e50;
    color: white;
}

.page-change-wrapper > button > i {
    vertical-align: -0.16em;
    font-size: 1.4em;
    line-height: 0.9666em;
}

.page-change-wrapper > button:nth-child(2) {
    position: absolute;
    top: 3px;
    left: 0px;
    background-color: #2c3e50;
    color: white;
}

.full-calendar > div:nth-child(1) > div:nth-child(1) {
    position: absolute;
    top: -50px;
    left: 65px;
}

.full-calendar > div:nth-child(1) > div:nth-child(3) {
    position: absolute;
    top: -53px;
    left: 0;
}

.resource-group-name {
    position: absolute;
    top: -93px;
    left: 0px;
}

.calendar-if-not-logged-in {
    width: 100% !important;
}

@media only screen and (max-width: 1150px) {
    .fc .fc-toolbar.fc-header-toolbar {
        width: 50%;
    }
}

@media only screen and (max-width: 600px) {
    .fc .fc-toolbar.fc-header-toolbar {
        width: 40%;
    }
}

@-moz-document url-prefix() {
    .page-change-wrapper {
        position: absolute;
        top: -15px;
        right: 0;
        font-size: small;
    }

    .page-change-wrapper > label {
        right: 20px;
    }
}

.truncate-lines {
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: var(--truncate-lines, 2);
}
</style>

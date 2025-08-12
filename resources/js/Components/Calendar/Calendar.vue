<template>
    <div class="calendar">
        <div class="w-full text-center">
            <h1 class="inline-block text-2xl font-bold">
                {{ resourceGroupTitle }}
            </h1>
        </div>
        <div class="my-2 flex flex-wrap justify-between">
            <!-- BROWSE DATE -->
            <div id="calendar-date-browser" class="mb-2 flex w-full justify-start lg:mb-0 lg:w-1/5">
                <!-- ARROW LEFT -->
                <div class="flex w-full items-center justify-end lg:w-1/6">
                    <button
                        id="calendar-date-previous"
                        :disabled="date?.isSame(validRange?.start, 'day')"
                        :class="{ 'opacity-25': date?.isSame(validRange?.start, 'day') }"
                        title="Go to previous calendar date"
                        @click="datePrev"
                    >
                        <i class="ri-arrow-left-s-line ri-xl"></i>
                        <span class="sr-only">Go to previous calendar date</span>
                    </button>
                </div>
                <!-- DATE DISPLAY -->
                <div id="calendar-date-display" class="flex w-full items-center justify-center text-center lg:w-4/6">
                    {{ date?.isToday() ? $t("calendar.today") : date?.locale(locale).format(appStore.dateFormat) }}
                </div>
                <!-- ARROW RIGHT -->
                <div class="flex w-full items-center justify-start lg:w-1/6">
                    <button
                        id="calendar-date-next"
                        :disabled="date?.isSame(validRange?.end, 'day')"
                        :class="{ 'opacity-25': date?.isSame(validRange?.end, 'day') }"
                        title="Go to next calendar date"
                        @click="dateNext"
                    >
                        <i class="ri-arrow-right-s-line ri-xl"></i>
                        <span class="sr-only">Go to next calendar date</span>
                    </button>
                </div>
            </div>

            <!-- LEGEND -->
            <div class="mb-2 flex w-full items-center justify-center text-center lg:mb-0 lg:w-3/5">
                <Legend></Legend>
            </div>

            <!-- BROWSE RESOURCES -->
            <div id="calendar-resource-browser" class="mb-2 flex w-full justify-end lg:mb-0 lg:w-1/5">
                <!-- ARROW LEFT -->
                <div class="flex w-full items-center justify-end lg:w-1/6">
                    <button
                        id="calendar-resources-previous"
                        :disabled="!pagination.previousPage"
                        :class="{ 'opacity-25': !pagination.previousPage }"
                        title="Go to previous calendar resources"
                        @click="resourcesPrev"
                    >
                        <i class="ri-arrow-left-s-line ri-xl"></i>
                        <span class="sr-only">Go to previous calendar resources</span>
                    </button>
                </div>
                <!-- RESOURCE DISPLAY -->
                <div class="flex w-full items-center justify-center text-center lg:w-4/6">
                    {{ $t("calendar.browse_resources") }}
                </div>
                <!-- ARROW RIGHT -->
                <div id="calendar-resources-display" class="flex w-full items-center justify-start lg:w-1/6">
                    <button
                        id="calendar-resources-next"
                        :disabled="!pagination.nextPage"
                        :class="{ 'opacity-25': !pagination.nextPage }"
                        title="Go to next calendar resources"
                        @click="resourcesNext"
                    >
                        <i class="ri-arrow-right-s-line ri-xl"></i>
                        <span class="sr-only">Go to next calendar resources</span>
                    </button>
                </div>
            </div>
        </div>

        <FullCalendar ref="refCalendar" class="full-calendar" :options="calendarOptions">
            <template #eventContent="arg">
                <div class="truncate-lines text-center" :style="{ '--truncate-lines': countLines(arg.event) }">
                    <div v-if="arg.event.display === 'background'" class="border-b-2 pt-5 text-xl">
                        {{ translate(arg.event.extendedProps.description) }}
                    </div>

                    <!-- EVENT TIME DISPLAY START -->
                    <i
                        v-if="
                            arg.event.extendedProps.status &&
                            (arg.event.extendedProps.status.type === 'user-reservation' ||
                                arg.event.extendedProps.status.type === 'user-booking')
                        "
                        class="ri-user-fill"
                    ></i>
                    <span class="text-sm">{{ arg.timeText }}</span>
                    <i
                        v-if="
                            arg.event.extendedProps.status &&
                            (arg.event.extendedProps.status.type === 'user-reservation' ||
                                arg.event.extendedProps.status.type === 'user-booking')
                        "
                        class="ri-user-fill"
                    ></i>
                    <!-- EVENT TIME DISPLAY END -->

                    <div class="px-1">{{ translate(arg.event.extendedProps.label) }}</div>
                </div>
            </template>
        </FullCalendar>
    </div>
</template>

<script setup>
import FullCalendar from "@fullcalendar/vue3";

import Legend from "@/Components/Calendar/Legend.vue";
import { useCalendar } from "@/Composables/Calendar";
//import { useResourceGroupInfoModal } from "@/Composables/ModalActions";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import dayjs from "dayjs";
import "dayjs/locale/de";
import isToday from "dayjs/plugin/isToday";
import { storeToRefs } from "pinia";
import { computed, onBeforeMount, onMounted, onUnmounted, reactive, ref, unref, watch } from "vue";

dayjs.extend(isToday);

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

const resourceGroupTitle = computed(() => {
    return translate(resourceGroup.institution.title) + ": " + translate(resourceGroup.title);
});

const pagination = reactive({
    currentPage: unref(initialPage),
    nextPage: null,
    previousPage: null,
});

const refCalendar = ref(null);
let calendarApi;

const date = ref();
const validRange = ref();

watch(date, () => {
    validRange.value = calendarApi.getOption("validRange");
});

const resetCalendarDate = () => {
    date.value = dayjs(calendarApi.getDate());

    const url = new URL(pagination.currentPage, window.location.origin);
    url.searchParams.set("date", date.value.utcOffset(0, true).format("YY-MM-DD"));
    pagination.currentPage = url.pathname + "?" + url.searchParams;
};

function dateNext() {
    calendarApi.next();
    resetCalendarDate();
    calendarApi.refetchResources();
}

function datePrev() {
    calendarApi.prev();
    resetCalendarDate();
    calendarApi.refetchResources();
}

const { locale } = storeToRefs(appStore);

const translate = appStore.translate;
const { calendarOptions, refetchHappenings } = useCalendar({
    emit,
    pagination,
    translate,
});

const { isAuthenticated } = storeToRefs(authStore);

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
    () => locale,
    () => {
        const api = unref(refCalendar)?.getApi();
        api?.setOption("locale", locale);
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

const resourcesPrev = () => {
    pagination.currentPage = pagination.previousPage;

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
};

const resourcesNext = () => {
    pagination.currentPage = pagination.nextPage;

    const api = unref(refCalendar)?.getApi();
    api?.refetchResources();
};

/*
const getResourceGroupInfo = (resourceGroup) => {
    emit(
        "open-modal-component",
        useResourceGroupInfoModal({
            title: resourceGroup.title,
            description: resourceGroup.description,
        }),
    );
};
 */

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
    date.value = dayjs(calendarApi.getDate());
});

onUnmounted(() => {
    Echo.leave("happenings");

    window.removeEventListener("resize", handleScreenResize);
});
</script>

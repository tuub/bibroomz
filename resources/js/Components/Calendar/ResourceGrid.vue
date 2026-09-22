<!--
    The resource calendar, built on MIT-only FullCalendar.

    One `timeGridDay` calendar per room, laid out in a flex row, in place of the
    single premium calendar with resource columns this replaced. The toolbar,
    legend and paging chrome are ours; below them a v-for renders one
    <FullCalendar> per room.
-->
<template>
    <div class="calendar">
        <div class="w-full text-center">
            <h1 class="inline-block text-2xl font-bold">
                {{ resourceGroupTitle }}
            </h1>
        </div>

        <!--
            The unattended kiosk has nobody to press these, so it gets the date
            on its own - which is all the premium view's `headerToolbar: { left:
            "title" }` ever showed there.
        -->
        <div v-if="!interactive" id="calendar-date-display" class="my-2 text-center">
            {{ date.isToday() ? $t("calendar.today") : formattedDate }}
        </div>

        <div v-else class="my-2 flex flex-wrap justify-between">
            <!-- BROWSE DATE -->
            <div id="calendar-date-browser" class="mb-2 flex w-full justify-start lg:mb-0 lg:w-1/5">
                <div class="flex w-full items-center justify-end lg:w-1/6">
                    <button
                        id="calendar-date-today"
                        :disabled="date.isToday()"
                        :class="{ 'opacity-25': date.isToday() }"
                        :title="$t('calendar.go_to_today')"
                        @click="goToday"
                    >
                        <i class="ri-calendar-check-line ri-xl"></i>
                        <span class="sr-only">{{ $t("calendar.go_to_today") }}</span>
                    </button>
                </div>
                <div class="flex w-full items-center justify-end lg:w-1/6">
                    <button
                        id="calendar-date-previous"
                        :disabled="isAtRangeStart"
                        :class="{ 'opacity-25': isAtRangeStart }"
                        title="Go to previous calendar date"
                        @click="goPrev"
                    >
                        <i class="ri-arrow-left-s-line ri-xl"></i>
                        <span class="sr-only">Go to previous calendar date</span>
                    </button>
                </div>
                <div id="calendar-date-display" class="flex w-full items-center justify-center text-center lg:w-3/6">
                    {{ date.isToday() ? $t("calendar.today") : formattedDate }}
                </div>
                <div class="flex w-full items-center justify-start lg:w-1/6">
                    <button
                        id="calendar-date-next"
                        :disabled="isAtRangeEnd"
                        :class="{ 'opacity-25': isAtRangeEnd }"
                        title="Go to next calendar date"
                        @click="goNext"
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
                <div class="flex w-full items-center justify-end lg:w-1/6">
                    <button
                        id="calendar-resources-previous"
                        :disabled="!previousPage || isLoadingRooms"
                        :class="{ 'opacity-25': !previousPage || isLoadingRooms }"
                        title="Go to previous calendar resources"
                        @click="roomsPrev"
                    >
                        <i class="ri-arrow-left-s-line ri-xl"></i>
                        <span class="sr-only">Go to previous calendar resources</span>
                    </button>
                </div>
                <div class="flex w-full items-center justify-center text-center lg:w-4/6">
                    {{ $t("calendar.browse_resources") }}
                </div>
                <div id="calendar-resources-display" class="flex w-full items-center justify-start lg:w-1/6">
                    <button
                        id="calendar-resources-next"
                        :disabled="!nextPage || isLoadingRooms"
                        :class="{ 'opacity-25': !nextPage || isLoadingRooms }"
                        title="Go to next calendar resources"
                        @click="roomsNext"
                    >
                        <i class="ri-arrow-right-s-line ri-xl"></i>
                        <span class="sr-only">Go to next calendar resources</span>
                    </button>
                </div>
            </div>
        </div>

        <!--
            The app's reactive classes live on a wrapper, never on a
            <FullCalendar> element, because FullCalendar
            assigns its root classes via classList and a Vue :class rebind would
            wipe them permanently.
        -->
        <div ref="gridRoot" class="full-calendar roomz-grid-root" :class="{ 'roomz-calendar-loading': isLoadingRooms }">
            <div class="roomz-grid-row">
                <div
                    v-for="(room, index) in rooms"
                    :key="room.id"
                    class="roomz-grid-col"
                    :class="{ 'roomz-grid-col-first': index === 0 }"
                    :data-room-id="room.id"
                >
                    <FullCalendar
                        :ref="(instance) => setColumnCalendar(room.id, instance)"
                        :options="columnOptions(room, index) as FullCalendarOptions"
                    >
                        <template #eventContent="arg">
                            <div
                                class="truncate-lines text-center"
                                :style="{ '--truncate-lines': countLines(arg.event) }"
                            >
                                <i v-if="isOwnHappening(arg.event)" class="ri-user-fill"></i>
                                <span class="text-sm">{{ arg.timeText }}</span>
                                <i v-if="isOwnHappening(arg.event)" class="ri-user-fill"></i>

                                <div class="px-1">{{ translate(arg.event.extendedProps.label) }}</div>
                                <div
                                    v-if="authStore.isAdmin && arg.event.extendedProps.user_01"
                                    class="px-1 text-xs font-medium"
                                >
                                    {{ arg.event.extendedProps.user_01 }}
                                </div>
                            </div>
                        </template>
                        <template #backgroundEventContent="arg">
                            <div class="border-b-2 pt-5 text-center text-xl">
                                {{ translate(arg.event.extendedProps.description) }}
                            </div>
                        </template>
                    </FullCalendar>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { EventApi, CalendarOptions as FullCalendarOptions } from "@fullcalendar/vue3";
import FullCalendar from "@fullcalendar/vue3";

import Legend from "@/Components/Calendar/Legend.vue";
import type { Room } from "@/Composables/ResourceGridCalendar";
import { useResourceGridCalendar } from "@/Composables/ResourceGridCalendar";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { ModalOpenPayload } from "@/Stores/Modal";
import { withBaseUrl } from "@/baseUrl";

import dayjs from "dayjs";
import "dayjs/locale/de";
import isToday from "dayjs/plugin/isToday";
import { storeToRefs } from "pinia";
import { computed, nextTick, onBeforeMount, onMounted, onUnmounted, ref, watch } from "vue";

dayjs.extend(isToday);

const appStore = useAppStore();
const authStore = useAuthStore();

const props = withDefaults(
    defineProps<{
        /**
         * The unattended kiosk sets this to false: no selecting a slot, no
         * clicking an event, and no chrome to press - the same interactions the
         * premium view switched off there through `select`/`selectAllow`/
         * `eventClick: false`.
         */
        interactive?: boolean;
    }>(),
    { interactive: true },
);

const emit = defineEmits<{
    (event: "show-status"): void;
    <Props>(event: "open-modal-component", payload: ModalOpenPayload<Props>): void;
}>();

const resourceGroup = appStore.resourceGroup;
if (!resourceGroup?.institution) {
    throw new Error("Calendar requires a current resource group with an institution.");
}
const institution = resourceGroup.institution;
const resourcesUrl = withBaseUrl(`/${institution.slug}/${resourceGroup.slug}/resources`);

const { locale } = storeToRefs(appStore);
const { isAuthenticated } = storeToRefs(authStore);
const translate = appStore.translate;

const windowWidth = ref(window.innerWidth);
const roomCount = ref(0);

const {
    rooms,
    date,
    validRange,
    isLoadingRooms,
    currentPage,
    nextPage,
    previousPage,
    loadRooms,
    loadHappenings,
    columnOptions,
} = useResourceGridCalendar({
    emit,
    translate,
    overrides: { selectable: props.interactive, interactive: props.interactive },
});

const resourceGroupTitle = computed(() => translate(institution.title) + ": " + translate(resourceGroup.title));

// The "today" half of the label needs `$t`, which only the template has, so
// only the formatted date is computed here.
const formattedDate = computed(() => date.value.locale(locale.value).format(appStore.dateFormat ?? undefined));

const isAtRangeStart = computed(() => date.value.isSame(dayjs(validRange.start), "day"));
const isAtRangeEnd = computed(() => date.value.isSame(dayjs(validRange.end), "day"));

function buildPage(page: number) {
    const url = new URL(resourcesUrl, window.location.origin);
    url.searchParams.set("count", String(roomCount.value));
    url.searchParams.set("page", String(page));
    url.searchParams.set("date", date.value.utcOffset(0, true).format("YY-MM-DD"));

    return `${url.pathname}?${url.searchParams.toString()}`;
}

function reload() {
    currentPage.value = buildPage(1);
    void loadRooms();
    void loadHappenings();
}

/**
 * FullCalendar reads `initialDate` once, when an instance is constructed, so
 * handing the per-column options a later date moves nothing: the columns would
 * keep rendering the day they were mounted on while the toolbar above them
 * showed another. The premium view never had to care - it was one calendar,
 * navigated through its own toolbar - but here the toolbar is ours, so each
 * column has to be walked to the new day through its own API.
 *
 * Keyed by room id rather than collected in an array, because paging swaps the
 * whole set of columns out and an array ref would keep the stale entries.
 */
type ColumnCalendar = { getApi: () => { gotoDate: (date: Date) => void } };

const columnCalendars = new Map<Room["id"], ColumnCalendar>();

function setColumnCalendar(id: Room["id"], instance: unknown) {
    if (instance) {
        columnCalendars.set(id, instance as ColumnCalendar);

        return;
    }

    columnCalendars.delete(id);
}

function goToDate(next: dayjs.Dayjs) {
    date.value = next;
    authStore.updateQuotas(next.toDate());
    columnCalendars.forEach((calendar) => calendar.getApi().gotoDate(next.toDate()));
    reload();
}

const goNext = () => goToDate(date.value.add(1, "day"));
const goPrev = () => goToDate(date.value.subtract(1, "day"));
const goToday = () => goToDate(dayjs());

function roomsPrev() {
    if (!previousPage.value) {
        return;
    }
    currentPage.value = previousPage.value;
    void loadRooms();
}

function roomsNext() {
    if (!nextPage.value) {
        return;
    }
    currentPage.value = nextPage.value;
    void loadRooms();
}

function isOwnHappening(event: EventApi) {
    const type = (event.extendedProps as { status?: { type?: string } }).status?.type;

    return type === "user-reservation" || type === "user-booking";
}

const convertTimeToMinutes = (time: string) => {
    const [hours = "0", minutes = "0"] = time.split(":");
    return Number(hours) * 60 + Number(minutes);
};

// https://github.com/fullcalendar/fullcalendar/issues/4816
const countLines = (event: EventApi) => {
    if (!event.start || !event.end) {
        return 1;
    }

    const minutes = (event.end.getTime() - event.start.getTime()) / 1000 / 60;
    const timeSlotLength = convertTimeToMinutes(appStore.settings?.resource_group?.time_slot_length ?? "01:00");

    return minutes / timeSlotLength;
};

const setRoomCountFromScreen = () => {
    if (windowWidth.value < 600) {
        roomCount.value = 2;
    } else if (windowWidth.value < 800) {
        roomCount.value = 3;
    } else if (windowWidth.value < 1000) {
        roomCount.value = 4;
    } else {
        roomCount.value = 8;
    }
};

const handleScreenResize = () => {
    windowWidth.value = window.innerWidth;
    setRoomCountFromScreen();

    // A narrower column rewraps the names, changing which header is tallest.
    equalizeHeaderHeights();
};

const gridRoot = ref<HTMLElement | null>(null);

// Each room is an independent calendar instance, so nothing makes their header
// cells agree on a height the way the premium view's single header row did. A
// name long enough to wrap - "Workbay I (2. Etage, neben Info, ohne Strom!)" -
// would push only its own column's grid down, and the rows would stop lining
// up. Measuring the tallest header and pinning every header to it keeps the
// full name visible (clamping it would lose text the premium view showed) and
// the grids aligned.
function equalizeHeaderHeights() {
    const root = gridRoot.value;

    if (!root) {
        return;
    }

    const headers = [...root.querySelectorAll<HTMLElement>(".roomz-calendar-resource-header-inner")];

    if (!headers.length) {
        return;
    }

    // Released first so each header reports its natural height again. Measuring
    // while the pinned height is still applied would only ever ratchet upwards:
    // a page of short names would keep the room a previous long one needed.
    root.style.removeProperty("--roomz-grid-header-height");

    const tallest = Math.max(...headers.map((header) => header.getBoundingClientRect().height));

    root.style.setProperty("--roomz-grid-header-height", `${Math.ceil(tallest)}px`);
}

// Rooms arrive asynchronously and paging swaps them out, so re-measure whenever
// the set of names on screen changes.
watch(rooms, () => void nextTick(equalizeHeaderHeights), { deep: true });

watch(roomCount, () => reload());
watch(isAuthenticated, () => void loadHappenings());
watch(
    () => authStore.userHappenings,
    () => authStore.updateQuotas(date.value.toDate()),
    { deep: true },
);

onBeforeMount(() => {
    setRoomCountFromScreen();
});

onMounted(() => {
    reload();

    // The first measurement can land while the webfont is still swapping in,
    // which changes how the names wrap. Re-measure once it has settled.
    void document.fonts?.ready.then(equalizeHeaderHeights);

    Echo.channel("happenings").listen("HappeningsChangedEvent", () => {
        void loadHappenings();
    });

    window.addEventListener("resize", handleScreenResize);
});

onUnmounted(() => {
    Echo.leave("happenings");
    window.removeEventListener("resize", handleScreenResize);
});
</script>

<template>
    <h1 class="sr-only">{{ $t("sidebar.header") }}</h1>
    <div class="mb-5">
        <!-- HAPPENING COUNT START -->
        <h2 class="mb-2 block text-sm font-bold uppercase">{{ $t("quota.header") }}</h2>
        <Chip class="my-1 py-0 pl-0 pr-4 text-xs uppercase">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-primary-contrast">
                {{ happeningsCount }}
            </span>
            <span class="ml-0 text-xs font-medium">
                {{ $t("user_happenings.bookings") }}
            </span>
        </Chip>
        <!-- HAPPENING COUNT END -->

        <!-- USER HAPPENING QUOTAS START -->
        <div v-if="!can('unlimited_quotas')" class="text-sm font-medium">
            <HappeningQuotas></HappeningQuotas>
        </div>
        <!-- USER HAPPENING QUOTAS END -->
    </div>

    <div class="mb-5">
        <h2 class="mb-2 block text-sm font-bold uppercase">{{ $t("user_happenings.header") }}</h2>
        <!-- PAST HAPPENINGS TOGGLE START -->
        <div class="text-sm font-medium">
            <label class="inline-flex cursor-pointer items-center">
                <ToggleSwitch v-model="hidePast" />
                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{
                    $t("user_happenings.hide_past_happenings")
                }}</span>
            </label>
        </div>
        <!-- PAST HAPPENINGS TOGGLE END -->
    </div>

    <!-- USER HAPPENINGS START -->
    <div id="user-happenings">
        <DataView :value="happenings">
            <template #list="slotProps">
                <div v-for="(item, index) in slotProps.items" :key="index" class="mb-2">
                    <UserHappening :happening="item" :is-past="isPastHappening(item)"></UserHappening>
                </div>
            </template>
        </DataView>
    </div>
    <!-- USER HAPPENINGS END -->

    <DataView v-if="false" :value="happenings">
        <template #list="slotProps">
            <div class="flex flex-col">
                <div v-for="(item, index) in slotProps.items" :key="index">
                    <div
                        class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center"
                        :class="{ 'border-t border-surface-200 dark:border-surface-700': index !== 0 }"
                    >
                        <div class="relative md:w-24">
                            <!--<img class="block xl:block mx-auto rounded w-full" :src="item.resource?.institution.teaser_uri" :alt="item.resource?.institution.title" />-->
                            <div class="absolute bg-black/70 rounded-border" style="left: 4px; top: 4px">
                                <!--<Tag :value="item.inventoryStatus" :severity="getSeverity(item)"></Tag>-->
                                <Tag value="AKTUELL" severity="d"></Tag>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col justify-between gap-6 md:flex-row md:items-center">
                            <div class="flex flex-row items-start justify-between gap-2 md:flex-col">
                                <div>
                                    {{ item.resource?.institution }}
                                    <!--<span class="font-medium text-surface-500 dark:text-surface-400 text-sm">{{ item.category }}</span>-->
                                    <!--<div class="text-lg font-medium mt-2">{{ item.name }}</div>-->
                                </div>
                                <div>
                                    <!--<div class="bg-surface-0 flex items-center gap-2 justify-center py-1 px-2" style="border-radius: 30px; box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.04), 0px 1px 2px 0px rgba(0, 0, 0, 0.06)">-->
                                    <!--<span class="text-surface-900 font-medium text-sm">{{ item.rating }}</span>-->
                                    <Button icon="pi pi-trash" severity="danger" outlined></Button>
                                    <!--</div>-->
                                </div>
                            </div>
                            <div class="flex flex-col gap-8 md:items-end">
                                <!--<span class="text-xl font-semibold">${{ item.price }}</span>-->
                                <div class="flex flex-row-reverse gap-2 md:flex-row">
                                    <Button icon="pi pi-heart" outlined></Button>
                                    <!--<Button icon="pi pi-shopping-cart" label="Buy Now" :disabled="item.inventoryStatus === 'OUTOFSTOCK'" class="flex-auto md:flex-initial whitespace-nowrap"></Button>-->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </DataView>
    <!-- USER HAPPENINGS END -->

    <!--
    <div class="events w-full border border-gray-200 bg-white p-4 shadow sm:p-8 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <h5 class="text-xl font-bold leading-none text-gray-900 dark:text-white">
                {{ $t("user_happening.header") }}
            </h5>
            <span class="text-sm font-medium text-red-600 dark:text-red-500">
                <HappeningCount :count="happeningsCount"></HappeningCount>
            </span>
        </div>
        <div v-if="!can('unlimited_quotas')" class="text-sm font-medium">
            <HappeningQuotas></HappeningQuotas>
        </div>
        <div class="mt-4">
            <label class="inline-flex cursor-pointer items-center">
                <ToggleSwitch v-model="hidePast" />
                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{
                    $t("user_happening.hide_past_happenings")
                }}</span>
            </label>
        </div>
        <div class="flow-root">
            <div v-if="happeningsCount === 0" class="mt-5 text-sm">
                {{ $t("user_happening.no_happenings") }}
            </div>
            <TransitionGroup
                v-if="happenings"
                name="list"
                tag="ul"
                role="list"
                class="divide-y divide-gray-200 dark:divide-gray-700"
            >
                <li v-for="happening in happenings" :key="happening.id" class="py-3 sm:py-4">
                    <UserHappening :happening="happening" :is-past="isPastHappening(happening)"></UserHappening>
                </li>
            </TransitionGroup>
        </div>
    </div>
    -->
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";

import HappeningQuotas from "./HappeningQuotas.vue";
import UserHappening from "./UserHappening.vue";

import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import ToggleSwitch from "primevue/toggleswitch";
import { computed, ref } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happenings: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const hidePast = ref(true);

const happenings = computed(() => {
    if (hidePast.value) {
        return props.happenings.filter((happening) => !isPastHappening(happening));
    }

    return props.happenings;
});

const happeningsCount = computed(() => {
    return props.happenings.length;
});

const isPastHappening = (happening) => {
    return dayjs(happening.end).isBefore(dayjs.utc());
};

// ------------------------------------------------
// Methods
// ------------------------------------------------
const can = authStore.can;
</script>

<style>
@media only screen and (max-width: 1150px) {
    .events {
        margin-top: 30px;
    }
}

.list-move, /* apply transition to moving elements */
.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
    transform: translateX(30px);
    opacity: 0;
}

/* ensure leaving items are taken out of layout flow so that moving
   animations can be calculated correctly. */
.list-leave-active {
    position: absolute;
}
</style>

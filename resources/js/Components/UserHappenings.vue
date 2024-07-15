<template>
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
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";

import HappeningCount from "./HappeningCount.vue";
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
const hidePast = ref(false);

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

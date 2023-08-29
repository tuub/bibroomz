<template>
    <div
        class="w-full max-w-md p-4 bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700"
    >
        <div class="flex items-center justify-between mb-4">
            <h5
                class="text-xl font-bold leading-none text-gray-900 dark:text-white"
            >
                {{ $t("user_happening.header") }}
            </h5>
            <span class="text-sm font-medium text-blue-600 dark:text-blue-500">
                <HappeningCount :count="happeningsCount"></HappeningCount>
            </span>
        </div>
        <div v-if="!can('unlimited quotas')" class="text-sm font-medium">
            <HappeningQuotas></HappeningQuotas>
        </div>
        <div class="mt-4">
            <label class="relative inline-flex items-center cursor-pointer">
                <input
                    v-model="hidePast"
                    type="checkbox"
                    class="sr-only peer"
                />
                <div
                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"
                ></div>
                <span
                    class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >{{ $t("user_happening.hide_past_happenings") }}</span
                >
            </label>
        </div>
        <div class="flow-root">
            <div v-if="happeningsCount === 0" class="text-sm mt-5">
                {{ $t("user_happening.no_happenings") }}
            </div>
            <TransitionGroup
                v-if="happenings"
                name="list"
                tag="ul"
                role="list"
                class="divide-y divide-gray-200 dark:divide-gray-700"
            >
                <li
                    v-for="happening in happenings"
                    :key="happening.id"
                    class="py-3 sm:py-4"
                >
                    <UserHappening
                        :happening="happening"
                        :is-past="isPastHappening(happening)"
                    ></UserHappening>
                </li>
            </TransitionGroup>
        </div>
    </div>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";
import UserHappening from "./UserHappening.vue";
import { computed, ref } from "vue";
import HappeningCount from "./HappeningCount.vue";
import HappeningQuotas from "./HappeningQuotas.vue";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";

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
        return props.happenings.filter(
            (happening) => !isPastHappening(happening)
        );
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
.list-move, /* apply transition to moving elements */
.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

/* ensure leaving items are taken out of layout flow so that moving
   animations can be calculated correctly. */
.list-leave-active {
    position: absolute;
}
</style>

<template>
    <div class="w-full max-w-md p-4 bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h5 class="text-xl font-bold leading-none text-gray-900 dark:text-white">
                My reservations
            </h5>
            <span class="text-sm font-medium text-blue-600 dark:text-blue-500">
                <HappeningCount :count="happeningsCount"></HappeningCount>
            </span>
        </div>
        <div class="flow-root">
            <div v-if="happeningsCount === 0" class="text-sm mt-5">
                You have no current reservations.
            </div>
            <TransitionGroup v-if="happenings"
                             name="list"
                             tag="ul"
                             role="list"
                             class="divide-y divide-gray-200 dark:divide-gray-700">
                <li class="py-3 sm:py-4"
                    v-for="(happening, index) in happenings"
                    :key="happening.id">
                    <UserHappening :index="index" :happening="happening"></UserHappening>
                </li>
            </TransitionGroup>
        </div>
    </div>
</template>

<script setup>
import UserHappening from './UserHappening.vue'
import {computed} from "vue";
import HappeningCount from "./HappeningCount.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    happenings: Object,
})

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happeningsCount = computed(() => {
    return props.happenings.length
})

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

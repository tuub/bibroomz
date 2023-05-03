<template>
    <h1 class="text-xl font-bold">My Reservations</h1>
    <div v-if="happeningsCount === 0" class="text-sm mt-5">
        You have no current reservations. Add some!
    </div>
    <div v-if="happenings">
        <HappeningCount :count="happeningsCount"></HappeningCount>
        <ul class="list-decimal mt-5 ml-5" v-if="happenings">
            <!--
            <TransitionGroup name="list"
                             tag="ul"
                             enter-from-class="opacity-0 scale-125"
                             enter-to-class="opacity-100 scale-100"
                             enter-active-class="transition duration-300"
                             leave-from-class="opacity-100 scale-100"
                             leave-to-class="opacity-0 scale-125"
                             leave-active-class="transition duration-200"
            >
            -->
            <TransitionGroup name="list" tag="ul">
                <li v-for="happening in happenings" :key="happening.id" class="mb-2">
                    <UserHappening :happening="happening"></UserHappening>
                </li>
            </TransitionGroup>
        </ul>
    </div>
</template>

<script setup>

import UserHappening from './UserHappening.vue'
import {computed} from "vue";
import HappeningCount from "./HappeningCount.vue";

let props = defineProps({
    happenings: Object,
})

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

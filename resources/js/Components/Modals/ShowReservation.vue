<!-- AddReservation.vue -->
<template>
    <div class="text-3xl font-bold">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>
    <ReservationInfo :reservation="payload"></ReservationInfo>
</template>

<script setup>
import ReservationInfo from "../ReservationInfo.vue";

const props = defineProps({
    content: Object,
    payload: Object
})
</script>

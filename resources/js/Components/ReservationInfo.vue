<template>
    <h1 class="text-xl">Reservation Info</h1>

    <Label :text="reservationResource" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-map-pin-fill"></Label>
    <Label :text="reservationDate" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-calendar-2-line"></Label>
    <Label :text="reservationStartDisplay" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-time-line"></Label>
    <Label :text="reservationEndDisplay" bg-color-class="bg-gray-200" text-color-class="text-gray-700" icon-class="ri-time-fill"></Label>

</template>

<script setup>
import Label from "../Shared/Label.vue";
import dayjs from "dayjs";
import utc from 'dayjs/plugin/utc';
import {computed} from "vue";

dayjs.extend(utc);

let props = defineProps({
    reservation: Object,
});

const reservationResource = computed(() => {
    return props.reservation.resource.title;
})

const reservationDate = computed(() => {
    return dayjs(props.reservation.start).format('DD.MM.YYYY');
})

const reservationStartDisplay = computed(() => {
    return dayjs(props.reservation.start).format('HH:mm');
})

const reservationEndDisplay = computed(() => {
    return dayjs(props.reservation.end).format('HH:mm');
})
</script>

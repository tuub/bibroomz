<template>
    <h1 class="text-xl">Reservation Info</h1>

    <Label :text="reservationResource" bg-color="whitesmoke" text-color-class="pink-300" icon-class="ri-map-pin-fill"></Label>
    <Label :text="reservationDate" bg-color-class="blue-200" text-color="white" icon-class="ri-calendar-2-line"></Label>
    <!--
    <Label :text="reservationStartDisplay" background-color="whitesmoke" text-color="#363636" icon-class="ri-time-line"></Label>
    <Label :text="reservationEndDisplay" background-color="whitesmoke" text-color="#363636" icon-class="ri-time-fill"></Label>
    -->
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
